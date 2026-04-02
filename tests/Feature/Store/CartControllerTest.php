<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store\Store;
use App\Models\Store\StoreProduct;
use App\Models\Store\StorePromocode;
use App\Models\Store\StoreTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты для публичного API корзины витрины (CartController)
 */
class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private StoreProduct $storeProduct;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();

        $this->store = Store::create([
            'company_id' => $company->id,
            'name' => 'Тестовый магазин',
            'slug' => 'cart-test-store',
            'is_active' => true,
            'is_published' => true,
        ]);

        // Тема создаётся автоматически в Store::boot, но проверим
        if (! $this->store->theme) {
            StoreTheme::create([
                'store_id' => $this->store->id,
                'template' => 'default',
            ]);
        }

        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Тестовый товар',
            'article' => 'TEST-001',
        ]);

        $this->storeProduct = StoreProduct::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'is_visible' => true,
            'custom_price' => 25000,
        ]);
    }

    /**
     * Пустая корзина возвращает пустой массив
     */
    public function test_show_cart_returns_empty_cart(): void
    {
        $response = $this->getJson("/store/cart-test-store/api/cart");

        $response->assertOk();
        $response->assertJsonPath('data.items', []);
        $response->assertJsonPath('data.count', 0);
    }

    /**
     * Добавление товара в корзину
     */
    public function test_add_product_to_cart(): void
    {
        $response = $this->postJson("/store/cart-test-store/api/cart/add", [
            'product_id' => $this->storeProduct->id,
            'quantity' => 2,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.count', 2);
        $this->assertCount(1, $response->json('data.items'));
        $this->assertEquals(50000, $response->json('data.total'));
    }

    /**
     * Добавление несуществующего товара возвращает 404
     */
    public function test_add_nonexistent_product_returns_404(): void
    {
        $response = $this->postJson("/store/cart-test-store/api/cart/add", [
            'product_id' => 99999,
            'quantity' => 1,
        ]);

        $response->assertStatus(404);
    }

    /**
     * Обновление количества товара в корзине
     */
    public function test_update_cart_quantity(): void
    {
        $cartKey = (string) $this->storeProduct->id;
        $cart = [
            $cartKey => [
                'product_id' => $this->storeProduct->id,
                'variant_id' => null,
                'quantity' => 1,
                'name' => 'Тестовый товар',
                'price' => 25000,
                'image' => null,
            ],
        ];

        $response = $this->withSession(["store_cart_{$this->store->id}" => $cart])
            ->putJson("/store/cart-test-store/api/cart/update", [
                'product_id' => $this->storeProduct->id,
                'quantity' => 5,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.count', 5);
        $this->assertEquals(125000, $response->json('data.total'));
    }

    /**
     * Удаление товара из корзины
     */
    public function test_remove_item_from_cart(): void
    {
        $cartKey = (string) $this->storeProduct->id;
        $cart = [
            $cartKey => [
                'product_id' => $this->storeProduct->id,
                'variant_id' => null,
                'quantity' => 3,
                'name' => 'Тестовый товар',
                'price' => 25000,
                'image' => null,
            ],
        ];

        $response = $this->withSession(["store_cart_{$this->store->id}" => $cart])
            ->deleteJson("/store/cart-test-store/api/cart/remove", [
                'product_id' => $this->storeProduct->id,
            ]);

        $response->assertOk();
        $response->assertJsonPath('data.count', 0);
        $response->assertJsonPath('data.items', []);
    }

    /**
     * Очистка корзины
     */
    public function test_clear_cart(): void
    {
        $cart = [
            '1' => [
                'product_id' => $this->storeProduct->id,
                'variant_id' => null,
                'quantity' => 2,
                'name' => 'Товар',
                'price' => 25000,
                'image' => null,
            ],
        ];

        $response = $this->withSession(["store_cart_{$this->store->id}" => $cart])
            ->deleteJson("/store/cart-test-store/api/cart/clear");

        $response->assertOk();
        $response->assertJsonPath('data.count', 0);
        $response->assertJsonPath('data.items', []);
    }

    /**
     * Применение валидного промокода
     */
    public function test_apply_valid_promocode(): void
    {
        StorePromocode::create([
            'store_id' => $this->store->id,
            'code' => 'SALE10',
            'type' => 'percent',
            'value' => 10,
            'is_active' => true,
        ]);

        $cart = [
            (string) $this->storeProduct->id => [
                'product_id' => $this->storeProduct->id,
                'variant_id' => null,
                'quantity' => 4,
                'name' => 'Тестовый товар',
                'price' => 25000,
                'image' => null,
            ],
        ];

        $response = $this->withSession(["store_cart_{$this->store->id}" => $cart])
            ->postJson("/store/cart-test-store/api/cart/promocode", [
                'code' => 'SALE10',
            ]);

        $response->assertOk();
        // 100000 * 10% = 10000 скидка
        $this->assertEquals(10000, $response->json('data.discount'));
        $this->assertEquals(90000, $response->json('data.total'));
    }

    /**
     * Невалидный промокод возвращает 422
     */
    public function test_apply_invalid_promocode_returns_422(): void
    {
        $cart = [
            (string) $this->storeProduct->id => [
                'product_id' => $this->storeProduct->id,
                'variant_id' => null,
                'quantity' => 1,
                'name' => 'Товар',
                'price' => 25000,
                'image' => null,
            ],
        ];

        $response = $this->withSession(["store_cart_{$this->store->id}" => $cart])
            ->postJson("/store/cart-test-store/api/cart/promocode", [
                'code' => 'NONEXISTENT',
            ]);

        $response->assertStatus(422);
    }

    /**
     * Несуществующий магазин возвращает 404
     */
    public function test_cart_on_nonexistent_store_returns_404(): void
    {
        $response = $this->getJson("/store/nonexistent-slug-xyz/api/cart");

        $response->assertStatus(404);
    }
}
