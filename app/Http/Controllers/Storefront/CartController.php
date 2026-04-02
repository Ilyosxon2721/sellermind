<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Traits\StorefrontHelpers;
use App\Models\Store\Store;
use App\Models\Store\StoreProduct;
use App\Models\Store\StorePromocode;
use App\Support\ApiResponder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Корзина покупателя (сессионная) — Blade-страница + API-эндпоинты
 */
final class CartController extends Controller
{
    use ApiResponder, StorefrontHelpers;

    /**
     * Страница корзины
     *
     * GET /store/{slug}/cart
     */
    public function index(string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $cart = $this->getCart($store);
        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.cart", compact('store', 'cart'));
    }

    /**
     * Получить содержимое корзины (JSON)
     *
     * GET /store/{slug}/api/cart
     */
    public function show(string $slug): JsonResponse
    {
        $store = $this->getPublishedStore($slug);
        $cart = $this->getCart($store);

        return $this->successResponse([
            'items' => array_values($cart),
            'subtotal' => $this->calculateTotal($cart),
            'discount' => 0,
            'total' => $this->calculateTotal($cart),
            'count' => $this->calculateCount($cart),
        ]);
    }

    /**
     * Добавить товар в корзину
     *
     * POST /store/{slug}/api/cart/add
     */
    public function add(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity'   => ['sometimes', 'integer', 'min:1'],
            'variant_id' => ['sometimes', 'nullable', 'integer'],
        ]);

        $quantity  = (int) ($data['quantity'] ?? 1);
        $variantId = isset($data['variant_id']) ? (int) $data['variant_id'] : null;

        // Проверяем что товар существует и видим в этом магазине
        $storeProduct = StoreProduct::where('store_id', $store->id)
            ->where('id', $data['product_id'])
            ->where('is_visible', true)
            ->with(['product.mainImage', 'product.variants'])
            ->first();

        if (! $storeProduct) {
            return $this->errorResponse(
                'Товар не найден или недоступен',
                'product_not_found',
                'product_id',
                404
            );
        }

        // Определяем вариант (явно переданный или первый активный)
        $variant = null;
        if ($variantId) {
            $variant = $storeProduct->product->variants
                ->where('id', $variantId)
                ->where('is_active', true)
                ->where('is_deleted', false)
                ->first();

            if (! $variant) {
                return $this->errorResponse(
                    'Вариант товара не найден',
                    'variant_not_found',
                    'variant_id',
                    404
                );
            }
        } elseif ($storeProduct->product->variants->isNotEmpty()) {
            // Если вариантов несколько, но variant_id не передан — берём первый активный
            $variant = $storeProduct->product->variants
                ->where('is_active', true)
                ->where('is_deleted', false)
                ->first();
        }

        // Проверка наличия выбранного варианта
        if ($variant && $variant->stock_default <= 0) {
            return $this->errorResponse(
                'Товар закончился на складе',
                'out_of_stock',
                'variant_id',
                422
            );
        }

        // Цена: если передан variant_id — берём цену варианта; иначе кастомную или из первого варианта
        $price = $variant
            ? (float) ($variant->price_default ?? $storeProduct->getDisplayPrice())
            : $storeProduct->getDisplayPrice();

        // Ключ корзины — учитываем вариант, чтобы разные варианты хранились отдельно
        $cartKey = $variant
            ? "{$storeProduct->id}_v{$variant->id}"
            : (string) $storeProduct->id;

        $cart = $this->getCart($store);

        if (isset($cart[$cartKey])) {
            $cart[$cartKey]['quantity'] += $quantity;
        } else {
            $variantLabel = $variant?->option_values_summary ?? $variant?->sku ?? null;
            $cart[$cartKey] = [
                'product_id' => $storeProduct->id,
                'variant_id' => $variant?->id,
                'quantity'   => $quantity,
                'name'       => $storeProduct->getDisplayName() . ($variantLabel ? " ({$variantLabel})" : ''),
                'price'      => $price,
                'image'      => $storeProduct->product->mainImage?->url ?? null,
            ];
        }

        $this->saveCart($store, $cart);

        // Трекинг добавления в корзину — fire-and-forget
        $this->trackCartAddition($store);

        return $this->successResponse([
            'items' => array_values($cart),
            'subtotal' => $this->calculateTotal($cart),
            'discount' => 0,
            'total' => $this->calculateTotal($cart),
            'count' => $this->calculateCount($cart),
        ]);
    }

    /**
     * Обновить количество товара в корзине
     *
     * PUT /store/{slug}/api/cart/update
     */
    public function update(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $cart = $this->getCart($store);
        $productKey = (string) $data['product_id'];

        if (! isset($cart[$productKey])) {
            return $this->errorResponse(
                'Товар не найден в корзине',
                'item_not_found',
                'product_id',
                404
            );
        }

        $cart[$productKey]['quantity'] = (int) $data['quantity'];

        $this->saveCart($store, $cart);

        return $this->successResponse([
            'items' => array_values($cart),
            'subtotal' => $this->calculateTotal($cart),
            'discount' => 0,
            'total' => $this->calculateTotal($cart),
            'count' => $this->calculateCount($cart),
        ]);
    }

    /**
     * Удалить товар из корзины
     *
     * DELETE /store/{slug}/api/cart/remove
     */
    public function remove(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $data = $request->validate([
            'product_id' => ['required', 'integer'],
        ]);

        $cart = $this->getCart($store);
        $productKey = (string) $data['product_id'];

        if (! isset($cart[$productKey])) {
            return $this->errorResponse(
                'Товар не найден в корзине',
                'item_not_found',
                'product_id',
                404
            );
        }

        unset($cart[$productKey]);

        $this->saveCart($store, $cart);

        return $this->successResponse([
            'items' => array_values($cart),
            'subtotal' => $this->calculateTotal($cart),
            'discount' => 0,
            'total' => $this->calculateTotal($cart),
            'count' => $this->calculateCount($cart),
        ]);
    }

    /**
     * Очистить корзину
     *
     * DELETE /store/{slug}/api/cart/clear
     */
    public function clear(string $slug): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $this->saveCart($store, []);

        return $this->successResponse([
            'items' => [],
            'total' => 0,
            'count' => 0,
        ]);
    }

    /**
     * Применить промокод к корзине
     *
     * POST /store/{slug}/api/cart/promocode
     */
    public function applyPromocode(string $slug, Request $request): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $promocode = StorePromocode::where('store_id', $store->id)
            ->where('code', $data['code'])
            ->first();

        if (! $promocode || ! $promocode->isValid()) {
            return $this->errorResponse(
                'Промокод недействителен или истёк',
                'invalid_promocode',
                'code',
                422
            );
        }

        $cart = $this->getCart($store);
        $subtotal = $this->calculateTotal($cart);

        $discount = $promocode->calculateDiscount($subtotal);

        if ($discount <= 0) {
            return $this->errorResponse(
                "Минимальная сумма заказа для этого промокода: {$promocode->min_order_amount}",
                'min_order_amount',
                'code',
                422
            );
        }

        // Сохраняем промокод в сессию
        session()->put("store_promocode_{$store->id}", [
            'code' => $promocode->code,
            'id' => $promocode->id,
            'type' => $promocode->type,
            'value' => (float) $promocode->value,
        ]);

        return $this->successResponse([
            'items' => array_values($cart),
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2),
            'count' => $this->calculateCount($cart),
            'promocode' => [
                'code' => $promocode->code,
                'discount' => $discount,
                'type' => $promocode->type,
                'value' => (float) $promocode->value,
            ],
        ]);
    }

    /**
     * Убрать промокод из корзины
     *
     * DELETE /store/{slug}/api/cart/promocode
     */
    public function removePromocode(string $slug): JsonResponse
    {
        $store = $this->getPublishedStore($slug);

        session()->forget("store_promocode_{$store->id}");

        $cart = $this->getCart($store);

        return $this->successResponse([
            'items' => array_values($cart),
            'subtotal' => $this->calculateTotal($cart),
            'discount' => 0,
            'total' => $this->calculateTotal($cart),
            'count' => $this->calculateCount($cart),
            'promocode' => null,
        ]);
    }

    // ==================
    // Вспомогательные методы
    // ==================

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
     * Сохранить корзину в сессию
     *
     * @param  array<string, array{product_id: int, quantity: int, name: string, price: float, image: string|null}>  $cart
     */
    private function saveCart(Store $store, array $cart): void
    {
        session()->put("store_cart_{$store->id}", $cart);
    }

    /**
     * Подсчитать итоговую сумму корзины
     */
    private function calculateTotal(array $cart): float
    {
        $total = 0.0;

        foreach ($cart as $item) {
            $total += (float) $item['price'] * (int) $item['quantity'];
        }

        return round($total, 2);
    }

    /**
     * Подсчитать общее количество товаров в корзине
     */
    private function calculateCount(array $cart): int
    {
        $count = 0;

        foreach ($cart as $item) {
            $count += (int) $item['quantity'];
        }

        return $count;
    }

}
