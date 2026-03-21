<?php

declare(strict_types=1);

namespace App\Services\Uzum\Api;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Единый шлюз для всех запросов к Uzum Seller API.
 *
 * Использование:
 *   $api = new UzumApi($account);
 *   $shops = $api->call(UzumEndpoints::SHOPS_LIST);
 *   $orders = $api->call(UzumEndpoints::FBS_ORDERS_LIST, query: ['status' => 'CREATED', 'shopIds' => '123']);
 *   $detail = $api->call(UzumEndpoints::FBS_ORDER_DETAIL, params: ['orderId' => 777]);
 *   $api->call(UzumEndpoints::FBS_ORDER_CONFIRM, params: ['orderId' => 777]);
 */
final class UzumApi
{
    private int $timeout;

    private bool $verifySsl;

    /** Флаг: токен уже обновлялся в этом цикле */
    private bool $tokenRefreshed = false;

    public function __construct(
        private MarketplaceAccount $account,
    ) {
        $this->timeout = (int) config('uzum.timeout', 60);
        $this->verifySsl = (bool) config('uzum.verify_ssl', true);
    }

    /**
     * Выполнить запрос к Uzum API по определению из UzumEndpoints
     *
     * @param  array  $endpoint  Константа из UzumEndpoints (например UzumEndpoints::SHOPS_LIST)
     * @param  array  $params  Подстановки в path: ['orderId' => 123]
     * @param  array  $query  Query-параметры для GET
     * @param  array  $body  Тело запроса для POST/PUT/PATCH
     * @return array Декодированный JSON-ответ
     *
     * @throws \RuntimeException При ошибках API (401, 403, 429, 5xx)
     */
    public function call(
        array $endpoint,
        array $params = [],
        array $query = [],
        array $body = [],
    ): array {
        $method = strtoupper($endpoint['method']);
        $url = UzumEndpoints::buildUrl($endpoint, $params);
        $desc = $endpoint['desc'] ?? $url;

        // OAuth-эндпоинты (отзывы) используют uzum_access_token, остальные — api_key
        $authType = $endpoint['auth'] ?? 'api_key';
        $authHeaders = $authType === 'oauth'
            ? $this->account->getUzumOAuthHeaders()
            : $this->account->getUzumAuthHeaders();

        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $this->trimHeaders($authHeaders));

        Log::info('Uzum API call', [
            'account_id' => $this->account->id,
            'method' => $method,
            'url' => $url,
            'desc' => $desc,
            'auth_type' => $authType,
            'query' => $this->sanitize($query),
            'body' => $method !== 'GET' ? $body : null,
        ]);

        $client = Http::timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySsl])
            ->withHeaders($headers);

        // Выполнение запроса
        $requestUrl = $url;
        if ($method === 'GET' && ! empty($query)) {
            $requestUrl .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $response = match ($method) {
            'GET' => $client->get($requestUrl),
            'POST' => $client->post($requestUrl, ! empty($body) ? $body : (object) []),
            'PUT' => $client->put($requestUrl, $body),
            'PATCH' => $client->patch($requestUrl, $body),
            'DELETE' => $client->delete($requestUrl, $query),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        $status = $response->status();
        $rawBody = $response->body();

        Log::info('Uzum API response', [
            'account_id' => $this->account->id,
            'url' => $url,
            'status' => $status,
            'body_length' => strlen($rawBody),
        ]);

        // Автообновление токена при 401
        if ($status === 401 && ! $this->tokenRefreshed) {
            if ($this->refreshToken()) {
                return $this->call($endpoint, $params, $query, $body);
            }
        }

        // Retry с backoff при 429 (rate limit)
        if ($status === 429) {
            return $this->retryWithBackoff($endpoint, $params, $query, $body, $response);
        }

        if (! $response->successful()) {
            $this->handleError($status, $rawBody, $url, $method, $desc);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Кэшированный вызов API (Redis, TTL в секундах)
     *
     * @param  int  $ttl  Время жизни кэша в секундах
     */
    public function cachedCall(
        array $endpoint,
        array $params = [],
        array $query = [],
        array $body = [],
        int $ttl = 300,
    ): array {
        $cacheKey = 'uzum:' . $this->account->id . ':' . md5(json_encode([
            $endpoint['path'] ?? '',
            $params,
            $query,
            $body,
        ]));

        return Cache::remember($cacheKey, $ttl, function () use ($endpoint, $params, $query, $body) {
            return $this->call($endpoint, $params, $query, $body);
        });
    }

    /**
     * Очистить кэш для текущего аккаунта
     */
    public function flushCache(): void
    {
        // Удаляем кэш по паттерну uzum:{accountId}:*
        $pattern = 'uzum:' . $this->account->id . ':*';

        try {
            $redis = Cache::getStore()->getRedis();
            $prefix = config('cache.prefix', '') . ':';
            $keys = $redis->keys($prefix . $pattern);
            if (! empty($keys)) {
                $redis->del($keys);
            }
        } catch (\Throwable $e) {
            Log::warning('Uzum cache flush failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Пагинированный запрос — собирает все страницы
     *
     * @param  array  $endpoint  Эндпоинт из UzumEndpoints
     * @param  array  $query  Query-параметры (page/size будут управляться автоматически)
     * @param  string  $dataKey  Ключ массива данных в ответе (например 'orderList', 'productList')
     * @param  int  $pageSize  Размер страницы
     * @param  int  $maxPages  Максимум страниц (защита от бесконечного цикла)
     * @param  int  $delayMs  Задержка между страницами (мс) для rate-limit
     * @return array Все собранные записи
     */
    public function paginate(
        array $endpoint,
        array $query = [],
        string $dataKey = 'content',
        int $pageSize = 100,
        int $maxPages = 100,
        int $delayMs = 300,
        array $params = [],
    ): array {
        $all = [];
        $page = 0;

        do {
            $query['page'] = $page;
            $query['size'] = $pageSize;

            $response = $this->call($endpoint, params: $params, query: $query);

            // Извлечь данные: пробуем dataKey, потом payload.dataKey
            $items = $response[$dataKey]
                ?? $response['payload'][$dataKey]
                ?? [];

            if (! is_array($items)) {
                break;
            }

            foreach ($items as $item) {
                $all[] = $item;
            }

            $page++;

            // Задержка между страницами для rate-limit
            if (! empty($items) && $page < $maxPages) {
                usleep($delayMs * 1000);
            }
        } while (count($items) >= $pageSize && $page < $maxPages);

        return $all;
    }

    /**
     * Обновить OAuth2 токен через refresh_token
     */
    private function refreshToken(): bool
    {
        $this->tokenRefreshed = true;

        $refreshToken = $this->account->uzum_refresh_token;
        $clientId = $this->account->uzum_client_id;
        $clientSecret = $this->account->uzum_client_secret;

        if (! $refreshToken || ! $clientId) {
            Log::warning("Uzum token refresh: нет refresh_token или client_id для аккаунта #{$this->account->id}");

            return false;
        }

        Log::info("Uzum token refresh: обновляем токен для аккаунта #{$this->account->id}");

        try {
            $response = Http::timeout(30)
                ->withOptions(['verify' => $this->verifySsl])
                ->asForm()
                ->post(UzumEndpoints::buildUrl(UzumEndpoints::OAUTH_TOKEN), [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $refreshToken,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret ?? '',
                ]);

            if (! $response->successful()) {
                Log::error("Uzum token refresh failed [{$response->status()}]", [
                    'account_id' => $this->account->id,
                    'body' => mb_substr($response->body(), 0, 500),
                ]);

                return false;
            }

            $data = $response->json();
            $accessToken = $data['access_token'] ?? null;
            $newRefreshToken = $data['refresh_token'] ?? null;
            $expiresIn = $data['expires_in'] ?? 3600;

            if (! $accessToken) {
                Log::error('Uzum token refresh: access_token отсутствует в ответе', [
                    'account_id' => $this->account->id,
                ]);

                return false;
            }

            // Сохраняем новые токены
            $this->account->uzum_access_token = $accessToken;
            if ($newRefreshToken) {
                $this->account->uzum_refresh_token = $newRefreshToken;
            }
            $this->account->uzum_token_expires_at = now()->addSeconds($expiresIn);
            $this->account->save();

            // Перечитываем модель чтобы подхватить новые зашифрованные значения
            $this->account->refresh();

            Log::info("Uzum token refresh: успешно обновлён для аккаунта #{$this->account->id}");

            return true;
        } catch (\Throwable $e) {
            Log::error('Uzum token refresh exception', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Повторить запрос с экспоненциальным backoff при 429
     */
    private function retryWithBackoff(
        array $endpoint,
        array $params,
        array $query,
        array $body,
        $firstResponse,
        int $maxRetries = 3,
    ): array {
        $retryAfter = (int) ($firstResponse->header('Retry-After') ?? 0);

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // Задержка: Retry-After или экспоненциальный backoff (1, 2, 4 сек)
            $delay = $retryAfter > 0 ? $retryAfter : (int) (pow(2, $attempt - 1));
            Log::info("Uzum API rate limit: повтор #{$attempt} через {$delay}с", [
                'account_id' => $this->account->id,
                'endpoint' => $endpoint['desc'] ?? '',
            ]);
            sleep($delay);

            try {
                // Рекурсивный вызов — 429 внутри снова попадёт сюда, но maxRetries ограничит
                return $this->doCallWithoutRetry($endpoint, $params, $query, $body);
            } catch (\RuntimeException $e) {
                if ($attempt === $maxRetries || ! str_contains($e->getMessage(), 'Слишком много запросов')) {
                    throw $e;
                }
            }
        }

        throw new \RuntimeException('Слишком много запросов. Подождите минуту.');
    }

    /**
     * Внутренний вызов без retry на 429 (для избежания рекурсии)
     */
    private function doCallWithoutRetry(
        array $endpoint,
        array $params,
        array $query,
        array $body,
    ): array {
        $method = strtoupper($endpoint['method']);
        $url = UzumEndpoints::buildUrl($endpoint, $params);
        $desc = $endpoint['desc'] ?? $url;

        $authType = $endpoint['auth'] ?? 'api_key';
        $authHeaders = $authType === 'oauth'
            ? $this->account->getUzumOAuthHeaders()
            : $this->account->getUzumAuthHeaders();

        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $this->trimHeaders($authHeaders));

        $client = Http::timeout($this->timeout)
            ->withOptions(['verify' => $this->verifySsl])
            ->withHeaders($headers);

        $requestUrl = $url;
        if ($method === 'GET' && ! empty($query)) {
            $requestUrl .= '?' . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
        }

        $response = match ($method) {
            'GET' => $client->get($requestUrl),
            'POST' => $client->post($requestUrl, ! empty($body) ? $body : (object) []),
            'PUT' => $client->put($requestUrl, $body),
            'PATCH' => $client->patch($requestUrl, $body),
            'DELETE' => $client->delete($requestUrl, $query),
            default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
        };

        $status = $response->status();
        $rawBody = $response->body();

        if (! $response->successful()) {
            $this->handleError($status, $rawBody, $url, $method, $desc);
        }

        $json = $response->json();

        return is_array($json) ? $json : [];
    }

    /**
     * Обработка ошибок API
     */
    private function handleError(int $status, string $rawBody, string $url, string $method, string $desc): void
    {
        $errorInfo = json_decode($rawBody, true) ?? ['raw' => mb_substr($rawBody, 0, 500)];

        Log::error("Uzum API error [{$status}]: {$desc}", [
            'account_id' => $this->account->id,
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'error' => $errorInfo,
        ]);

        $message = match (true) {
            $status === 400 => 'Неверные параметры запроса: ' . ($errorInfo['message'] ?? $rawBody),
            $status === 401 => 'Неверный токен. Проверьте API-ключ в настройках.',
            $status === 403 => 'Доступ запрещён. Проверьте права токена.',
            $status === 429 => 'Слишком много запросов. Подождите минуту.',
            $status >= 500 => 'Сервер Uzum временно недоступен. Попробуйте позже.',
            default => "Ошибка API ({$status}): " . mb_substr($rawBody, 0, 200),
        };

        throw new \RuntimeException($message);
    }

    /**
     * Очистка заголовков от лишних пробелов
     */
    private function trimHeaders(array $headers): array
    {
        return array_map(fn ($v) => is_string($v) ? trim($v) : $v, $headers);
    }

    /**
     * Скрытие секретов в логах
     */
    private function sanitize(array $data): array
    {
        $sensitive = ['token', 'api_key', 'authorization', 'secret', 'password'];
        array_walk_recursive($data, function (&$value, $key) use ($sensitive) {
            foreach ($sensitive as $needle) {
                if (stripos((string) $key, $needle) !== false) {
                    $value = '***';
                    break;
                }
            }
        });

        return $data;
    }

    /**
     * Получить аккаунт маркетплейса
     */
    public function getAccount(): MarketplaceAccount
    {
        return $this->account;
    }
}
