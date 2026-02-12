<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\StoreOrder;
use App\Support\ApiResponder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Оплата заказа — заглушка для будущей интеграции Click/Payme (Phase 5)
 */
final class PaymentController extends Controller
{
    use ApiResponder;

    /**
     * Инициировать оплату заказа
     *
     * POST /store/{slug}/api/payment/{orderId}/initiate
     *
     * Заглушка — реальная интеграция Click/Payme будет реализована в Phase 5
     */
    public function initiate(string $slug, int $orderId): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $order = StoreOrder::where('store_id', $store->id)
            ->where('id', $orderId)
            ->firstOrFail();

        if ($order->payment_status === StoreOrder::PAYMENT_PAID) {
            return $this->errorResponse(
                'Заказ уже оплачен',
                'already_paid',
                status: 422
            );
        }

        // Заглушка — реальный URL оплаты будет генерироваться в Phase 5
        return $this->successResponse([
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'payment_url' => null,
            'message' => 'Интеграция с платёжной системой будет доступна в ближайшее время',
        ]);
    }

    /**
     * Страница успешной оплаты
     *
     * GET /store/{slug}/payment/success
     */
    public function success(string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $template = $store->theme->template ?? 'default';

        return view("storefront.themes.{$template}.payment-success", compact('store'));
    }

    /**
     * Страница неудачной оплаты
     *
     * GET /store/{slug}/payment/fail
     */
    public function fail(string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $template = $store->theme->template ?? 'default';

        return view("storefront.themes.{$template}.payment-fail", compact('store'));
    }

    /**
     * Получить опубликованный магазин по slug
     */
    private function getPublishedStore(string $slug): Store
    {
        return Store::where('slug', $slug)
            ->where('is_active', true)
            ->where('is_published', true)
            ->with('theme')
            ->firstOrFail();
    }
}
