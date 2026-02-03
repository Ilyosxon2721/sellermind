<?php

namespace App\Services\Marketplaces\YandexMarket;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * HTTP клиент для Yandex Market Partner API
 */
class YandexMarketHttpClient
{
    protected string $baseUrl;

    protected int $timeout;

    protected int $retryAttempts;

    public function __construct()
    {
        $this->baseUrl = config('marketplaces.yandex_market.base_url', 'https://api.partner.market.yandex.ru');
        $this->timeout = config('marketplaces.yandex_market.timeout', 30);
        $this->retryAttempts = config('marketplaces.yandex_market.retry_attempts', 3);
    }

    /**
     * Выполнить GET запрос
     */
    public function get(MarketplaceAccount $account, string $path, array $query = []): array
    {
        return $this->request($account, 'GET', $path, $query);
    }

    /**
     * Выполнить POST запрос
     */
    public function post(MarketplaceAccount $account, string $path, array $body = [], array $query = []): array
    {
        return $this->request($account, 'POST', $path, $query, $body);
    }

    /**
     * Выполнить PUT запрос
     */
    public function put(MarketplaceAccount $account, string $path, array $body = [], array $query = []): array
    {
        return $this->request($account, 'PUT', $path, $query, $body);
    }

    /**
     * Выполнить DELETE запрос
     */
    public function delete(MarketplaceAccount $account, string $path, array $query = []): array
    {
        return $this->request($account, 'DELETE', $path, $query);
    }

    /**
     * Выполнить GET запрос и вернуть сырой контент (для PDF, файлов)
     */
    public function getRaw(MarketplaceAccount $account, string $path, array $query = []): string
    {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
        if (! empty($query)) {
            $url .= '?'.http_build_query($query);
        }
        $headers = $this->buildHeaders($account);

        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->get($url);

        if (! $response->successful()) {
            $this->handleError($account, $response->status(), $response->body(), $url, 'GET');
        }

        return $response->body();
    }

    /**
     * Основной метод выполнения запроса
     */
    protected function request(
        MarketplaceAccount $account,
        string $method,
        string $path,
        array $query = [],
        array $body = []
    ): array {
        $url = rtrim($this->baseUrl, '/').'/'.ltrim($path, '/');
        $headers = $this->buildHeaders($account);

        Log::info('YandexMarket API request', [
            'account_id' => $account->id,
            'method' => $method,
            'url' => $url,
            'query' => $this->sanitizeForLog($query),
        ]);

        $client = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->retry($this->retryAttempts, 1000, function ($exception) {
                // Retry only on connection errors or 5xx responses
                return $exception instanceof \Illuminate\Http\Client\ConnectionException
                    || ($exception instanceof \Illuminate\Http\Client\RequestException
                        && $exception->response->status() >= 500);
            });

        try {
            $response = match (strtoupper($method)) {
                'GET' => $client->get($url, $query),
                'POST' => $client->post($url, $body),
                'PUT' => $client->put($url, $body),
                'DELETE' => $client->delete($url, $query),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };

            $status = $response->status();
            $rawBody = $response->body();

            Log::info('YandexMarket API response', [
                'account_id' => $account->id,
                'method' => $method,
                'url' => $url,
                'status' => $status,
                'body_preview' => mb_substr($rawBody, 0, 500),
            ]);

            if (! $response->successful()) {
                $this->handleError($account, $status, $rawBody, $url, $method);
            }

            $json = $response->json();

            return is_array($json) ? $json : [];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('YandexMarket API connection error', [
                'account_id' => $account->id,
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            throw new \RuntimeException("Yandex Market API недоступен: {$e->getMessage()}");
        }
    }

    /**
     * Построить заголовки авторизации
     */
    protected function buildHeaders(MarketplaceAccount $account): array
    {
        $credentials = $account->getDecryptedCredentials();
        $apiKey = $credentials['api_key'] ?? $credentials['oauth_token'] ?? null;

        if (! $apiKey) {
            throw new \RuntimeException('API Key не настроен для аккаунта Yandex Market');
        }

        return [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Api-Key' => $apiKey,
        ];
    }

    /**
     * Обработка ошибок API
     */
    protected function handleError(MarketplaceAccount $account, int $status, string $rawBody, string $url, string $method): void
    {
        $errorInfo = $this->extractError($rawBody);

        Log::warning('YandexMarket API error', [
            'account_id' => $account->id,
            'status' => $status,
            'url' => $url,
            'method' => $method,
            'error' => $errorInfo,
        ]);

        $message = $errorInfo ?: "HTTP {$status}";

        match ($status) {
            401 => throw new \RuntimeException("Yandex Market: Неверный API Key. {$message}"),
            403 => throw new \RuntimeException("Yandex Market: Доступ запрещён. Проверьте права API Key. {$message}"),
            404 => throw new \RuntimeException("Yandex Market: Ресурс не найден. {$message}"),
            429 => throw new \RuntimeException('Yandex Market: Превышен лимит запросов. Повторите позже.'),
            default => throw new \RuntimeException("Yandex Market API error ({$status}): {$message}"),
        };
    }

    /**
     * Извлечь сообщение об ошибке из ответа
     */
    protected function extractError(?string $rawBody): ?string
    {
        if (! $rawBody) {
            return null;
        }

        $json = json_decode($rawBody, true);
        if (! is_array($json)) {
            return mb_substr(trim($rawBody), 0, 200);
        }

        // Yandex Market error format
        if (isset($json['errors']) && is_array($json['errors'])) {
            $errors = array_map(function ($e) {
                return ($e['code'] ?? '').': '.($e['message'] ?? '');
            }, $json['errors']);

            return implode('; ', $errors);
        }

        return $json['message'] ?? $json['error'] ?? null;
    }

    /**
     * Очистить чувствительные данные для логов
     */
    protected function sanitizeForLog(array $data): array
    {
        $sensitive = ['api_key', 'token', 'authorization', 'secret', 'password'];

        array_walk_recursive($data, function (&$value, $key) use ($sensitive) {
            foreach ($sensitive as $needle) {
                if (stripos($key, $needle) !== false) {
                    $value = '***';
                    break;
                }
            }
        });

        return $data;
    }

    /**
     * Получить Campaign ID из аккаунта
     */
    public function getCampaignId(MarketplaceAccount $account): ?string
    {
        return $account->getDecryptedCredentials()['campaign_id'] ?? null;
    }

    /**
     * Получить Business ID из аккаунта
     */
    public function getBusinessId(MarketplaceAccount $account): ?string
    {
        return $account->getDecryptedCredentials()['business_id'] ?? null;
    }
}
