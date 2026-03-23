<?php

declare(strict_types=1);

namespace Tests\Unit\UzumAnalytics;

use App\Modules\UzumAnalytics\Models\UzumToken;
use App\Modules\UzumAnalytics\Services\CircuitBreaker;
use App\Modules\UzumAnalytics\Services\RateLimiter;
use App\Modules\UzumAnalytics\Services\TokenRefreshService;
use App\Modules\UzumAnalytics\Services\UzumAnalyticsApiClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Тесты HTTP клиента Uzum Analytics (#082).
 *
 * Покрытие:
 *  - Успешный запрос
 *  - Retry при 429 (с backoff)
 *  - Retry при 503
 *  - Смена токена при 401
 *  - Circuit Breaker блокирует запросы
 */
class UzumApiClientTest extends TestCase
{
    private TokenRefreshService $tokenService;

    private RateLimiter $rateLimiter;

    private CircuitBreaker $circuitBreaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Нулевые задержки для тестов
        config([
            'uzum-crawler.api.rest_base_url'    => 'https://api.uzum.uz/api',
            'uzum-crawler.api.graphql_url'       => 'https://graphql.uzum.uz',
            'uzum-crawler.cache_ttl_minutes'     => 0, // без кэша
            'uzum-crawler.redis_prefix'          => 'test:uzum:',
            'uzum-crawler.retry.max_attempts'    => 3,
            'uzum-crawler.retry.backoff_seconds' => [0, 0, 0], // мгновенно
            'uzum-crawler.user_agents'           => ['TestAgent/1.0'],
        ]);

        // Мок TokenRefreshService — всегда возвращает фейковый токен
        $fakeToken                 = Mockery::mock(UzumToken::class)->makePartial();
        $fakeToken->token          = 'fake-jwt-token';
        $fakeToken->iid            = 'fake-uuid';
        $fakeToken->shouldReceive('update')->andReturnNull()->byDefault();
        $fakeToken->shouldReceive('incrementRequests')->andReturnNull()->byDefault();

        $this->tokenService = Mockery::mock(TokenRefreshService::class);
        $this->tokenService->shouldReceive('getToken')->andReturn($fakeToken)->byDefault();

        // Мок RateLimiter — мгновенный throttle
        $this->rateLimiter = Mockery::mock(RateLimiter::class);
        $this->rateLimiter->shouldReceive('throttle')->andReturnNull()->byDefault();

        // Circuit Breaker с закрытой цепью
        $this->circuitBreaker = Mockery::mock(CircuitBreaker::class);
        $this->circuitBreaker->shouldReceive('isOpen')->andReturn(false)->byDefault();
        $this->circuitBreaker->shouldReceive('recordSuccess')->andReturnNull()->byDefault();
        $this->circuitBreaker->shouldReceive('recordFailure')->andReturnNull()->byDefault();

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

    private function makeClient(): UzumAnalyticsApiClient
    {
        return new UzumAnalyticsApiClient(
            $this->tokenService,
            $this->rateLimiter,
            $this->circuitBreaker,
        );
    }

    /**
     * Успешный ответ 200 — возвращает данные
     */
    public function test_successful_request_returns_data(): void
    {
        Http::fake([
            'api.uzum.uz/*' => Http::response(['product' => ['id' => 123, 'title' => 'Test']], 200),
        ]);

        $client = $this->makeClient();
        $result = $client->getProduct(123);

        $this->assertArrayHasKey('product', $result);
        $this->assertEquals(123, $result['product']['id']);
        Http::assertSentCount(1);
    }

    /**
     * 429 на первой попытке → retry → успех
     */
    public function test_retries_on_429_and_succeeds(): void
    {
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response('Too Many Requests', 429)
                : Http::response(['product' => ['id' => 1]], 200);
        });

        $this->circuitBreaker->shouldReceive('recordFailure')->once()->andReturnNull();

        $client = $this->makeClient();
        $result = $client->getProduct(1);

        $this->assertArrayHasKey('product', $result);
        $this->assertEquals(2, $calls);
    }

    /**
     * 503 на первой попытке → retry → успех
     */
    public function test_retries_on_503_and_succeeds(): void
    {
        $calls = 0;

        Http::fake(function () use (&$calls) {
            $calls++;

            return $calls === 1
                ? Http::response('Service Unavailable', 503)
                : Http::response(['product' => ['id' => 5]], 200);
        });

        $this->circuitBreaker->shouldReceive('recordFailure')->once()->andReturnNull();

        $client = $this->makeClient();
        $result = $client->getProduct(5);

        $this->assertNotEmpty($result);
        $this->assertEquals(2, $calls);
    }

    /**
     * 401 → токен деактивируется на каждой попытке
     */
    public function test_deactivates_token_on_401(): void
    {
        $fakeToken        = Mockery::mock(UzumToken::class)->makePartial();
        $fakeToken->token = 'expired-token';
        $fakeToken->iid   = 'some-uuid';
        // 3 попытки — на каждой токен деактивируется
        $fakeToken->shouldReceive('update')->times(3)->with(['is_active' => false]);
        $fakeToken->shouldReceive('incrementRequests')->andReturnNull();

        $this->tokenService->shouldReceive('getToken')->andReturn($fakeToken);

        $calls = 0;
        Http::fake(function () use (&$calls) {
            $calls++;
            // Все попытки 401 → все исчерпаны → RuntimeException
            return Http::response('Unauthorized', 401);
        });

        $client = $this->makeClient();

        $this->expectException(\RuntimeException::class);
        $client->getProduct(999);
    }

    /**
     * Circuit Breaker открыт → RuntimeException без HTTP запроса
     */
    public function test_throws_when_circuit_breaker_is_open(): void
    {
        $this->circuitBreaker->shouldReceive('isOpen')->andReturn(true);

        $client = $this->makeClient();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Circuit Breaker/');

        $client->getProduct(42);
    }

    /**
     * Все 3 попытки провалились → RuntimeException
     */
    public function test_throws_after_all_retries_exhausted(): void
    {
        Http::fake([
            'api.uzum.uz/*' => Http::response('Server Error', 500),
        ]);

        $client = $this->makeClient();

        $this->expectException(\RuntimeException::class);
        $client->getProduct(1);
    }

    /**
     * Пустой JSON ответ возвращает пустой массив
     */
    public function test_returns_empty_array_on_null_json(): void
    {
        Http::fake([
            'api.uzum.uz/*' => Http::response('', 200),
        ]);

        $client = $this->makeClient();
        $result = $client->getProduct(1);

        $this->assertEquals([], $result);
    }
}
