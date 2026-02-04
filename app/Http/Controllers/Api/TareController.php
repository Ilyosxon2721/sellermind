<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Models\Tare;
use App\Models\WbOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TareController extends Controller
{
    /**
     * Get all tares for a supply
     */
    public function index(Supply $supply): JsonResponse
    {
        $tares = $supply->tares()
            ->withCount('orders')
            ->with('orders:id,external_order_id,tare_id,article,nm_id,total_amount')
            ->get();

        return response()->json([
            'tares' => $tares,
        ]);
    }

    /**
     * Create a new tare for a supply
     *
     * Для WB короба создаются через API /api/v3/supplies/{supplyId}/trbx
     */
    public function store(Request $request, Supply $supply): JsonResponse
    {
        // Проверяем, что поставка синхронизирована с WB
        if ($supply->account->marketplace === 'wb' &&
            (! $supply->external_supply_id || ! str_starts_with($supply->external_supply_id, 'WB-'))) {
            return response()->json([
                'message' => 'Поставка не синхронизирована с WB. Сначала синхронизируйте поставку.',
            ], 422);
        }

        try {
            // Создаём HTTP клиент и сервис заказов
            $httpClient = new \App\Services\Marketplaces\Wildberries\WildberriesHttpClient($supply->account);
            $orderService = new \App\Services\Marketplaces\Wildberries\WildberriesOrderService($httpClient);

            // Создаём короб через WB API
            // WB API возвращает массив с trbxIds - IDs созданных коробов
            $wbResponse = $orderService->createTare($supply->account, $supply->external_supply_id, 1);

            if (empty($wbResponse['trbxIds'])) {
                throw new \Exception('WB API не вернул ID созданного короба');
            }

            $trbxId = $wbResponse['trbxIds'][0];

            // Сохраняем короб в нашей БД с данными от WB
            $tare = $supply->tares()->create([
                'external_tare_id' => $trbxId,
                'barcode' => $trbxId, // WB использует trbxId как barcode для коробов
                'orders_count' => 0,
            ]);

            Log::info('Tare created via WB API', [
                'tare_id' => $tare->id,
                'external_tare_id' => $tare->external_tare_id,
                'supply_id' => $supply->id,
                'external_supply_id' => $supply->external_supply_id,
            ]);

            return response()->json([
                'message' => 'Коробка успешно создана.',
                'tare' => $tare,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Failed to create tare via WB API', [
                'supply_id' => $supply->id,
                'external_supply_id' => $supply->external_supply_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Не удалось создать коробку: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get a specific tare with its orders
     */
    public function show(Tare $tare): JsonResponse
    {
        $tare->load([
            'supply:id,external_supply_id,name,status',
            'orders' => function ($query) {
                $query->select(
                    'id',
                    'tare_id',
                    'external_order_id',
                    'supply_id',
                    'article',
                    'nm_id',
                    'total_amount',
                    'status',
                    'wb_supplier_status',
                    'wb_status_group'
                );
            },
        ]);

        return response()->json([
            'tare' => $tare,
        ]);
    }

    /**
     * Update tare details
     */
    public function update(Request $request, Tare $tare): JsonResponse
    {
        $validated = $request->validate([
            'external_tare_id' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
        ]);

        $tare->update($validated);

        Log::info('Tare updated', [
            'tare_id' => $tare->id,
            'changes' => $validated,
        ]);

        return response()->json([
            'message' => 'Коробка успешно обновлена.',
            'tare' => $tare->fresh(),
        ]);
    }

    /**
     * Delete a tare
     */
    public function destroy(Tare $tare): JsonResponse
    {
        $tareId = $tare->id;
        $ordersCount = $tare->orders_count;

        // Remove orders from tare before deleting
        if ($ordersCount > 0) {
            $tare->orders()->update(['tare_id' => null]);
        }

        $tare->delete();

        Log::info('Tare deleted', [
            'tare_id' => $tareId,
            'orders_removed' => $ordersCount,
        ]);

        return response()->json([
            'message' => 'Коробка успешно удалена.',
            'orders_removed' => $ordersCount,
        ]);
    }

    /**
     * Add order to tare
     */
    public function addOrder(Request $request, Tare $tare): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:wb_orders,id'],
        ]);

        $order = WbOrder::findOrFail($validated['order_id']);

        // Check if order belongs to the same supply
        if ($order->supply_id !== $tare->supply->external_supply_id) {
            return response()->json([
                'message' => 'Заказ не принадлежит данной поставке.',
            ], 422);
        }

        // Check if order already in another tare of the same supply
        if ($order->tare_id && $order->tare_id !== $tare->id) {
            $currentTare = Tare::find($order->tare_id);
            if ($currentTare && $currentTare->supply_id === $tare->supply_id) {
                return response()->json([
                    'message' => 'Заказ уже находится в другой коробке этой поставки.',
                    'current_tare_id' => $currentTare->id,
                ], 422);
            }
        }

        try {
            // Для WB добавляем заказ в короб через API
            if ($tare->supply->account->marketplace === 'wb' && $tare->external_tare_id) {
                $httpClient = new \App\Services\Marketplaces\Wildberries\WildberriesHttpClient($tare->supply->account);
                $orderService = new \App\Services\Marketplaces\Wildberries\WildberriesOrderService($httpClient);

                // Добавляем заказ в короб через WB API
                $orderService->addOrdersToTare(
                    $tare->supply->account,
                    $tare->supply->external_supply_id,
                    $tare->external_tare_id,
                    [(int) $order->external_order_id]
                );

                Log::info('Order added to tare via WB API', [
                    'tare_id' => $tare->id,
                    'external_tare_id' => $tare->external_tare_id,
                    'order_id' => $order->id,
                    'external_order_id' => $order->external_order_id,
                ]);
            }

            // Обновляем в нашей БД
            $order->tare_id = $tare->id;
            $order->save();

            $tare->updateOrdersCount();

            return response()->json([
                'message' => 'Заказ добавлен в коробку.',
                'tare' => $tare->fresh(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to add order to tare', [
                'tare_id' => $tare->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Не удалось добавить заказ в коробку: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove order from tare
     */
    public function removeOrder(Request $request, Tare $tare): JsonResponse
    {
        $validated = $request->validate([
            'order_id' => ['required', 'exists:wb_orders,id'],
        ]);

        $order = WbOrder::findOrFail($validated['order_id']);

        if ($order->tare_id !== $tare->id) {
            return response()->json([
                'message' => 'Заказ не находится в этой коробке.',
            ], 422);
        }

        $order->tare_id = null;
        $order->save();

        $tare->updateOrdersCount();

        Log::info('Order removed from tare', [
            'tare_id' => $tare->id,
            'order_id' => $order->id,
            'external_order_id' => $order->external_order_id,
        ]);

        return response()->json([
            'message' => 'Заказ удалён из коробки.',
            'tare' => $tare->fresh(),
        ]);
    }

    /**
     * Get barcode for tare (download barcode file from WB)
     */
    public function getBarcode(Request $request, Tare $tare)
    {
        // Check if supply belongs to Wildberries account
        if (! $tare->supply->account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 422);
        }

        // Check if supply is synced with WB
        if (! $tare->supply->external_supply_id || ! str_starts_with($tare->supply->external_supply_id, 'WB-')) {
            return response()->json(['message' => 'Поставка не синхронизирована с WB.'], 422);
        }

        // Check if tare has barcode or external_tare_id
        $tareId = $tare->external_tare_id ?? $tare->barcode;
        if (! $tareId) {
            return response()->json(['message' => 'У коробки нет штрихкода или ID.'], 422);
        }

        $type = $request->input('type', 'png');

        try {
            // Создаём HTTP клиент и сервис заказов вручную
            $httpClient = new \App\Services\Marketplaces\Wildberries\WildberriesHttpClient($tare->supply->account);
            $orderService = new \App\Services\Marketplaces\Wildberries\WildberriesOrderService($httpClient);

            $result = $orderService->getTareBarcode(
                $tare->supply->account,
                $tare->supply->external_supply_id,
                $tareId,
                $type
            );

            return response($result['file_content'], 200)
                ->header('Content-Type', $result['content_type'])
                ->header('Content-Disposition', "attachment; filename=\"tare-{$tare->id}-barcode.{$result['format']}\"");

        } catch (\Exception $e) {
            Log::error('Failed to get tare barcode', [
                'tare_id' => $tare->id,
                'supply_id' => $tare->supply_id,
                'external_tare_id' => $tareId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка получения баркода: '.$e->getMessage(),
            ], 500);
        }
    }
}
