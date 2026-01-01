<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supply;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceOrder;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplyController extends Controller
{
    /**
     * Get WB Order Service for account
     */
    protected function getWbOrderService(MarketplaceAccount $account): WildberriesOrderService
    {
        $httpClient = new WildberriesHttpClient($account);
        return new WildberriesOrderService($httpClient);
    }

    /**
     * Получить список поставок
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'status' => ['nullable', 'string'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $query = Supply::query()
            ->whereHas('account', fn($q) => $q->where('company_id', $request->company_id))
            ->with('account')
            ->forAccount($request->marketplace_account_id);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $supplies = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'supplies' => $supplies,
        ]);
    }

    /**
     * Получить открытые поставки (доступные для добавления заказов)
     */
    public function open(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $supplies = Supply::query()
            ->whereHas('account', fn($q) => $q->where('company_id', $request->company_id))
            ->forAccount($request->marketplace_account_id)
            ->open()
            ->with('account')
            ->orderBy('created_at', 'desc')
            ->get();

        // Добавляем маркеры синхронизации с WB
        $supplies->each(function($supply) {
            $supply->is_synced_with_wb = !empty($supply->external_supply_id);
            $supply->needs_sync = empty($supply->external_supply_id) && $supply->account->marketplace === 'wb';
        });

        return response()->json([
            'supplies' => $supplies,
        ])->header('Cache-Control', 'no-cache, no-store, must-revalidate')
          ->header('Pragma', 'no-cache')
          ->header('Expires', '0');
    }

    /**
     * Создать новую поставку
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'company_id' => ['required', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'external_supply_id' => ['nullable', 'string', 'max:255'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Проверяем, что аккаунт принадлежит компании
        $account = MarketplaceAccount::where('id', $validated['marketplace_account_id'])
            ->where('company_id', $validated['company_id'])
            ->first();

        if (!$account) {
            return response()->json(['message' => 'Аккаунт не найден.'], 404);
        }

        try {
            DB::beginTransaction();

            // Создаём локальную поставку
            $supply = Supply::create([
                'marketplace_account_id' => $validated['marketplace_account_id'],
                'external_supply_id' => $validated['external_supply_id'] ?? null,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'status' => Supply::STATUS_DRAFT,
            ]);

            // Если это WB и external_supply_id не указан, создаём поставку в WB автоматически
            if ($account->marketplace === 'wb' && empty($validated['external_supply_id'])) {
                try {
                    $orderService = $this->getWbOrderService($account);
                    $wbSupply = $orderService->createSupply($account, $validated['name']);

                    $externalSupplyId = $wbSupply['id'] ?? $wbSupply['supplyId'] ?? null;

                    // Обновляем external_supply_id
                    $supply->update([
                        'external_supply_id' => $externalSupplyId,
                    ]);

                    Log::info('Supply auto-synced with WB', [
                        'supply_id' => $supply->id,
                        'external_supply_id' => $externalSupplyId,
                    ]);

                    // Баркод будет загружен автоматически при закрытии поставки (метод close())
                } catch (\Exception $e) {
                    // Логируем ошибку, но не прерываем создание локальной поставки
                    Log::warning('Failed to auto-sync supply with WB (supply created locally)', [
                        'supply_id' => $supply->id,
                        'account_id' => $account->id,
                        'error' => $e->getMessage(),
                    ]);
                    // Не прерываем транзакцию - поставка создана локально, можно синхронизировать позже
                }
            }

            DB::commit();

            return response()->json([
                'supply' => $supply->fresh()->load('account'),
                'message' => 'Поставка создана успешно.',
                'synced_with_wb' => !empty($supply->external_supply_id),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create supply', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка создания поставки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить информацию о поставке
     */
    public function show(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $supply->load(['account', 'orders']);

        return response()->json([
            'supply' => $supply,
        ]);
    }

    /**
     * Обновить поставку
     */
    public function update(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$supply->canEdit()) {
            return response()->json(['message' => 'Поставка не может быть отредактирована.'], 422);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['sometimes', 'in:draft,in_assembly,ready,sent,delivered,cancelled'],
        ]);

        $supply->update($validated);

        return response()->json([
            'supply' => $supply->fresh()->load('account'),
            'message' => 'Поставка обновлена успешно.',
        ]);
    }

    /**
     * Добавить заказ в поставку
     */
    public function addOrder(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$supply->canAddOrders()) {
            return response()->json(['message' => 'Поставка закрыта для добавления заказов.'], 422);
        }

        $validated = $request->validate([
            'order_id' => ['required', 'exists:marketplace_orders,id'],
        ]);

        $order = MarketplaceOrder::findOrFail($validated['order_id']);

        // Проверяем, что заказ принадлежит тому же аккаунту
        if ($order->marketplace_account_id !== $supply->marketplace_account_id) {
            return response()->json(['message' => 'Заказ принадлежит другому аккаунту.'], 422);
        }

        // Проверяем, что заказ ещё не в другой поставке
        // (заказ может иметь supply_id, но мы проверяем не совпадает ли он с текущей поставкой)
        if ($order->supply_id && $order->supply_id !== $supply->external_supply_id) {
            return response()->json([
                'message' => 'Заказ уже добавлен в другую поставку.',
                'current_supply_id' => $order->supply_id,
            ], 422);
        }

        // Проверяем статус заказа для WB (используем wb_status_group)
        if ($supply->account->marketplace === 'wb') {
            // Для WB можем добавлять заказы в статусе "new" или "assembling"
            if (!in_array($order->wb_status_group, ['new', 'assembling'])) {
                return response()->json([
                    'message' => 'Заказ не может быть добавлен в поставку (неподходящий статус).',
                    'current_status_group' => $order->wb_status_group,
                ], 422);
            }

            // Проверяем, что заказ ещё не в поставке WB (wb_supplier_status)
            if ($order->supply_id === $supply->external_supply_id) {
                return response()->json(['message' => 'Заказ уже в этой поставке.'], 422);
            }
        } else {
            // Для других маркетплейсов используем общий статус
            if (!in_array($order->status, ['new', 'confirmed', 'packed'])) {
                return response()->json([
                    'message' => 'Заказ не может быть добавлен в поставку (неподходящий статус).',
                    'current_status' => $order->status,
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Для WB FBS заказы добавляются в поставку через PATCH /api/v3/supplies/{supplyId}/orders
            // Но API может возвращать ошибку, если заказ не в нужном статусе
            // В таком случае просто обновляем локально и синхронизация произойдёт позже
            if ($supply->account->marketplace === 'wb' &&
                $supply->external_supply_id &&
                str_starts_with($supply->external_supply_id, 'WB-')) {

                try {
                    // Пробуем добавить заказ в WB поставку через API
                    $orderService = $this->getWbOrderService($supply->account);
                    $orderService->addOrdersToSupply(
                        $supply->account,
                        $supply->external_supply_id,
                        [$order->external_order_id]
                    );

                    Log::info('Order added to WB supply via API', [
                        'order_id' => $order->id,
                        'external_order_id' => $order->external_order_id,
                        'supply_id' => $supply->id,
                        'external_supply_id' => $supply->external_supply_id,
                    ]);
                } catch (\Exception $e) {
                    // Логируем ошибку WB API, но продолжаем - добавим локально
                    Log::warning('Failed to add order to WB supply via API (will sync locally)', [
                        'order_id' => $order->id,
                        'supply_id' => $supply->id,
                        'error' => $e->getMessage(),
                        'note' => 'Order will be linked locally and synced later',
                    ]);
                    // НЕ прерываем транзакцию - продолжаем добавление локально
                }
            }

            // Обновляем supply_id у заказа
            $supplyId = $supply->external_supply_id ?? 'SUPPLY-' . $supply->id;
            $order->update([
                'supply_id' => $supplyId,
            ]);

            // Пересчитываем статистику поставки
            $supply->recalculateStats();

            DB::commit();

            return response()->json([
                'supply' => $supply->fresh()->load('account'),
                'order' => $order->fresh(),
                'message' => 'Заказ добавлен в поставку.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to add order to supply', [
                'order_id' => $order->id,
                'supply_id' => $supply->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка добавления заказа: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить заказ из поставки
     */
    public function removeOrder(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$supply->canEdit()) {
            return response()->json(['message' => 'Поставка не может быть отредактирована.'], 422);
        }

        $validated = $request->validate([
            'order_id' => ['required', 'exists:marketplace_orders,id'],
        ]);

        $order = MarketplaceOrder::findOrFail($validated['order_id']);

        // Проверяем, что заказ в этой поставке
        $supplyId = $supply->external_supply_id ?? 'SUPPLY-' . $supply->id;
        if ($order->supply_id !== $supplyId) {
            return response()->json(['message' => 'Заказ не найден в этой поставке.'], 422);
        }

        try {
            DB::beginTransaction();

            // Если это WB и поставка синхронизирована, удаляем заказ через API
            if ($supply->account->marketplace === 'wb' &&
                $supply->external_supply_id &&
                str_starts_with($supply->external_supply_id, 'WB-')) {

                try {
                    // Удаляем заказ из WB поставки
                    $orderService = $this->getWbOrderService($supply->account);
                    $orderService->removeOrderFromSupply(
                        $supply->account,
                        $supply->external_supply_id,
                        (int)$order->external_order_id
                    );

                    Log::info('Order removed from WB supply via API', [
                        'order_id' => $order->id,
                        'external_order_id' => $order->external_order_id,
                        'supply_id' => $supply->id,
                        'external_supply_id' => $supply->external_supply_id,
                    ]);
                } catch (\Exception $e) {
                    DB::rollBack();

                    Log::error('Failed to remove order from WB supply via API', [
                        'order_id' => $order->id,
                        'supply_id' => $supply->id,
                        'error' => $e->getMessage(),
                    ]);

                    return response()->json([
                        'message' => 'Ошибка удаления заказа из поставки WB: ' . $e->getMessage(),
                    ], 500);
                }
            }

            // Обновляем supply_id у заказа
            $order->update([
                'supply_id' => null,
            ]);

            // Пересчитываем статистику поставки
            $supply->recalculateStats();

            DB::commit();

            return response()->json([
                'supply' => $supply->fresh()->load('account'),
                'order' => $order->fresh(),
                'message' => 'Заказ удалён из поставки.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to remove order from supply', [
                'order_id' => $order->id,
                'supply_id' => $supply->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка удаления заказа: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Закрыть поставку
     */
    public function close(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$supply->canAddOrders()) {
            return response()->json(['message' => 'Поставка уже закрыта.'], 422);
        }

        try {
            DB::beginTransaction();

            // Закрываем поставку
            $supply->close();

            // Если это WB и поставка синхронизирована, загружаем баркод/QR код
            if ($supply->account->marketplace === 'wb' &&
                $supply->external_supply_id &&
                str_starts_with($supply->external_supply_id, 'WB-')) {

                try {
                    $orderService = $this->getWbOrderService($supply->account);
                    $barcode = $orderService->getSupplyBarcode($supply->account, $supply->external_supply_id, 'png');

                    // Сохраняем баркод в storage/app/supplies/barcodes/
                    $barcodePath = "supplies/barcodes/{$supply->id}.png";
                    \Storage::put($barcodePath, $barcode['file_content']);

                    $supply->update([
                        'barcode_path' => $barcodePath,
                    ]);

                    Log::info('Supply barcode downloaded after closing', [
                        'supply_id' => $supply->id,
                        'barcode_path' => $barcodePath,
                    ]);
                } catch (\Exception $e) {
                    // Логируем ошибку загрузки баркода, но не прерываем процесс
                    Log::warning('Failed to download supply barcode after closing', [
                        'supply_id' => $supply->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'supply' => $supply->fresh()->load('account'),
                'message' => 'Поставка закрыта.',
                'barcode_downloaded' => !empty($supply->barcode_path),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to close supply', [
                'supply_id' => $supply->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка закрытия поставки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Удалить поставку
     */
    public function destroy(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($supply->orders_count > 0) {
            return response()->json(['message' => 'Нельзя удалить поставку с заказами.'], 422);
        }

        try {
            // Если поставка синхронизирована с WB, сначала отменяем её там
            if ($supply->external_supply_id &&
                str_starts_with($supply->external_supply_id, 'WB-') &&
                $supply->account->marketplace === 'wb') {

                try {
                    $orderService = $this->getWbOrderService($supply->account);
                    $orderService->cancelSupply($supply->account, $supply->external_supply_id);

                    Log::info('Supply cancelled in WB before deletion', [
                        'supply_id' => $supply->id,
                        'external_supply_id' => $supply->external_supply_id,
                    ]);
                } catch (\Exception $e) {
                    // Логируем ошибку, но продолжаем удаление локально
                    Log::warning('Failed to cancel supply in WB, deleting locally anyway', [
                        'supply_id' => $supply->id,
                        'external_supply_id' => $supply->external_supply_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            $supply->delete();

            return response()->json([
                'message' => 'Поставка удалена.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to delete supply', [
                'supply_id' => $supply->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка удаления поставки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Синхронизировать поставку с WB (создать в WB если ещё не создана)
     */
    public function syncWithWb(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Проверяем, что аккаунт - Wildberries
        if (!$supply->account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 422);
        }

        // Проверяем, что поставка ещё не синхронизирована
        if ($supply->external_supply_id && str_starts_with($supply->external_supply_id, 'WB-')) {
            return response()->json(['message' => 'Поставка уже синхронизирована с WB.'], 422);
        }

        try {
            $orderService = $this->getWbOrderService($supply->account);

            // Создаём поставку в WB
            $wbSupply = $orderService->createSupply($supply->account, $supply->name);

            // Обновляем нашу поставку с external_supply_id из WB
            $supply->update([
                'external_supply_id' => $wbSupply['id'] ?? $wbSupply['supplyId'] ?? null,
            ]);

            // Баркод будет загружен автоматически при закрытии поставки (метод close())

            // Если в поставке уже есть заказы, добавляем их в WB поставку
            if ($supply->orders_count > 0 && $supply->external_supply_id) {
                $orders = MarketplaceOrder::where('supply_id', 'SUPPLY-' . $supply->id)->get();
                $orderIds = $orders->pluck('external_order_id')->filter()->toArray();

                if (!empty($orderIds)) {
                    $orderService->addOrdersToSupply(
                        $supply->account,
                        $supply->external_supply_id,
                        $orderIds
                    );
                }

                // Обновляем supply_id у заказов на external_supply_id
                MarketplaceOrder::where('supply_id', 'SUPPLY-' . $supply->id)
                    ->update(['supply_id' => $supply->external_supply_id]);
            }

            return response()->json([
                'supply' => $supply->fresh()->load('account'),
                'message' => 'Поставка успешно синхронизирована с Wildberries.',
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync supply with WB', [
                'supply_id' => $supply->id,
                'account_id' => $supply->marketplace_account_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка синхронизации с WB: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить баркод поставки из WB
     */
    public function barcode(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Проверяем, что аккаунт - Wildberries
        if (!$supply->account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 422);
        }

        // Проверяем, что поставка синхронизирована с WB
        if (!$supply->external_supply_id || !str_starts_with($supply->external_supply_id, 'WB-')) {
            return response()->json(['message' => 'Поставка не синхронизирована с WB. Сначала синхронизируйте поставку.'], 422);
        }

        $type = $request->input('type', 'png');

        try {
            $orderService = $this->getWbOrderService($supply->account);
            $result = $orderService->getSupplyBarcode($supply->account, $supply->external_supply_id, $type);

            // Возвращаем base64 чтобы frontend мог скачать через blob
            // Это решает проблему с авторизацией при открытии в новой вкладке
            return response()->json([
                'success' => true,
                'file_content' => base64_encode($result['file_content']),
                'content_type' => $result['content_type'],
                'format' => $result['format'],
                'filename' => "supply-{$supply->id}-barcode.{$result['format']}",
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get supply barcode', [
                'supply_id' => $supply->id,
                'external_supply_id' => $supply->external_supply_id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка получения баркода: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Передать поставку в доставку
     * POST /api/marketplace/supplies/{supply}/deliver
     */
    public function deliver(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Проверка что поставка закрыта
        if ($supply->status !== Supply::STATUS_READY) {
            return response()->json([
                'message' => 'Поставку можно передать в доставку только в статусе "Готова".',
            ], 422);
        }

        // Проверка что есть external_supply_id
        if (!$supply->external_supply_id) {
            return response()->json([
                'message' => 'Поставка не синхронизирована с WB.',
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Передаём поставку в доставку в WB
            $orderService = $this->getWbOrderService($supply->account);
            $orderService->deliverSupply($supply->account, $supply->external_supply_id);

            // Обновляем статус поставки
            $supply->update([
                'status' => Supply::STATUS_SENT,
                'delivery_started_at' => now(),
            ]);

            DB::commit();

            Log::info('Supply delivered to WB', [
                'supply_id' => $supply->id,
                'external_supply_id' => $supply->external_supply_id,
            ]);

            return response()->json([
                'supply' => $supply->fresh()->load('account'),
                'message' => 'Поставка передана в доставку.',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to deliver supply', [
                'supply_id' => $supply->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Ошибка передачи поставки в доставку: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить короба (tares) поставки
     * GET /api/marketplace/supplies/{supply}/tares
     */
    public function tares(Request $request, Supply $supply): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($supply->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Загружаем короба с количеством заказов
        $tares = $supply->tares()->get();

        return response()->json([
            'success' => true,
            'tares' => $tares,
            'count' => $tares->count(),
        ]);
    }
}
