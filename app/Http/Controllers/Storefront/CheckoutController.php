<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\StoreAnalytics;
use App\Models\Store\StoreOrder;
use App\Models\Store\StoreOrderItem;
use App\Models\Store\StorePromocode;
use App\Support\ApiResponder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Оформление заказа — страница чекаута и создание заказа
 */
final class CheckoutController extends Controller
{
    use ApiResponder;

    /**
     * Страница оформления заказа
     *
     * GET /store/{slug}/checkout
     */
    public function index(string $slug): View
    {
        $store = $this->getPublishedStore($slug, [
            'activeDeliveryMethods',
            'activePaymentMethods',
        ]);

        $cart = $this->getCart($store);
        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.checkout", compact('store', 'cart'));
    }

    /**
     * Создать заказ
     *
     * POST /store/{slug}/api/checkout
     */
    public function store(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug, [
            'activeDeliveryMethods',
            'activePaymentMethods',
        ]);

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['required', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_method_id' => ['required', 'integer'],
            'payment_method_id' => ['required', 'integer'],
            'delivery_address' => ['nullable', 'string', 'max:500'],
            'delivery_city' => ['nullable', 'string', 'max:255'],
            'delivery_comment' => ['nullable', 'string', 'max:1000'],
            'customer_note' => ['nullable', 'string', 'max:1000'],
            'promocode' => ['nullable', 'string', 'max:50'],
        ]);

        // Проверяем что методы доставки и оплаты принадлежат этому магазину
        $deliveryMethod = $store->activeDeliveryMethods
            ->where('id', $data['delivery_method_id'])
            ->first();

        if (! $deliveryMethod) {
            return $this->errorResponse(
                'Указанный способ доставки недоступен',
                'invalid_delivery_method',
                'delivery_method_id',
                422
            );
        }

        $paymentMethod = $store->activePaymentMethods
            ->where('id', $data['payment_method_id'])
            ->first();

        if (! $paymentMethod) {
            return $this->errorResponse(
                'Указанный способ оплаты недоступен',
                'invalid_payment_method',
                'payment_method_id',
                422
            );
        }

        // Получаем корзину
        $cart = $this->getCart($store);

        if (empty($cart)) {
            return $this->errorResponse(
                'Корзина пуста',
                'empty_cart',
                status: 422
            );
        }

        // Рассчитываем подытог
        $subtotal = 0.0;
        foreach ($cart as $item) {
            $subtotal += (float) $item['price'] * (int) $item['quantity'];
        }
        $subtotal = round($subtotal, 2);

        // Применяем промокод
        $discount = 0.0;
        $promocode = null;

        if (! empty($data['promocode'])) {
            $promocode = StorePromocode::where('store_id', $store->id)
                ->where('code', $data['promocode'])
                ->first();

            if (! $promocode || ! $promocode->isValid()) {
                return $this->errorResponse(
                    'Промокод недействителен или истёк',
                    'invalid_promocode',
                    'promocode',
                    422
                );
            }

            $discount = $promocode->calculateDiscount($subtotal);
        }

        // Рассчитываем стоимость доставки
        $deliveryPrice = $deliveryMethod->isFreeFor($subtotal - $discount)
            ? 0.0
            : (float) $deliveryMethod->price;

        // Итоговая сумма
        $total = round($subtotal - $discount + $deliveryPrice, 2);

        // Проверяем минимальную сумму заказа
        if ($store->min_order_amount && $total < (float) $store->min_order_amount) {
            return $this->errorResponse(
                "Минимальная сумма заказа: {$store->min_order_amount} {$store->currency}",
                'min_order_amount',
                status: 422
            );
        }

        // Создаём заказ в транзакции
        $order = DB::transaction(function () use ($store, $data, $cart, $subtotal, $discount, $deliveryPrice, $total, $promocode) {
            /** @var StoreOrder $order */
            $order = StoreOrder::create([
                'store_id' => $store->id,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'delivery_method_id' => $data['delivery_method_id'],
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_city' => $data['delivery_city'] ?? null,
                'delivery_comment' => $data['delivery_comment'] ?? null,
                'delivery_price' => $deliveryPrice,
                'payment_method_id' => $data['payment_method_id'],
                'payment_status' => StoreOrder::PAYMENT_PENDING,
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total' => $total,
                'status' => StoreOrder::STATUS_NEW,
                'customer_note' => $data['customer_note'] ?? null,
            ]);

            // Создаём позиции заказа
            foreach ($cart as $item) {
                StoreOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'name' => $item['name'],
                    'price' => (float) $item['price'],
                    'quantity' => (int) $item['quantity'],
                    'total' => round((float) $item['price'] * (int) $item['quantity'], 2),
                ]);
            }

            // Увеличиваем счётчик использования промокода
            if ($promocode) {
                $promocode->increment('usage_count');
            }

            return $order;
        });

        // Трекинг аналитики — fire-and-forget
        $this->trackOrderCompleted($store, $total);

        // Сохраняем ID заказа в сессию для страниц оплаты
        session()->put("store_{$store->id}_last_order_id", $order->id);

        // Очищаем корзину
        session()->forget("store_cart_{$store->id}");

        // Интеграция с SellerMind: Sale + остатки + уведомления
        // Вызов ВНЕ DB::transaction — ошибка НЕ откатывает заказ покупателя
        try {
            $storeOrderService = app(\App\Services\Store\StoreOrderService::class);
            $storeOrderService->syncOrderToSale($order);
            $storeOrderService->notifyStoreOwner($order);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('StoreOrder integration failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $this->successResponse(
            $order->load('items'),
            ['message' => 'Заказ успешно создан']
        );
    }

    /**
     * Страница статуса заказа
     *
     * GET /store/{slug}/order/{orderNumber}
     */
    public function orderStatus(string $slug, string $orderNumber): View
    {
        $store = $this->getPublishedStore($slug);

        $order = StoreOrder::where('store_id', $store->id)
            ->where('order_number', $orderNumber)
            ->with(['items', 'deliveryMethod', 'paymentMethod'])
            ->firstOrFail();

        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.order", compact('store', 'order'));
    }

    // ==================
    // Вспомогательные методы
    // ==================

    /**
     * Получить опубликованный магазин по slug
     *
     * @param  array<int, string>  $with
     */
    private function getPublishedStore(string $slug, array $with = []): Store
    {
        return Store::where('slug', $slug)
            ->where('is_active', true)
            ->where('is_published', true)
            ->with(array_merge(['theme'], $with))
            ->firstOrFail();
    }

    /**
     * Получить корзину из сессии
     *
     * @return array<string, array{product_id: int, quantity: int, name: string, price: float, image: string|null}>
     */
    private function getCart(Store $store): array
    {
        return session()->get("store_cart_{$store->id}", []);
    }

    /**
     * Трекинг завершённого заказа — fire-and-forget
     */
    private function trackOrderCompleted(Store $store, float $total): void
    {
        try {
            $today = now()->toDateString();

            StoreAnalytics::updateOrCreate(
                ['store_id' => $store->id, 'date' => $today],
                []
            );

            StoreAnalytics::where('store_id', $store->id)
                ->where('date', $today)
                ->increment('orders_completed');

            StoreAnalytics::where('store_id', $store->id)
                ->where('date', $today)
                ->increment('revenue', $total);
        } catch (\Throwable) {
            // Не прерываем пользовательский флоу при ошибке аналитики
        }
    }
}
