<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Store\Store;
use App\Models\Store\StoreAnalytics;
use App\Models\Store\StoreProduct;
use App\Support\ApiResponder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Корзина покупателя (сессионная) — Blade-страница + API-эндпоинты
 */
final class CartController extends Controller
{
    use ApiResponder;

    /**
     * Страница корзины
     *
     * GET /store/{slug}/cart
     */
    public function index(string $slug): View
    {
        $store = $this->getPublishedStore($slug);
        $cart = $this->getCart($store);
        $template = $store->theme->template ?? 'default';

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
            'quantity' => ['sometimes', 'integer', 'min:1'],
        ]);

        $quantity = (int) ($data['quantity'] ?? 1);

        // Проверяем что товар существует и видим в этом магазине
        $storeProduct = StoreProduct::where('store_id', $store->id)
            ->where('id', $data['product_id'])
            ->where('is_visible', true)
            ->with('product.mainImage')
            ->first();

        if (! $storeProduct) {
            return $this->errorResponse(
                'Товар не найден или недоступен',
                'product_not_found',
                'product_id',
                404
            );
        }

        $cart = $this->getCart($store);
        $productKey = (string) $storeProduct->id;

        if (isset($cart[$productKey])) {
            $cart[$productKey]['quantity'] += $quantity;
        } else {
            $cart[$productKey] = [
                'product_id' => $storeProduct->id,
                'quantity' => $quantity,
                'name' => $storeProduct->getDisplayName(),
                'price' => $storeProduct->getDisplayPrice(),
                'image' => $storeProduct->product->mainImage?->url ?? null,
            ];
        }

        $this->saveCart($store, $cart);

        // Трекинг добавления в корзину — fire-and-forget
        $this->trackCartAddition($store);

        return $this->successResponse([
            'items' => array_values($cart),
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

    // ==================
    // Вспомогательные методы
    // ==================

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

    /**
     * Трекинг добавления в корзину — fire-and-forget
     */
    private function trackCartAddition(Store $store): void
    {
        try {
            $today = now()->toDateString();

            StoreAnalytics::updateOrCreate(
                ['store_id' => $store->id, 'date' => $today],
                []
            );

            StoreAnalytics::where('store_id', $store->id)
                ->where('date', $today)
                ->increment('cart_additions');
        } catch (\Throwable) {
            // Не прерываем пользовательский флоу при ошибке аналитики
        }
    }
}
