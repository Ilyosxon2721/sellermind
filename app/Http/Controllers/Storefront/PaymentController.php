<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\StoreOrder;
use App\Support\ApiResponder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Оплата заказа — интеграция Click/Payme и оффлайн-методы
 */
final class PaymentController extends Controller
{
    use ApiResponder;

    /**
     * Инициировать оплату заказа
     *
     * POST /store/{slug}/api/payment/{orderId}/initiate
     *
     * Генерирует URL для оплаты через Click/Payme или возвращает инструкции
     * для оффлайн-методов (наличные, перевод, карта)
     */
    public function initiate(string $slug, int $orderId): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $order = StoreOrder::where('store_id', $store->id)
            ->where('id', $orderId)
            ->firstOrFail();

        if ($order->payment_status !== StoreOrder::PAYMENT_PENDING) {
            return $this->errorResponse(
                'Заказ уже оплачен или обработан',
                'invalid_payment_status',
                status: 422
            );
        }

        $order->load('paymentMethod');

        $paymentType = $order->paymentMethod?->type;
        $paymentUrl = null;
        $message = null;

        switch ($paymentType) {
            case 'click':
                $paymentUrl = $this->generateClickUrl($order, $slug);
                break;

            case 'payme':
                $paymentUrl = $this->generatePaymeUrl($order, $slug);
                break;

            case 'cash':
                $message = 'Оплата наличными при получении заказа';
                break;

            case 'transfer':
                $message = 'Оплата банковским переводом. Реквизиты будут отправлены на ваш контакт';
                break;

            case 'card':
                $message = 'Оплата картой при получении заказа';
                break;

            default:
                $message = 'Способ оплаты будет согласован с менеджером';
                break;
        }

        return $this->successResponse([
            'payment_url' => $paymentUrl,
            'method' => $paymentType,
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'total' => $order->total,
            'message' => $message,
        ]);
    }

    /**
     * Страница успешной оплаты
     *
     * GET /store/{slug}/payment/success
     */
    public function success(Request $request, string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $template = $store->theme?->resolvedTemplate() ?? 'default';

        $order = $this->resolveOrderFromRequest($request, $store);

        return view("storefront.themes.{$template}.payment-success", compact('store', 'order'));
    }

    /**
     * Страница неудачной оплаты
     *
     * GET /store/{slug}/payment/fail
     */
    public function fail(Request $request, string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $template = $store->theme?->resolvedTemplate() ?? 'default';

        $order = $this->resolveOrderFromRequest($request, $store);

        return view("storefront.themes.{$template}.payment-fail", compact('store', 'order'));
    }

    /**
     * Сгенерировать URL оплаты через Click
     */
    private function generateClickUrl(StoreOrder $order, string $slug): string
    {
        $settings = $order->paymentMethod->settings ?? [];
        $merchantId = $settings['merchant_id'] ?? config('payments.click.merchant_id');
        $serviceId = $settings['service_id'] ?? config('payments.click.service_id');

        $transactionId = 'ORDER-'.$order->id.'-'.time();
        $order->update(['payment_id' => $transactionId]);

        return 'https://my.click.uz/services/pay?'.http_build_query([
            'service_id' => $serviceId,
            'merchant_id' => $merchantId,
            'amount' => $order->total,
            'transaction_param' => $transactionId,
            'return_url' => url("/store/{$slug}/payment/success"),
        ]);
    }

    /**
     * Сгенерировать URL оплаты через Payme
     */
    private function generatePaymeUrl(StoreOrder $order, string $slug): string
    {
        $settings = $order->paymentMethod->settings ?? [];
        $merchantId = $settings['merchant_id'] ?? config('payments.payme.merchant_id');

        $transactionId = 'ORDER-'.$order->id.'-'.time();
        $order->update(['payment_id' => $transactionId]);

        $amount = (int) ($order->total * 100); // тийин
        $returnUrl = url("/store/{$slug}/payment/success");

        // Формат по документации Payme: https://checkout.paycom.uz/{base64(params)}
        // Параметры: m=merchant_id;ac.order_id=ID;a=amount;c=return_url
        $params = "m={$merchantId};ac.order_id={$order->id};a={$amount};c={$returnUrl}";

        return 'https://checkout.paycom.uz/'.base64_encode($params);
    }

    /**
     * Найти заказ по query-параметру order_id или из сессии
     */
    private function resolveOrderFromRequest(Request $request, Store $store): ?StoreOrder
    {
        // Попытка найти заказ по order_id из query string
        $orderId = $request->query('order_id');

        if ($orderId !== null) {
            $order = StoreOrder::where('store_id', $store->id)
                ->where('id', (int) $orderId)
                ->first();

            if ($order !== null) {
                return $order;
            }
        }

        // Попытка найти последний заказ магазина из сессии
        $sessionOrderId = $request->session()->get("store_{$store->id}_last_order_id");

        if ($sessionOrderId !== null) {
            return StoreOrder::where('store_id', $store->id)
                ->where('id', (int) $sessionOrderId)
                ->first();
        }

        return null;
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
