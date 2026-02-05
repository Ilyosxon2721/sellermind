<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Services\Marketplaces\OzonClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер для управления заказами OZON
 */
class OzonOrderController extends Controller
{
    use HasPaginatedResponse;

    public function __construct(
        protected OzonClient $ozonClient
    ) {}

    /**
     * Получить список заказов с фильтрацией
     * GET /api/marketplace/ozon/accounts/{account}/orders
     */
    public function index(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorize($account);

        $query = OzonOrder::where('marketplace_account_id', $account->id);

        // Фильтр по статусу
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Поиск
        if ($search = $request->input('search')) {
            $search = $this->escapeLike($search);
            $query->where(function ($q) use ($search) {
                $q->where('posting_number', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%");
            });
        }

        // Группировка по статусам для счетчиков
        $statusCounts = OzonOrder::where('marketplace_account_id', $account->id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $perPage = $this->getPerPage($request);

        $orders = $query->orderBy('created_at_ozon', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'orders' => $orders->items(),
            'meta' => $this->paginationMeta($orders),
            'status_counts' => $statusCounts,
        ]);
    }

    /**
     * Получить детальную информацию о заказе
     * GET /api/marketplace/ozon/accounts/{account}/orders/{order}
     */
    public function show(Request $request, MarketplaceAccount $account, OzonOrder $order): JsonResponse
    {
        $this->authorize($account);

        if ($order->marketplace_account_id !== $account->id) {
            return response()->json(['message' => 'Заказ не найден'], 404);
        }

        // Загрузить свежие данные из OZON API
        try {
            $details = $this->ozonClient->getPostingDetails($account, $order->posting_number);

            // Обновить заказ свежими данными
            $order->update([
                'order_data' => $details,
                'products' => $details['products'] ?? $order->products,
            ]);
        } catch (\Exception $e) {
            // Если не удалось обновить - вернем то что есть
            \Log::warning('Failed to fetch order details from OZON', [
                'posting_number' => $order->posting_number,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'order' => $order,
        ]);
    }

    /**
     * Отменить заказ
     * POST /api/marketplace/ozon/accounts/{account}/orders/{order}/cancel
     */
    public function cancel(Request $request, MarketplaceAccount $account, OzonOrder $order): JsonResponse
    {
        $this->authorize($account);

        $request->validate([
            'cancel_reason_id' => 'required|integer',
            'cancel_reason_message' => 'nullable|string|max:500',
        ]);

        if (! $order->canBeCancelled()) {
            return response()->json([
                'success' => false,
                'message' => 'Заказ в статусе "'.$order->getStatusLabel().'" не может быть отменен',
            ], 400);
        }

        try {
            $result = $this->ozonClient->cancelPosting(
                $account,
                $order->posting_number,
                $request->cancel_reason_id,
                $request->cancel_reason_message ?? ''
            );

            $order->update([
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancel_reason_message,
                'cancelled_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно отменен',
                'order' => $order->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отмене заказа: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить список причин отмены
     * GET /api/marketplace/ozon/accounts/{account}/cancel-reasons
     */
    public function getCancelReasons(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorize($account);

        try {
            $reasons = $this->ozonClient->getCancelReasons($account);

            return response()->json([
                'success' => true,
                'reasons' => $reasons,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения причин отмены: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Подтвердить отгрузку (передать к отгрузке)
     * POST /api/marketplace/ozon/accounts/{account}/orders/{order}/ship
     */
    public function ship(Request $request, MarketplaceAccount $account, OzonOrder $order): JsonResponse
    {
        $this->authorize($account);

        if (! $order->canBeShipped()) {
            return response()->json([
                'success' => false,
                'message' => 'Заказ не может быть отправлен в текущем статусе',
            ], 400);
        }

        try {
            // Сначала передаем к отгрузке
            $this->ozonClient->markAsAwaitingDelivery($account, $order->posting_number);

            // Затем собираем
            $result = $this->ozonClient->shipPosting($account, [
                ['posting_number' => $order->posting_number],
            ]);

            $order->update([
                'status' => 'awaiting_deliver',
                'shipment_date' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Заказ передан к отгрузке',
                'order' => $order->fresh(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при отправке заказа: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Получить этикетку для печати (PDF)
     * POST /api/marketplace/ozon/accounts/{account}/orders/{order}/label
     */
    public function getLabel(Request $request, MarketplaceAccount $account, OzonOrder $order): JsonResponse
    {
        $this->authorize($account);

        try {
            $pdfBase64 = $this->ozonClient->getPackageLabel($account, [$order->posting_number]);

            if (empty($pdfBase64)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Не удалось получить этикетку',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'label' => $pdfBase64,
                'filename' => "label_{$order->posting_number}.pdf",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении этикетки: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Массовая печать этикеток
     * POST /api/marketplace/ozon/accounts/{account}/orders/labels
     */
    public function getLabels(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorize($account);

        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:ozon_orders,id',
        ]);

        $orders = OzonOrder::whereIn('id', $request->order_ids)
            ->where('marketplace_account_id', $account->id)
            ->get();

        if ($orders->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Заказы не найдены',
            ], 404);
        }

        $postingNumbers = $orders->pluck('posting_number')->toArray();

        try {
            $pdfBase64 = $this->ozonClient->getPackageLabel($account, $postingNumbers);

            return response()->json([
                'success' => true,
                'label' => $pdfBase64,
                'filename' => 'labels_'.date('Ymd_His').'.pdf',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при получении этикеток: '.$e->getMessage(),
            ], 500);
        }
    }

    protected function authorize(MarketplaceAccount $account): void
    {
        if (! auth()->user()->hasCompanyAccess($account->company_id)) {
            abort(403, 'Доступ запрещён');
        }
    }
}
