<?php

declare(strict_types=1);

namespace Tests\Unit\Store;

use App\Models\Company;
use App\Models\Sale;
use App\Models\Store\Store;
use App\Models\Store\StoreOrder;
use App\Models\Store\StoreOrderItem;
use App\Models\Store\StoreTheme;
use App\Models\User;
use App\Notifications\NewStoreOrderNotification;
use App\Services\SaleService;
use App\Services\Store\StoreOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

/**
 * Тесты для StoreOrderService — синхронизация заказов витрины с системой продаж
 */
class StoreOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private Company $company;

    private Store $store;

    private StoreOrder $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();

        $this->store = Store::create([
            'company_id' => $this->company->id,
            'name' => 'Тестовый магазин',
            'slug' => 'test-store-' . uniqid(),
            'is_active' => true,
            'is_published' => true,
        ]);

        $this->order = StoreOrder::create([
            'store_id' => $this->store->id,
            'customer_name' => 'Иван Иванов',
            'customer_phone' => '+998901234567',
            'subtotal' => 100000,
            'discount' => 0,
            'total' => 100000,
            'status' => StoreOrder::STATUS_NEW,
            'payment_status' => StoreOrder::PAYMENT_PENDING,
        ]);

        StoreOrderItem::create([
            'order_id' => $this->order->id,
            'product_id' => 1,
            'name' => 'Тестовый товар',
            'price' => 50000,
            'quantity' => 2,
            'total' => 100000,
        ]);
    }

    /**
     * Синхронизация заказа создаёт Sale и привязывает к StoreOrder
     */
    public function test_sync_order_creates_sale_and_links_to_order(): void
    {
        $fakeSale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $saleService = Mockery::mock(SaleService::class);
        $saleService->shouldReceive('createSale')->once()->andReturn($fakeSale);
        $saleService->shouldReceive('confirmSale')->once();

        $service = new StoreOrderService($saleService);
        $result = $service->syncOrderToSale($this->order);

        $this->assertNotNull($result);
        $this->assertEquals($fakeSale->id, $result->id);

        $this->order->refresh();
        $this->assertEquals($fakeSale->id, $this->order->sellermind_order_id);
    }

    /**
     * Ошибка confirmSale не прерывает синхронизацию — Sale остаётся в draft
     */
    public function test_sync_order_handles_confirm_failure_gracefully(): void
    {
        $fakeSale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'draft',
        ]);

        $saleService = Mockery::mock(SaleService::class);
        $saleService->shouldReceive('createSale')->once()->andReturn($fakeSale);
        $saleService->shouldReceive('confirmSale')->once()->andThrow(new \RuntimeException('Stock error'));

        $service = new StoreOrderService($saleService);
        $result = $service->syncOrderToSale($this->order);

        // Sale всё равно привязан
        $this->assertNotNull($result);
        $this->order->refresh();
        $this->assertEquals($fakeSale->id, $this->order->sellermind_order_id);
    }

    /**
     * Отмена заказа вызывает cancelSale
     */
    public function test_cancel_order_sale_cancels_linked_sale(): void
    {
        $fakeSale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'confirmed',
        ]);

        $this->order->update(['sellermind_order_id' => $fakeSale->id]);

        $saleService = Mockery::mock(SaleService::class);
        $saleService->shouldReceive('cancelSale')->once()->with(Mockery::on(function ($sale) use ($fakeSale) {
            return $sale->id === $fakeSale->id;
        }));

        $service = new StoreOrderService($saleService);
        $service->cancelOrderSale($this->order);
    }

    /**
     * Отмена заказа без привязки к Sale не вызывает ошибку
     */
    public function test_cancel_order_does_nothing_without_linked_sale(): void
    {
        $saleService = Mockery::mock(SaleService::class);
        $saleService->shouldNotReceive('cancelSale');

        $service = new StoreOrderService($saleService);
        $service->cancelOrderSale($this->order);

        // Не должно быть исключений
        $this->assertTrue(true);
    }

    /**
     * Завершение заказа вызывает completeSale
     */
    public function test_complete_order_sale_completes_linked_sale(): void
    {
        $fakeSale = Sale::factory()->create([
            'company_id' => $this->company->id,
            'status' => 'confirmed',
        ]);

        $this->order->update(['sellermind_order_id' => $fakeSale->id]);

        $saleService = Mockery::mock(SaleService::class);
        $saleService->shouldReceive('completeSale')->once();

        $service = new StoreOrderService($saleService);
        $service->completeOrderSale($this->order);
    }

    /**
     * Уведомление о заказе отправляется пользователям компании
     */
    public function test_notify_store_owner_sends_notification_to_users(): void
    {
        Notification::fake();

        $user = User::factory()->create([
            'company_id' => $this->company->id,
        ]);

        $saleService = Mockery::mock(SaleService::class);
        $service = new StoreOrderService($saleService);

        $service->notifyStoreOwner($this->order);

        Notification::assertSentTo($user, NewStoreOrderNotification::class);
    }
}
