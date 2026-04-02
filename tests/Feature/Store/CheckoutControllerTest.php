<?php

declare(strict_types=1);

namespace Tests\Feature\Store;

use App\Models\Company;
use App\Models\Product;
use App\Models\Store\Store;
use App\Models\Store\StoreDeliveryMethod;
use App\Models\Store\StoreOrder;
use App\Models\Store\StorePaymentMethod;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreTheme;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Тесты для публичного чекаута витрины (CheckoutController)
 */
class CheckoutControllerTest extends TestCase
{
    use RefreshDatabase;

    private Store $store;

    private StoreProduct $storeProduct;

    private StoreDeliveryMethod $deliveryMethod;

    private StorePaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        $company = Company::factory()->create();

        $this->store = Store::create([
            'company_id' => $company->id,
            'name' => 'Тестовый магазин',
            'slug' => 'checkout-test-store',
            'is_active' => true,
            'is_published' => true,
            'currency' => 'UZS',
        ]);

        if (! $this->store->theme) {
            StoreTheme::create([
                'store_id' => $this->store->id,
                'template' => 'default',
            ]);
        }

        $product = Product::create([
            'company_id' => $company->id,
            'name' => 'Тестовый товар',
            'article' => 'CHKOUT-001',
        ]);

        $this->storeProduct = StoreProduct::create([
            'store_id' => $this->store->id,
            'product_id' => $product->id,
            'is_visible' => true,
            'custom_price' => 50000,
        ]);

        $this->deliveryMethod = StoreDeliveryMethod::create([
            'store_id' => $this->store->id,
            'name' => 'Курьер',
            'type' => 'courier',
            'price' => 15000,
            'is_active' => true,
        ]);

        $this->paymentMethod = StorePaymentMethod::create([
            'store_id' => $this->store->id,
            'type' => 'cash',
            'name' => 'Наличные',
            'is_active' => true,
        ]);
    }

    /**
     * Вспомогательный метод — корзина с одним товаром
     */
    private function cartWithProduct(): array
    {
        return [
            (string) $this->storeProduct->id => [
                'product_id' => $this->storeProduct->id,
                'variant_id' => null,
                'quantity' => 2,
                'name' => 'Тестовый товар',
                'price' => 50000,
                'image' => null,
            ],
        ];
    }

    /**
     * Страница чекаута загружается
     */
    public function test_checkout_page_loads(): void
    {
        $response = $this->get("/store/checkout-test-store/checkout");

        $response->assertOk();
    }

    /**
     * Создание заказа с валидными данными
     */
    public function test_create_order_with_valid_data(): void
    {
        $response = $this->withSession(["store_cart_{$this->store->id}" => $this->cartWithProduct()])
            ->postJson("/store/checkout-test-store/api/checkout", [
                'customer_name' => 'Алишер Каримов',
                'customer_phone' => '+998901234567',
                'delivery_method_id' => $this->deliveryMethod->id,
                'payment_method_id' => $this->paymentMethod->id,
                'delivery_address' => 'ул. Навои, 15',
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'order_number', 'total', 'items']]);

        // Проверяем что заказ создан в БД
        $this->assertDatabaseHas('store_orders', [
            'store_id' => $this->store->id,
            'customer_name' => 'Алишер Каримов',
            'customer_phone' => '+998901234567',
            'status' => 'new',
        ]);

        // Проверяем позиции заказа
        $orderId = $response->json('data.id');
        $this->assertDatabaseHas('store_order_items', [
            'order_id' => $orderId,
            'name' => 'Тестовый товар',
            'quantity' => 2,
        ]);
    }

    /**
     * Заказ с пустой корзиной возвращает 422
     */
    public function test_create_order_with_empty_cart_returns_422(): void
    {
        $response = $this->postJson("/store/checkout-test-store/api/checkout", [
            'customer_name' => 'Тест',
            'customer_phone' => '+998900000000',
            'delivery_method_id' => $this->deliveryMethod->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response->assertStatus(422);
    }

    /**
     * Валидация обязательных полей
     */
    public function test_create_order_validates_required_fields(): void
    {
        $response = $this->withSession(["store_cart_{$this->store->id}" => $this->cartWithProduct()])
            ->postJson("/store/checkout-test-store/api/checkout", [
                // customer_name отсутствует
                'customer_phone' => '+998900000000',
                'delivery_method_id' => $this->deliveryMethod->id,
                'payment_method_id' => $this->paymentMethod->id,
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['customer_name']);
    }

    /**
     * Невалидный метод доставки (не принадлежит магазину)
     */
    public function test_create_order_with_invalid_delivery_method_returns_422(): void
    {
        $response = $this->withSession(["store_cart_{$this->store->id}" => $this->cartWithProduct()])
            ->postJson("/store/checkout-test-store/api/checkout", [
                'customer_name' => 'Тест',
                'customer_phone' => '+998900000000',
                'delivery_method_id' => 99999,
                'payment_method_id' => $this->paymentMethod->id,
            ]);

        $response->assertStatus(422);
    }

    /**
     * Быстрый заказ (1 клик) создаёт заказ
     */
    public function test_quick_order_creates_order(): void
    {
        $response = $this->postJson("/store/checkout-test-store/api/quick-order", [
            'customer_name' => 'Быстрый покупатель',
            'customer_phone' => '+998901111111',
            'product_id' => $this->storeProduct->id,
            'quantity' => 1,
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['data' => ['id', 'order_number', 'total']]);

        $this->assertDatabaseHas('store_orders', [
            'store_id' => $this->store->id,
            'customer_name' => 'Быстрый покупатель',
        ]);
    }

    /**
     * Быстрый заказ с несуществующим товаром возвращает 404
     */
    public function test_quick_order_with_invalid_product_returns_404(): void
    {
        $response = $this->postJson("/store/checkout-test-store/api/quick-order", [
            'customer_name' => 'Тест',
            'customer_phone' => '+998900000000',
            'product_id' => 99999,
        ]);

        $response->assertStatus(404);
    }

    /**
     * Страница статуса заказа отображается
     */
    public function test_order_status_page_shows_order(): void
    {
        $order = StoreOrder::create([
            'store_id' => $this->store->id,
            'customer_name' => 'Тест',
            'customer_phone' => '+998900000000',
            'subtotal' => 50000,
            'discount' => 0,
            'total' => 50000,
            'status' => 'new',
            'payment_status' => 'pending',
        ]);

        $response = $this->get("/store/checkout-test-store/order/{$order->order_number}");

        $response->assertOk();
    }

    /**
     * Стоимость доставки добавляется к итоговой сумме
     */
    public function test_order_includes_delivery_price_in_total(): void
    {
        $response = $this->withSession(["store_cart_{$this->store->id}" => $this->cartWithProduct()])
            ->postJson("/store/checkout-test-store/api/checkout", [
                'customer_name' => 'Тест Доставка',
                'customer_phone' => '+998900000000',
                'delivery_method_id' => $this->deliveryMethod->id,
                'payment_method_id' => $this->paymentMethod->id,
            ]);

        $response->assertOk();

        // subtotal = 2 * 50000 = 100000, delivery = 15000, total = 115000
        $this->assertEquals(115000, $response->json('data.total'));
    }
}
