<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\OrderStockReturn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API контроллер для управления возвратами товаров
 */
class OrderStockReturnController extends Controller
{
    use HasPaginatedResponse;

    /**
     * Получить список возвратов
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'marketplace_account_id' => ['nullable', 'exists:marketplace_accounts,id'],
            'status' => ['nullable', 'in:pending,processed,rejected'],
            'order_type' => ['nullable', 'in:wb,uzum,ozon'],
        ]);

        if (! $request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $query = OrderStockReturn::forCompany($request->company_id)
            ->with(['marketplaceAccount', 'processedByUser'])
            ->orderByDesc('returned_at');

        if ($request->marketplace_account_id) {
            $query->forAccount($request->marketplace_account_id);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->order_type) {
            $query->where('order_type', $request->order_type);
        }

        $perPage = $this->getPerPage($request, 50);

        $returns = $query->paginate($perPage);

        // Добавляем информацию о заказе
        $returns->getCollection()->transform(function ($return) {
            $order = $return->getOrder();

            return [
                'id' => $return->id,
                'order_type' => $return->order_type,
                'order_id' => $return->order_id,
                'external_order_id' => $return->external_order_id,
                'status' => $return->status,
                'action' => $return->action,
                'return_reason' => $return->return_reason,
                'returned_at' => $return->returned_at?->format('Y-m-d H:i:s'),
                'processed_at' => $return->processed_at?->format('Y-m-d H:i:s'),
                'process_notes' => $return->process_notes,
                'marketplace_account' => [
                    'id' => $return->marketplaceAccount?->id,
                    'name' => $return->marketplaceAccount?->name,
                    'marketplace' => $return->marketplaceAccount?->marketplace,
                ],
                'processed_by' => $return->processedByUser ? [
                    'id' => $return->processedByUser->id,
                    'name' => $return->processedByUser->name,
                ] : null,
                'order' => $order ? [
                    'total_amount' => $order->total_amount ?? $order->total_price ?? 0,
                    'ordered_at' => $order->ordered_at?->format('Y-m-d H:i:s') ?? $order->created_at_ozon?->format('Y-m-d H:i:s'),
                    'customer_name' => $order->customer_name ?? null,
                ] : null,
            ];
        });

        return response()->json([
            'returns' => $returns->items(),
            'meta' => $this->paginationMeta($returns),
        ]);
    }

    /**
     * Получить детали возврата
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $return = OrderStockReturn::with(['marketplaceAccount', 'processedByUser'])->find($id);

        if (! $return) {
            return response()->json(['message' => 'Возврат не найден.'], 404);
        }

        if (! $request->user()->hasCompanyAccess($return->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $order = $return->getOrder();

        // Получаем товары из заказа
        $items = [];
        if ($order) {
            $orderStockService = new \App\Services\Stock\OrderStockService;
            $items = $orderStockService->getOrderItems($order, $return->order_type);
        }

        return response()->json([
            'return' => [
                'id' => $return->id,
                'order_type' => $return->order_type,
                'order_id' => $return->order_id,
                'external_order_id' => $return->external_order_id,
                'status' => $return->status,
                'action' => $return->action,
                'return_reason' => $return->return_reason,
                'returned_at' => $return->returned_at?->format('Y-m-d H:i:s'),
                'processed_at' => $return->processed_at?->format('Y-m-d H:i:s'),
                'process_notes' => $return->process_notes,
                'marketplace_account' => [
                    'id' => $return->marketplaceAccount?->id,
                    'name' => $return->marketplaceAccount?->name,
                    'marketplace' => $return->marketplaceAccount?->marketplace,
                ],
                'processed_by' => $return->processedByUser ? [
                    'id' => $return->processedByUser->id,
                    'name' => $return->processedByUser->name,
                ] : null,
                'order' => $order ? [
                    'total_amount' => $order->total_amount ?? $order->total_price ?? 0,
                    'ordered_at' => $order->ordered_at?->format('Y-m-d H:i:s') ?? $order->created_at_ozon?->format('Y-m-d H:i:s'),
                    'customer_name' => $order->customer_name ?? null,
                    'items' => $items,
                ] : null,
            ],
        ]);
    }

    /**
     * Обработать возврат - вернуть товар на склад
     */
    public function returnToStock(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = OrderStockReturn::find($id);

        if (! $return) {
            return response()->json(['message' => 'Возврат не найден.'], 404);
        }

        if (! $request->user()->hasCompanyAccess($return->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($return->status !== OrderStockReturn::STATUS_PENDING) {
            return response()->json(['message' => 'Возврат уже обработан.'], 422);
        }

        $success = $return->processReturnToStock($request->user(), $request->notes);

        if (! $success) {
            return response()->json(['message' => 'Не удалось обработать возврат.'], 500);
        }

        return response()->json([
            'message' => 'Товар возвращён на склад.',
            'return' => [
                'id' => $return->id,
                'status' => $return->status,
                'action' => $return->action,
                'processed_at' => $return->processed_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Обработать возврат - списать товар
     */
    public function writeOff(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = OrderStockReturn::find($id);

        if (! $return) {
            return response()->json(['message' => 'Возврат не найден.'], 404);
        }

        if (! $request->user()->hasCompanyAccess($return->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($return->status !== OrderStockReturn::STATUS_PENDING) {
            return response()->json(['message' => 'Возврат уже обработан.'], 422);
        }

        $success = $return->processWriteOff($request->user(), $request->notes);

        if (! $success) {
            return response()->json(['message' => 'Не удалось обработать возврат.'], 500);
        }

        return response()->json([
            'message' => 'Товар списан.',
            'return' => [
                'id' => $return->id,
                'status' => $return->status,
                'action' => $return->action,
                'processed_at' => $return->processed_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Отклонить возврат (ошибочная запись)
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = OrderStockReturn::find($id);

        if (! $return) {
            return response()->json(['message' => 'Возврат не найден.'], 404);
        }

        if (! $request->user()->hasCompanyAccess($return->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($return->status !== OrderStockReturn::STATUS_PENDING) {
            return response()->json(['message' => 'Возврат уже обработан.'], 422);
        }

        $success = $return->reject($request->user(), $request->notes);

        if (! $success) {
            return response()->json(['message' => 'Не удалось отклонить возврат.'], 500);
        }

        return response()->json([
            'message' => 'Возврат отклонён.',
            'return' => [
                'id' => $return->id,
                'status' => $return->status,
                'processed_at' => $return->processed_at?->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    /**
     * Получить статистику возвратов
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'marketplace_account_id' => ['nullable', 'exists:marketplace_accounts,id'],
        ]);

        if (! $request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $query = OrderStockReturn::forCompany($request->company_id);

        if ($request->marketplace_account_id) {
            $query->forAccount($request->marketplace_account_id);
        }

        $stats = [
            'pending' => (clone $query)->where('status', 'pending')->count(),
            'processed' => (clone $query)->where('status', 'processed')->count(),
            'rejected' => (clone $query)->where('status', 'rejected')->count(),
            'returned_to_stock' => (clone $query)->where('action', 'return_to_stock')->count(),
            'written_off' => (clone $query)->where('action', 'write_off')->count(),
        ];

        return response()->json(['stats' => $stats]);
    }
}
