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
use Illuminate\Support\Facades\Mail;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $statusChanged = isset($data['status']) && $data['status'] !== $order->status;

        $order->update($data);

        // При отмене заказа — отменяем Sale и возвращаем остатки
        if (isset($data['status']) && $data['status'] === 'cancelled' && $order->sellermind_order_id) {
            try {
                app(\App\Services\Store\StoreOrderService::class)->cancelOrderSale($order);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to cancel Sale for StoreOrder', [
                    'order_id' => $order->id,
                    'sale_id' => $order->sellermind_order_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Email-уведомление покупателю при смене статуса
        if ($statusChanged && $order->customer_email) {
            try {
                $order->load('items');
                Mail::to($order->customer_email)->queue(
                    new \App\Mail\StoreOrderStatusMail($order, $store->name)
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Failed to send order status email', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

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
     * Экспорт заказов магазина в CSV
     *
     * GET /api/store/stores/{storeId}/orders/export?status=...&from=...&to=...
     */
    public function export(int $storeId, Request $request): StreamedResponse
    {
        $store = $this->findStore($storeId);

        $request->validate([
            'status' => ['nullable', 'string', 'in:new,confirmed,processing,shipped,delivered,cancelled'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $query = StoreOrder::where('store_id', $store->id)
            ->with(['items', 'deliveryMethod', 'paymentMethod']);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->input('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->input('to'));
        }

        $orders = $query->latest()->get();

        $filename = "orders-{$store->slug}-" . now()->format('Y-m-d') . '.csv';

        return response()->streamDownload(function () use ($orders) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM для корректного отображения в Excel
            fwrite($handle, "\xEF\xBB\xBF");

            // Заголовки
            fputcsv($handle, [
                '№ Заказа',
                'Дата',
                'Клиент',
                'Телефон',
                'Email',
                'Статус',
                'Оплата',
                'Доставка',
                'Адрес доставки',
                'Подытог',
                'Скидка',
                'Доставка (стоимость)',
                'Итого',
                'Товары',
                'Заметка клиента',
                'Заметка администратора',
            ], ';');

            $statusLabels = [
                'new' => 'Новый',
                'confirmed' => 'Подтверждён',
                'processing' => 'В обработке',
                'shipped' => 'Отправлен',
                'delivered' => 'Доставлен',
                'cancelled' => 'Отменён',
            ];

            $paymentLabels = [
                'pending' => 'Ожидает',
                'paid' => 'Оплачен',
                'failed' => 'Ошибка',
                'refunded' => 'Возврат',
            ];

            foreach ($orders as $order) {
                $items = $order->items->map(function ($item) {
                    return "{$item->name} x{$item->quantity} = {$item->total}";
                })->implode(' | ');

                fputcsv($handle, [
                    $order->order_number,
                    $order->created_at?->format('d.m.Y H:i'),
                    $order->customer_name,
                    $order->customer_phone,
                    $order->customer_email ?? '',
                    $statusLabels[$order->status] ?? $order->status,
                    $paymentLabels[$order->payment_status] ?? $order->payment_status,
                    $order->deliveryMethod?->name ?? '',
                    $order->delivery_address ?? '',
                    $order->subtotal,
                    $order->discount,
                    $order->delivery_price,
                    $order->total,
                    $items,
                    $order->customer_note ?? '',
                    $order->admin_note ?? '',
                ], ';');
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
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
