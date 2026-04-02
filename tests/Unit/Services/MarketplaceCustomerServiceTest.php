<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceCustomer;
use App\Models\MarketplaceCustomerOrder;
use App\Models\OzonOrder;
use App\Models\UzumOrder;
use App\Models\WbOrder;
use App\Services\Marketplaces\MarketplaceCustomerService;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Тесты для MarketplaceCustomerService.
 *
 * Тестируем приватные методы через reflection (без БД)
 * и логику дедупликации/фильтрации заказов.
 */
class MarketplaceCustomerServiceTest extends TestCase
{
    protected MarketplaceCustomerService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MarketplaceCustomerService;
    }

    // ========== Phone normalization ==========

    #[DataProvider('phoneNormalizationProvider')]
    public function test_normalize_phone(string $input, ?string $expected): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'normalizePhone');

        $result = $method->invoke($this->service, $input);

        $this->assertEquals($expected, $result);
    }

    public static function phoneNormalizationProvider(): array
    {
        return [
            'uz format' => ['+998901234567', '+998901234567'],
            'ru format' => ['+79161234567', '+79161234567'],
            'with spaces' => ['8 916 123 45 67', '+89161234567'],
            'with dashes' => ['+998-90-123-45-67', '+998901234567'],
            'with parentheses' => ['8 (916) 123-45-67', '+89161234567'],
            'short number' => ['12345', null],
            'empty' => ['', null],
            'digits only 11' => ['79161234567', '+79161234567'],
            'digits only 9' => ['901234567', '901234567'],
        ];
    }

    public function test_normalize_phone_null(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'normalizePhone');

        $result = $method->invoke($this->service, null);

        $this->assertNull($result);
    }

    // ========== Uzum address building ==========

    public function test_build_uzum_address_from_full_address(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'buildUzumAddress');

        $order = new UzumOrder;
        $order->delivery_address_full = 'г. Ташкент, ул. Навои, д. 10';
        $order->delivery_city = 'Ташкент';
        $order->delivery_street = 'ул. Навои';
        $order->delivery_home = '10';

        $result = $method->invoke($this->service, $order);

        $this->assertEquals('г. Ташкент, ул. Навои, д. 10', $result);
    }

    public function test_build_uzum_address_from_parts(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'buildUzumAddress');

        $order = new UzumOrder;
        $order->delivery_address_full = null;
        $order->delivery_city = 'Ташкент';
        $order->delivery_street = 'ул. Навои';
        $order->delivery_home = '10';
        $order->delivery_flat = '5';

        $result = $method->invoke($this->service, $order);

        $this->assertEquals('Ташкент, ул. Навои, д. 10, кв. 5', $result);
    }

    public function test_build_uzum_address_null_when_empty(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'buildUzumAddress');

        $order = new UzumOrder;
        $order->delivery_address_full = null;
        $order->delivery_city = null;
        $order->delivery_street = null;
        $order->delivery_home = null;
        $order->delivery_flat = null;

        $result = $method->invoke($this->service, $order);

        $this->assertNull($result);
    }

    // ========== DBS detection ==========

    public function test_is_uzum_dbs_order(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'isUzumDbsOrder');

        $dbsOrder = new UzumOrder;
        $dbsOrder->delivery_type = 'DBS';
        $this->assertTrue($method->invoke($this->service, $dbsOrder));

        $edbsOrder = new UzumOrder;
        $edbsOrder->delivery_type = 'EDBS';
        $this->assertTrue($method->invoke($this->service, $edbsOrder));

        $fbsOrder = new UzumOrder;
        $fbsOrder->delivery_type = 'FBS';
        $this->assertFalse($method->invoke($this->service, $fbsOrder));
    }

    public function test_is_wb_dbs_order(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'isWbDbsOrder');

        $dbsOrder = new WbOrder;
        $dbsOrder->wb_delivery_type = 'dbs';
        $this->assertTrue($method->invoke($this->service, $dbsOrder));

        $dbsOrder2 = new WbOrder;
        $dbsOrder2->wb_delivery_type = 'DBS';
        $this->assertTrue($method->invoke($this->service, $dbsOrder2));

        $dbsOrder3 = new WbOrder;
        $dbsOrder3->wb_delivery_type = '2';
        $this->assertTrue($method->invoke($this->service, $dbsOrder3));

        $fbsOrder = new WbOrder;
        $fbsOrder->wb_delivery_type = 'fbs';
        $this->assertFalse($method->invoke($this->service, $fbsOrder));

        $nullOrder = new WbOrder;
        $nullOrder->wb_delivery_type = null;
        $this->assertFalse($method->invoke($this->service, $nullOrder));
    }

    // ========== Extract logic (without DB) ==========

    public function test_extract_skips_fbs_uzum_order(): void
    {
        $account = new MarketplaceAccount;
        $account->company_id = 1;
        $account->marketplace = 'uzum';

        $order = new UzumOrder;
        $order->delivery_type = 'FBS';
        $order->customer_name = 'Тест';
        $order->customer_phone = '+998901234567';

        $result = $this->service->extractFromUzumOrder($account, $order);

        $this->assertNull($result);
    }

    public function test_extract_skips_order_without_phone(): void
    {
        $account = new MarketplaceAccount;
        $account->company_id = 1;
        $account->marketplace = 'uzum';

        $order = new UzumOrder;
        $order->delivery_type = 'DBS';
        $order->customer_name = 'Тест';
        $order->customer_phone = null;

        $result = $this->service->extractFromUzumOrder($account, $order);

        $this->assertNull($result);
    }

    public function test_extract_skips_order_without_name(): void
    {
        $account = new MarketplaceAccount;
        $account->company_id = 1;
        $account->marketplace = 'uzum';

        $order = new UzumOrder;
        $order->delivery_type = 'DBS';
        $order->customer_name = null;
        $order->customer_phone = '+998901234567';

        $result = $this->service->extractFromUzumOrder($account, $order);

        $this->assertNull($result);
    }

    public function test_extract_skips_short_phone(): void
    {
        $account = new MarketplaceAccount;
        $account->company_id = 1;
        $account->marketplace = 'uzum';

        $order = new UzumOrder;
        $order->delivery_type = 'DBS';
        $order->customer_name = 'Тест';
        $order->customer_phone = '12345';

        $result = $this->service->extractFromUzumOrder($account, $order);

        $this->assertNull($result);
    }

    public function test_extract_skips_wb_fbs_order(): void
    {
        $account = new MarketplaceAccount;
        $account->company_id = 1;
        $account->marketplace = 'wb';

        $order = new WbOrder;
        $order->wb_delivery_type = 'fbs';
        $order->customer_name = 'Тест';
        $order->customer_phone = '+79161234567';

        $result = $this->service->extractFromWbOrder($account, $order);

        $this->assertNull($result);
    }

    public function test_extract_skips_ozon_order_without_phone(): void
    {
        $account = new MarketplaceAccount;
        $account->company_id = 1;
        $account->marketplace = 'ozon';

        $order = new OzonOrder;
        $order->customer_name = 'Тест';
        $order->customer_phone = null;

        $result = $this->service->extractFromOzonOrder($account, $order);

        $this->assertNull($result);
    }

    // ========== extractFromOrder dispatch ==========

    public function test_extract_from_order_dispatches_to_correct_method(): void
    {
        $account = new MarketplaceAccount;
        $account->company_id = 1;

        // FBS order should return null without DB interaction
        $uzumFbs = new UzumOrder;
        $uzumFbs->delivery_type = 'FBS';
        $uzumFbs->customer_name = 'Тест';
        $uzumFbs->customer_phone = '+998901234567';

        $this->assertNull($this->service->extractFromOrder($account, $uzumFbs));

        $wbFbs = new WbOrder;
        $wbFbs->wb_delivery_type = null;
        $wbFbs->customer_name = 'Тест';
        $wbFbs->customer_phone = '+79161234567';

        $this->assertNull($this->service->extractFromOrder($account, $wbFbs));
    }

    // ========== MarketplaceCustomer model ==========

    public function test_marketplace_customer_source_label(): void
    {
        $customer = new MarketplaceCustomer;

        $customer->source = 'uzum';
        $this->assertEquals('Uzum Market', $customer->getSourceLabel());

        $customer->source = 'wb';
        $this->assertEquals('Wildberries', $customer->getSourceLabel());

        $customer->source = 'ozon';
        $this->assertEquals('Ozon', $customer->getSourceLabel());

        $customer->source = 'ym';
        $this->assertEquals('Yandex Market', $customer->getSourceLabel());
    }

    // ========== MarketplaceCustomerOrder model ==========

    public function test_customer_order_is_cancelled(): void
    {
        $co = new MarketplaceCustomerOrder;

        $co->status = 'cancelled';
        $this->assertTrue($co->isCancelled());

        $co->status = 'canceled';
        $this->assertTrue($co->isCancelled());

        $co->status = 'CANCELLED';
        $this->assertTrue($co->isCancelled());

        $co->status = 'delivered';
        $this->assertFalse($co->isCancelled());

        $co->status = 'processing';
        $this->assertFalse($co->isCancelled());
    }

    public function test_customer_order_status_labels(): void
    {
        $co = new MarketplaceCustomerOrder;

        $co->status = 'delivered';
        $this->assertEquals('Доставлен', $co->getStatusLabel());

        $co->status = 'cancelled';
        $this->assertEquals('Отменён', $co->getStatusLabel());

        $co->status = 'processing';
        $this->assertEquals('В обработке', $co->getStatusLabel());

        $co->status = 'new';
        $this->assertEquals('Новый', $co->getStatusLabel());

        $co->status = null;
        $this->assertEquals('Неизвестен', $co->getStatusLabel());
    }

    // ========== Source label helper ==========

    public function test_get_source_label(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'getSourceLabel');

        $this->assertEquals('Uzum Market', $method->invoke($this->service, 'uzum'));
        $this->assertEquals('Wildberries', $method->invoke($this->service, 'wb'));
        $this->assertEquals('Ozon', $method->invoke($this->service, 'ozon'));
        $this->assertEquals('Yandex Market', $method->invoke($this->service, 'ym'));
    }

    // ========== Order items extraction ==========

    public function test_get_order_items_from_uzum(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'getOrderItems');

        $item = new \App\Models\UzumOrderItem;
        $item->name = 'Футболка';
        $item->external_offer_id = 'SKU-001';
        $item->quantity = 2;
        $item->price = 50000;
        $item->total_price = 100000;

        $order = new UzumOrder;
        // Устанавливаем items через setRelation
        $order->setRelation('items', collect([$item]));

        $result = $method->invoke($this->service, $order);

        $this->assertCount(1, $result);
        $this->assertEquals('Футболка', $result[0]['name']);
        $this->assertEquals('SKU-001', $result[0]['sku']);
        $this->assertEquals(2, $result[0]['quantity']);
        $this->assertEquals(50000, $result[0]['price']);
        $this->assertEquals(100000, $result[0]['total_price']);
    }

    public function test_get_order_items_from_wb(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'getOrderItems');

        $item = new \App\Models\WbOrderItem;
        $item->name = 'Куртка';
        $item->external_offer_id = 'WB-123';
        $item->quantity = 1;
        $item->price = 5000;
        $item->total_price = 5000;

        $order = new WbOrder;
        $order->setRelation('items', collect([$item]));

        $result = $method->invoke($this->service, $order);

        $this->assertCount(1, $result);
        $this->assertEquals('Куртка', $result[0]['name']);
        $this->assertEquals('WB-123', $result[0]['sku']);
    }

    public function test_get_order_items_from_ozon(): void
    {
        $method = new ReflectionMethod(MarketplaceCustomerService::class, 'getOrderItems');

        $order = new OzonOrder;
        $order->products = [
            ['name' => 'Наушники', 'offer_id' => 'OZ-001', 'quantity' => 1, 'price' => 3500],
            ['name' => 'Кабель', 'offer_id' => 'OZ-002', 'quantity' => 2, 'price' => 500],
        ];

        $result = $method->invoke($this->service, $order);

        $this->assertCount(2, $result);
        $this->assertEquals('Наушники', $result[0]['name']);
        $this->assertEquals('OZ-001', $result[0]['sku']);
        $this->assertEquals(3500, $result[0]['total_price']);
        $this->assertEquals('Кабель', $result[1]['name']);
        $this->assertEquals(1000, $result[1]['total_price']); // 500 * 2
    }
}
