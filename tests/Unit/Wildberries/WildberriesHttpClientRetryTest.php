<?php

declare(strict_types=1);

namespace Tests\Unit\Wildberries;

use App\Exceptions\RateLimitException;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

/**
 * Тесты retry логики при получении 429 от WB API
 */
class WildberriesHttpClientRetryTest extends TestCase
{
    private MarketplaceAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        // Мок MarketplaceAccount через Mockery
        $this->account = Mockery::mock(MarketplaceAccount::class)->makePartial();
        $this->account->id = 1;
        $this->account->api_key = 'test-token-123';
        $this->account->shouldReceive('getWbToken')->andReturn('test-token-123');
        $this->account->shouldReceive('markWbTokensValid')->andReturnNull();
        $this->account->shouldReceive('markWbTokensInvalid')->andReturnNull();

        // Устанавливаем нулевые задержки для тестов (sleep(0) = мгновенно)
        config([
            'wildberries.retry.max_attempts' => 3,
            'wildberries.retry.delays' => [0, 0, 0],
            'wildberries.retry.max_delay' => 0,
            'wildberries.timeout' => 30,
            'wildberries.sandbox' => false,
            'wildberries.verify_ssl' => true,
            'wildberries.base_urls' => [
                'common' => 'https://common-api.wildberries.ru',
                'content' => 'https://content-api.wildberries.ru',
                'marketplace' => 'https://marketplace-api.wildberries.ru',
            ],
            'wildberries.tokens' => [],
            'app.debug' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /**
     * Успешный запрос без retry
     */
    public function test_successful_request_without_retry(): void
    {
        Http::fake([
            'common-api.wildberries.ru/*' => Http::response(['data' => 'ok'], 200),
        ]);

        $client = new WildberriesHttpClient($this->account);
        $result = $client->get('common', '/api/v1/test');

        $this->assertEquals(['data' => 'ok'], $result);

        Http::assertSentCount(1);
    }

    /**
     * 429 на первой попытке, успех на второй — retry работает
     */
    public function test_retries_on_429_and_succeeds(): void
    {
        $callCount = 0;

        Http::fake(function ($request) use (&$callCount) {
            $callCount++;

            if ($callCount === 1) {
                return Http::response('Rate limit exceeded', 429, [
                    'Retry-After' => '5',
                ]);
            }

            return Http::response(['data' => 'ok'], 200);
        });

        Log::shouldReceive('warning')->atLeast()->once();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('channel')->andReturnSelf()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $client = new WildberriesHttpClient($this->account);
        $result = $client->get('common', '/api/v1/test');

        $this->assertEquals(['data' => 'ok'], $result);
        $this->assertEquals(2, $callCount);
    }

    /**
     * 429 на всех попытках — выбрасывается RateLimitException
     */
    public function test_throws_rate_limit_exception_after_max_retries(): void
    {
        Http::fake([
            'common-api.wildberries.ru/*' => Http::response('Rate limit exceeded', 429, [
                'Retry-After' => '10',
            ]),
        ]);

        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('channel')->andReturnSelf()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $this->expectException(RateLimitException::class);
        $this->expectExceptionMessage('Wildberries rate limit exceeded (429)');

        $client = new WildberriesHttpClient($this->account);
        $client->get('common', '/api/v1/test');
    }

    /**
     * RateLimitException содержит корректное значение retryAfter
     */
    public function test_rate_limit_exception_contains_retry_after(): void
    {
        Http::fake([
            'common-api.wildberries.ru/*' => Http::response('Rate limit exceeded', 429, [
                'Retry-After' => '42',
            ]),
        ]);

        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('channel')->andReturnSelf()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        try {
            $client = new WildberriesHttpClient($this->account);
            $client->get('common', '/api/v1/test');
            $this->fail('Expected RateLimitException');
        } catch (RateLimitException $e) {
            $this->assertEquals(42, $e->getRetryAfter());
            $this->assertEquals(429, $e->getCode());
        }
    }

    /**
     * Retry работает для POST запросов
     */
    public function test_retries_on_429_for_post_request(): void
    {
        $callCount = 0;

        Http::fake(function ($request) use (&$callCount) {
            $callCount++;

            if ($callCount <= 2) {
                return Http::response('Rate limit exceeded', 429, [
                    'X-Ratelimit-Retry' => '3',
                ]);
            }

            return Http::response(['result' => 'created'], 200);
        });

        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('channel')->andReturnSelf()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $client = new WildberriesHttpClient($this->account);
        $result = $client->post('common', '/api/v1/test', ['key' => 'value']);

        $this->assertEquals(['result' => 'created'], $result);
        $this->assertEquals(3, $callCount);
    }

    /**
     * Не-429 ошибки не вызывают retry
     */
    public function test_does_not_retry_on_non_429_errors(): void
    {
        Http::fake([
            'common-api.wildberries.ru/*' => Http::response('Server Error', 500),
        ]);

        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('channel')->andReturnSelf()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Wildberries API error (HTTP 500)');

        $client = new WildberriesHttpClient($this->account);
        $client->get('common', '/api/v1/test');
    }

    /**
     * Проверка getRetryDelay через рефлексию
     */
    public function test_get_retry_delay_uses_header_value(): void
    {
        config([
            'wildberries.retry.max_delay' => 120,
            'wildberries.retry.delays' => [5, 15, 30],
        ]);

        $client = new WildberriesHttpClient($this->account);

        $reflection = new \ReflectionMethod($client, 'getRetryDelay');
        $reflection->setAccessible(true);

        // Когда сервер указал Retry-After, используем его
        $this->assertEquals(10, $reflection->invoke($client, 1, 10));
        $this->assertEquals(60, $reflection->invoke($client, 1, 60));

        // Не превышаем максимум
        $this->assertEquals(120, $reflection->invoke($client, 1, 200));

        // retryAfterHeader = 0 означает "не указан" → используем конфиг задержки
        $this->assertEquals(5, $reflection->invoke($client, 1, 0));
    }

    /**
     * Проверка getRetryDelay использует конфиг задержки
     */
    public function test_get_retry_delay_uses_config_delays(): void
    {
        config([
            'wildberries.retry.max_delay' => 120,
            'wildberries.retry.delays' => [5, 15, 30],
        ]);

        $client = new WildberriesHttpClient($this->account);

        $reflection = new \ReflectionMethod($client, 'getRetryDelay');
        $reflection->setAccessible(true);

        // Когда header = 0, используем конфиг задержки
        // But wait, getRetryDelay checks if $retryAfterHeader > 0
        // Header 0 means "not set", so config is used
        // Actually, RateLimitException always sets retryAfter to at least 60 by default
        // But let me test the edge case where retryAfterHeader is passed as 0

        // retryAfterHeader <= 0 → используем конфиг
        // attempt 1 → delays[0] = 5
        $this->assertEquals(5, $reflection->invoke($client, 1, 0));
        // attempt 2 → delays[1] = 15
        $this->assertEquals(15, $reflection->invoke($client, 2, 0));
        // attempt 3 → delays[2] = 30
        $this->assertEquals(30, $reflection->invoke($client, 3, 0));
    }

    /**
     * max_attempts = 1 означает без retry
     */
    public function test_max_attempts_one_means_no_retry(): void
    {
        config(['wildberries.retry.max_attempts' => 1]);

        Http::fake([
            'common-api.wildberries.ru/*' => Http::response('Rate limit exceeded', 429, [
                'Retry-After' => '5',
            ]),
        ]);

        Log::shouldReceive('warning')->zeroOrMoreTimes();
        Log::shouldReceive('error')->zeroOrMoreTimes();
        Log::shouldReceive('info')->zeroOrMoreTimes();
        Log::shouldReceive('channel')->andReturnSelf()->zeroOrMoreTimes();
        Log::shouldReceive('debug')->zeroOrMoreTimes();

        $this->expectException(RateLimitException::class);

        $client = new WildberriesHttpClient($this->account);
        $client->get('common', '/api/v1/test');
    }
}
