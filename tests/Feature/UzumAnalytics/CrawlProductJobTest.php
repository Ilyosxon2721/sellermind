<?php

declare(strict_types=1);

namespace Tests\Feature\UzumAnalytics;

use App\Modules\UzumAnalytics\Jobs\CrawlProductJob;
use App\Modules\UzumAnalytics\Models\UzumProductSnapshot;
use App\Modules\UzumAnalytics\Models\UzumTrackedProduct;
use App\Modules\UzumAnalytics\Repositories\AnalyticsRepository;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use App\Telegram\TelegramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Тесты CrawlProductJob (#083).
 *
 * Покрытие:
 *  - Снепшот сохраняется при успешном ответе API
 *  - Пустой ответ API → Job завершается без сохранения
 *  - Telegram алерт при значительном изменении цены
 *  - Алерт не отправляется если цена не изменилась
 */
class CrawlProductJobTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Log::shouldReceive('channel')->andReturnSelf()->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function makeApiResponse(int $productId, int $priceTimyen = 100000): array
    {
        return [
            'product' => [
                'id'            => $productId,
                'title'         => "Тестовый товар #{$productId}",
                'minSellPrice'  => $priceTimyen,
                'maxFullPrice'  => $priceTimyen + 10000,
                'rating'        => 4.5,
                'reviewsAmount' => 100,
                'ordersAmount'  => 500,
                'category'      => ['id' => 10],
                'shop'          => ['slug' => 'test-shop'],
            ],
        ];
    }

    /**
     * Снепшот сохраняется в БД при успешном ответе
     */
    public function test_saves_snapshot_on_successful_api_response(): void
    {
        $productId = 12345;

        $apiClient  = Mockery::mock(UzumAnalyticsApiClient::class);
        $repository = app(AnalyticsRepository::class);
        $telegram   = Mockery::mock(TelegramService::class);

        $apiClient->shouldReceive('getProduct')
            ->once()
            ->with($productId)
            ->andReturn($this->makeApiResponse($productId, 500000)); // 5000 сум

        $telegram->shouldReceive('isConfigured')->zeroOrMoreTimes()->andReturn(false);

        $job = new CrawlProductJob($productId, null);
        $job->handle($apiClient, $repository, $telegram);

        $snapshot = UzumProductSnapshot::where('product_id', $productId)->first();
        $this->assertNotNull($snapshot);
        $this->assertEquals(5000, $snapshot->price);   // 500000 тийин ÷ 100
        $this->assertEquals('test-shop', $snapshot->shop_slug);
        $this->assertEquals("Тестовый товар #{$productId}", $snapshot->title);
    }

    /**
     * Пустой ответ API → ничего не сохраняется
     */
    public function test_skips_on_empty_api_response(): void
    {
        $productId = 99999;

        $apiClient  = Mockery::mock(UzumAnalyticsApiClient::class);
        $repository = app(AnalyticsRepository::class);
        $telegram   = Mockery::mock(TelegramService::class);

        $apiClient->shouldReceive('getProduct')
            ->once()
            ->with($productId)
            ->andReturn([]);

        $job = new CrawlProductJob($productId, null);
        $job->handle($apiClient, $repository, $telegram);

        $this->assertDatabaseMissing('uzum_products_snapshots', ['product_id' => $productId]);
    }

    /**
     * Telegram алерт отправляется если цена изменилась > threshold%
     */
    public function test_sends_telegram_alert_on_significant_price_change(): void
    {
        $productId = 11111;
        $companyId = 1;

        // Создаём компанию
        $company = \App\Models\Company::factory()->create(['id' => $companyId]);

        // Создаём запись об отслеживаемом товаре с ценой 1000 сум
        UzumTrackedProduct::create([
            'company_id'           => $companyId,
            'product_id'           => $productId,
            'last_price'           => 1000,
            'alert_enabled'        => true,
            'alert_threshold_pct'  => 5,
        ]);

        $apiClient  = Mockery::mock(UzumAnalyticsApiClient::class);
        $repository = app(AnalyticsRepository::class);
        $telegram   = Mockery::mock(TelegramService::class);

        // Новая цена 2000 сум = +100% изменение (> 5% порог)
        $apiClient->shouldReceive('getProduct')
            ->once()
            ->andReturn($this->makeApiResponse($productId, 200000)); // 2000 сум

        $telegram->shouldReceive('isConfigured')->andReturn(true);
        $telegram->shouldReceive('sendMessage')->once();

        $company->update(['settings' => array_merge($company->settings ?? [], ['telegram_chat_id' => '123456'])]);

        $job = new CrawlProductJob($productId, $companyId);
        $job->handle($apiClient, $repository, $telegram);

        // last_price должна обновиться
        $tracked = UzumTrackedProduct::where('product_id', $productId)->first();
        $this->assertEquals(2000, (float) $tracked->last_price);
    }

    /**
     * Алерт НЕ отправляется если цена не изменилась значительно
     */
    public function test_no_alert_when_price_unchanged(): void
    {
        $productId = 22222;
        $companyId = 2;

        $company = \App\Models\Company::factory()->create(['id' => $companyId]);

        UzumTrackedProduct::create([
            'company_id'           => $companyId,
            'product_id'           => $productId,
            'last_price'           => 1000,
            'alert_enabled'        => true,
            'alert_threshold_pct'  => 10, // 10% порог
        ]);

        $apiClient  = Mockery::mock(UzumAnalyticsApiClient::class);
        $repository = app(AnalyticsRepository::class);
        $telegram   = Mockery::mock(TelegramService::class);

        // Цена 1020 сум = +2%, меньше 10% порога — алерт не нужен
        $apiClient->shouldReceive('getProduct')
            ->once()
            ->andReturn($this->makeApiResponse($productId, 102000)); // 1020 сум

        $telegram->shouldReceive('isConfigured')->zeroOrMoreTimes()->andReturn(true);
        $telegram->shouldReceive('sendMessage')->never();

        $job = new CrawlProductJob($productId, $companyId);
        $job->handle($apiClient, $repository, $telegram);

        // Снепшот сохранён — алерт не должен был отправиться
        $this->assertDatabaseHas('uzum_products_snapshots', ['product_id' => $productId]);
    }

    /**
     * Без companyId — алерт не проверяется
     */
    public function test_system_crawl_without_company_skips_alert_check(): void
    {
        $productId = 33333;

        $apiClient  = Mockery::mock(UzumAnalyticsApiClient::class);
        $repository = app(AnalyticsRepository::class);
        $telegram   = Mockery::mock(TelegramService::class);

        $apiClient->shouldReceive('getProduct')
            ->once()
            ->andReturn($this->makeApiResponse($productId));

        // Telegram никогда не вызывается для системного краулинга
        $telegram->shouldReceive('sendMessage')->never();
        $telegram->shouldReceive('isConfigured')->never();

        $job = new CrawlProductJob($productId, null); // companyId = null
        $job->handle($apiClient, $repository, $telegram);

        $this->assertDatabaseHas('uzum_products_snapshots', ['product_id' => $productId]);
    }
}
