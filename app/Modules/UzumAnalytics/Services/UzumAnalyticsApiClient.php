<?php

// file: app/Modules/UzumAnalytics/Services/UzumAnalyticsApiClient.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP клиент для публичного API Uzum.
 *
 * Возможности:
 *  - Автоматическая подстановка JWT из пула токенов
 *  - Retry с экспоненциальным backoff при 401/429/503
 *  - Rate limiting через RateLimiter
 *  - Circuit Breaker — защита от перегрузки
 *  - Кэширование ответов в Redis на 30 минут
 */
final class UzumAnalyticsApiClient
{
    private readonly string $baseUrl;

    private readonly int $cacheTtl;

    private readonly array $retryConfig;

    public function __construct(
        private readonly TokenRefreshService $tokenService,
        private readonly RateLimiter $rateLimiter,
        private readonly CircuitBreaker $circuitBreaker,
    ) {
        $this->baseUrl     = rtrim(config('uzum-crawler.api.rest_base_url', 'https://api.umarket.uz/api'), '/');
        $this->cacheTtl    = (int) config('uzum-crawler.cache_ttl_minutes', 30) * 60;
        $this->retryConfig = config('uzum-crawler.retry', ['max_attempts' => 3, 'backoff_seconds' => [30, 60, 120]]);
    }

    /**
     * Получить карточку товара по ID
     */
    public function getProduct(int $productId): array
    {
        return $this->cachedRequest("product:{$productId}", function () use ($productId): array {
            $this->rateLimiter->throttle('product');

            // ТЗ: GET /v2/product/{productId} — цены в тийинах (÷100 = сум)
            return $this->request('GET', "/v2/product/{$productId}");
        });
    }

    /**
     * Получить список товаров категории (GraphQL пагинация)
     */
    public function getCategory(int $categoryId, int $page = 0, int $size = 48): array
    {
        $cacheKey = "category:{$categoryId}:p{$page}:s{$size}";

        return $this->cachedRequest($cacheKey, function () use ($categoryId, $page, $size): array {
            $this->rateLimiter->throttle('category');

            // ТЗ: POST https://graphql.umarket.uz — отдельный хост для GraphQL
            $graphqlUrl = config('uzum-crawler.api.graphql_url', 'https://graphql.umarket.uz');

            return $this->requestUrl('POST', $graphqlUrl, [
                'query'     => $this->categoryGraphqlQuery(),
                'variables' => [
                    'categoryId' => $categoryId,
                    'offset'     => $page * $size,
                    'limit'      => $size,
                    'sort'       => 'BY_REVIEWS_COUNT_DESC',
                ],
            ], graphql: true);
        });
    }

    /**
     * Получить данные магазина по slug
     */
    public function getShop(string $shopSlug): array
    {
        return $this->cachedRequest("shop:{$shopSlug}", function () use ($shopSlug): array {
            $this->rateLimiter->throttle('shop');

            // ТЗ: GET /shop/{shopName}
            return $this->request('GET', "/shop/{$shopSlug}");
        });
    }

    /**
     * Получить корневые категории (для синхронизации дерева)
     */
    public function getRootCategories(): array
    {
        return $this->cachedRequest('root_categories', function (): array {
            $this->rateLimiter->throttle('category');

            // ТЗ: GET /main/root-categories
            return $this->request('GET', '/main/root-categories');
        });
    }

    /**
     * Поиск товаров
     */
    public function searchProducts(string $query, int $page = 0): array
    {
        $cacheKey = "search:' . md5($query) . ':p{$page}";

        return $this->cachedRequest($cacheKey, function () use ($query, $page): array {
            $this->rateLimiter->throttle('product');

            return $this->request('GET', '/api/search', [
                'text'   => $query,
                'offset' => $page * 48,
                'limit'  => 48,
            ]);
        });
    }

    /**
     * Запрос к REST API (путь относительно baseUrl)
     */
    private function request(string $method, string $path, array $payload = []): array
    {
        return $this->requestUrl($method, $this->baseUrl . $path, $payload);
    }

    /**
     * Базовый HTTP запрос с retry и backoff.
     * graphql=true добавляет X-Iid заголовок (UUID из пула токенов).
     */
    private function requestUrl(string $method, string $url, array $payload = [], bool $graphql = false): array
    {
        if ($this->circuitBreaker->isOpen()) {
            Log::warning('UzumCrawler: Circuit Breaker открыт, запрос отклонён', ['url' => $url]);
            throw new \RuntimeException('UzumCrawler: краулер на паузе (Circuit Breaker)');
        }

        $maxAttempts   = $this->retryConfig['max_attempts'];
        $backoffs      = $this->retryConfig['backoff_seconds'];
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $token     = $this->tokenService->getToken();
                $userAgent = $this->getRandomUserAgent();

                $headers = [
                    'User-Agent'      => $userAgent,
                    'Accept'          => 'application/json',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,uz;q=0.8',
                    'Origin'          => 'https://uzum.uz',
                    'Referer'         => 'https://uzum.uz/',
                ];

                // X-Iid нужен для GraphQL запросов (UUID из записи токена)
                if ($graphql && $token?->iid) {
                    $headers['X-Iid'] = $token->iid;
                }

                $httpClient = Http::withHeaders($headers);

                if ($token) {
                    $httpClient = $httpClient->withToken($token->token);
                }

                $response = match (strtoupper($method)) {
                    'GET'  => $httpClient->timeout(30)->get($url, $payload),
                    'POST' => $httpClient->timeout(30)->post($url, $payload),
                    default => throw new \InvalidArgumentException("Неподдерживаемый метод: {$method}"),
                };

                if ($response->status() === 401) {
                    // Токен недействителен — деактивировать и повторить
                    $token?->update(['is_active' => false]);
                    Log::info("UzumCrawler: 401, смена токена [{$url}]");
                    continue;
                }

                if (in_array($response->status(), [429, 503], true)) {
                    // Rate limit или перегрузка — backoff
                    $this->circuitBreaker->recordFailure();
                    $sleepSec = $backoffs[$attempt - 1] ?? end($backoffs);
                    Log::warning("UzumCrawler: {$response->status()} [{$url}], backoff {$sleepSec}s");
                    sleep($sleepSec);
                    continue;
                }

                if ($response->successful()) {
                    $this->circuitBreaker->recordSuccess();

                    return $response->json() ?? [];
                }

                Log::warning("UzumCrawler: HTTP {$response->status()} [{$url}]");
            } catch (\Throwable $e) {
                $lastException = $e;
                $this->circuitBreaker->recordFailure();
                Log::error("UzumCrawler: ошибка запроса [{$url}]", [
                    'attempt' => $attempt,
                    'error'   => $e->getMessage(),
                ]);

                if ($attempt < $maxAttempts) {
                    $sleepSec = $backoffs[$attempt - 1] ?? end($backoffs);
                    sleep($sleepSec);
                }
            }
        }

        $this->circuitBreaker->recordFailure();
        throw $lastException ?? new \RuntimeException("UzumCrawler: все попытки исчерпаны для {$path}");
    }

    /**
     * Кэшировать результат в Redis
     */
    private function cachedRequest(string $key, \Closure $callback): array
    {
        $cacheKey = config('uzum-crawler.redis_prefix', 'uzum_crawler:') . 'api:' . $key;

        return Cache::remember($cacheKey, $this->cacheTtl, $callback);
    }

    private function getRandomUserAgent(): string
    {
        $agents = config('uzum-crawler.user_agents', []);

        return $agents[array_rand($agents)];
    }

    /**
     * GraphQL запрос для получения товаров категории
     */
    private function categoryGraphqlQuery(): string
    {
        return <<<'GQL'
        query CategoryProductList(
            $categoryId: Long!,
            $offset: Int!,
            $limit: Int!,
            $sort: ProductSortType
        ) {
            makeSearch(
                categoryId: $categoryId,
                offset: $offset,
                limit: $limit,
                sort: $sort
            ) {
                total
                items {
                    catalogCard {
                        ... on ProductCard {
                            id
                            title
                            slug
                            rating
                            reviewsCount
                            ordersCount
                            minFullPrice
                            minSellPrice
                            category { id title }
                            shop { id slug title }
                            photos { photoKey }
                        }
                    }
                }
            }
        }
        GQL;
    }
}
