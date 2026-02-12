<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\Store\Store;
use App\Models\Store\StoreOrder;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление заказами магазина
 */
final class StoreOrderController extends Controller
{
    use ApiResponder, HasCompanyScope, HasPaginatedResponse;

    /**
     * Список заказов магазина (с пагинацией, фильтрацией и поиском)
     */
    public function index(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $perPage = $this->getPerPage($request);

        $query = StoreOrder::where('store_id', $store->id)
            ->with('items');

        // Фильтр по статусу заказа
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Фильтр по статусу оплаты
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->input('payment_status'));
        }

        // Поиск по номеру заказа или имени клиента
        if ($request->filled('search')) {
            $search = $this->escapeLike($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->latest()->paginate($perPage);

        return $this->successResponse($orders->items(), $this->paginationMeta($orders));
    }

    /**
     * Показать заказ со всеми связями
     */
    public function show(int $storeId, int $orderId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $order = StoreOrder::where('store_id', $store->id)
            ->with(['items', 'deliveryMethod', 'paymentMethod'])
            ->findOrFail($orderId);

        return $this->successResponse($order);
    }

    /**
     * Обновить заказ (статус, заметка администратора, статус оплаты)
     */
    public function update(int $storeId, int $orderId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $order = StoreOrder::where('store_id', $store->id)->findOrFail($orderId);

        $data = $request->validate([
            'status' => ['sometimes', 'string', 'in:new,confirmed,processing,shipped,delivered,cancelled'],
            'admin_note' => ['nullable', 'string', 'max:2000'],
            'payment_status' => ['sometimes', 'string', 'in:pending,paid,failed,refunded'],
        ]);

        $order->update($data);

        return $this->successResponse(
            $order->fresh(['items', 'deliveryMethod', 'paymentMethod'])
        );
    }

    /**
     * Статистика заказов магазина
     */
    public function stats(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $orders = StoreOrder::where('store_id', $store->id);

        // Общая статистика
        $totalOrders = (clone $orders)->count();
        $totalRevenue = (clone $orders)->where('status', '!=', StoreOrder::STATUS_CANCELLED)->sum('total');

        // Статистика по статусам
        $byStatus = (clone $orders)
            ->selectRaw('status, COUNT(*) as count, SUM(total) as revenue')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        return $this->successResponse([
            'total_orders' => $totalOrders,
            'total_revenue' => (float) $totalRevenue,
            'by_status' => $byStatus,
        ]);
    }

    /**
     * Найти магазин текущей компании
     */
    private function findStore(int $storeId): Store
    {
        $companyId = $this->getCompanyId();

        return Store::where('company_id', $companyId)->findOrFail($storeId);
    }
}
