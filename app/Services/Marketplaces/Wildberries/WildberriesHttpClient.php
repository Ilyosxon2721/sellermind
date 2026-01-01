<?php
// file: app/Services/Marketplaces/Wildberries/WildberriesHttpClient.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

/**
 * HTTP client for Wildberries API with support for different API categories.
 *
 * WB uses different domains for different API categories:
 * - content: Content API (cards, media)
 * - marketplace: Marketplace API (orders, supplies)
 * - prices: Prices & Discounts API
 * - statistics: Statistics API (reports)
 * - common: Common API (ping, info)
 */
class WildberriesHttpClient
{
    protected MarketplaceAccount $account;
    protected bool $debugMode;

    public function __construct(MarketplaceAccount $account)
    {
        $this->account = $account;
        $this->debugMode = config('app.debug', false);
    }

    /**
     * Make GET request to WB API
     *
     * @param string $category API category (content, marketplace, prices, statistics, common)
     * @param string $uri Endpoint URI
     * @param array $query Query parameters
     */
    public function get(string $category, string $uri, array $query = []): array
    {
        return $this->request($category, 'GET', $uri, ['query' => $query]);
    }

    /**
     * Make POST request to WB API
     */
    public function post(string $category, string $uri, array $payload = []): array
    {
        return $this->request($category, 'POST', $uri, ['json' => $payload]);
    }

    /**
     * Make PUT request to WB API
     */
    public function put(string $category, string $uri, array $payload = []): array
    {
        return $this->request($category, 'PUT', $uri, ['json' => $payload]);
    }

    /**
     * Make PATCH request to WB API
     */
    public function patch(string $category, string $uri, array $payload = []): array
    {
        return $this->request($category, 'PATCH', $uri, ['json' => $payload]);
    }

    /**
     * Make DELETE request to WB API
     */
    public function delete(string $category, string $uri): array
    {
        return $this->request($category, 'DELETE', $uri);
    }

    /**
     * Make GET request and return binary response (for files, images, etc.)
     *
     * @param string $category API category
     * @param string $uri Endpoint URI
     * @param array $query Query parameters
     * @return string Binary content
     * @throws \RuntimeException
     */
    public function getBinary(string $category, string $uri, array $query = []): string
    {
        $client = $this->baseClient($category);

        // Log request
        $this->logRequest($category, 'GET', $uri, ['query' => $query]);

        $startTime = microtime(true);

        try {
            $response = $client->get($uri, $query);
        } catch (\Exception $e) {
            $this->logError($category, 'GET', $uri, $e->getMessage());
            throw new \RuntimeException("WB API request failed: {$e->getMessage()}", 0, $e);
        }

        $duration = round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            $this->logError($category, 'GET', $uri, "HTTP {$response->status()}: {$response->body()}");
            throw new \RuntimeException("Wildberries API error (HTTP {$response->status()}): " . $response->body());
        }

        Log::info('WB API binary response received', [
            'category' => $category,
            'method' => 'GET',
            'uri' => $uri,
            'status' => $response->status(),
            'size' => strlen($response->body()),
            'duration_ms' => $duration,
        ]);

        return $response->body();
    }

    /**
     * Make POST request and return binary response (for stickers, barcodes, etc.)
     *
     * @param string $category API category
     * @param string $uri Endpoint URI
     * @param array $payload Request body
     * @param array $query Query parameters
     * @return string Binary content
     * @throws \RuntimeException
     */
    public function postBinary(string $category, string $uri, array $payload = [], array $query = []): string
    {
        $client = $this->baseClient($category);

        // Log request
        $this->logRequest($category, 'POST', $uri, ['json' => $payload, 'query' => $query]);

        $startTime = microtime(true);

        try {
            // Combine payload and query parameters
            $fullUrl = $uri;
            if (!empty($query)) {
                $fullUrl .= '?' . http_build_query($query);
            }

            $response = $client->post($fullUrl, $payload);
        } catch (\Exception $e) {
            $this->logError($category, 'POST', $uri, $e->getMessage());
            throw new \RuntimeException("WB API request failed: {$e->getMessage()}", 0, $e);
        }

        $duration = round((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            $this->logError($category, 'POST', $uri, "HTTP {$response->status()}: {$response->body()}");
            throw new \RuntimeException("Wildberries API error (HTTP {$response->status()}): " . $response->body());
        }

        Log::info('WB API binary response received (POST)', [
            'category' => $category,
            'method' => 'POST',
            'uri' => $uri,
            'status' => $response->status(),
            'size' => strlen($response->body()),
            'content_type' => $response->header('Content-Type'),
            'duration_ms' => $duration,
        ]);

        return $response->body();
    }

    /**
     * General request method with logging and error handling
     *
     * @throws \RuntimeException
     */
    public function request(string $category, string $method, string $uri, array $options = []): array
    {
        $client = $this->baseClient($category);

        // Log request
        $this->logRequest($category, $method, $uri, $options);

        // Make request
        $startTime = microtime(true);

        try {
            $response = match (strtoupper($method)) {
                'GET' => $client->get($uri, $options['query'] ?? []),
                'POST' => $client->post($uri, $options['json'] ?? []),
                'PUT' => $client->put($uri, $options['json'] ?? []),
                'PATCH' => $client->patch($uri, $options['json'] ?? []),
                'DELETE' => $client->delete($uri),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };
        } catch (\Exception $e) {
            $this->logError($category, $method, $uri, $e->getMessage());
            throw new \RuntimeException("WB API request failed: {$e->getMessage()}", 0, $e);
        }

        $duration = round((microtime(true) - $startTime) * 1000);

        return $this->handleResponse($response, $category, $method, $uri, $duration);
    }

    /**
     * Build base HTTP client for specified API category
     */
    protected function baseClient(string $category): PendingRequest
    {
        $config = config('wildberries');

        $useSandbox = (bool)($config['sandbox'] ?? false);
        $baseUrls = $useSandbox
            ? ($config['sandbox_base_urls'] ?? [])
            : ($config['base_urls'] ?? []);

        $baseUrl = $baseUrls[$category] ?? $baseUrls['common'] ?? 'https://common-api.wildberries.ru';

        // Get token: account-level first, then fallback to api_key, then config default
        $token = $this->account->getWbToken($category)
            ?: $this->account->api_key
            ?: ($config['tokens'][$category] ?? null);

        if (!$token) {
            throw new \RuntimeException("No API token available for WB category: {$category}. Please configure wb_marketplace_token or api_key for account ID {$this->account->id}");
        }

        return Http::withHeaders([
            'Authorization' => $token,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
            ->baseUrl($baseUrl)
            ->timeout($config['timeout'] ?? 30);
    }

    /**
     * Handle API response
     *
     * @throws \RuntimeException
     */
    protected function handleResponse(Response $response, string $category, string $method, string $uri, int $durationMs): array
    {
        $status = $response->status();
        $body = $response->body();

        // Log response
        $this->logResponse($category, $method, $uri, $status, $body, $durationMs);

        // Handle rate limiting (429)
        if ($status === 429) {
            $retryAfter = $response->header('X-Ratelimit-Retry') ?? $response->header('Retry-After') ?? 60;

            Log::warning('WB API rate limit exceeded', [
                'account_id' => $this->account->id,
                'category' => $category,
                'retry_after' => $retryAfter,
            ]);

            throw new \RuntimeException("Wildberries rate limit exceeded (429). Retry after: {$retryAfter}s");
        }

        // Handle auth errors (401, 403)
        if ($status === 401 || $status === 403) {
            $this->account->markWbTokensInvalid();

            throw new \RuntimeException("Wildberries auth error ({$status}): Token may be invalid or expired");
        }

        // Handle other errors
        if ($status < 200 || $status >= 300) {
            throw new \RuntimeException("Wildberries API error (HTTP {$status}): " . mb_substr($body, 0, 500));
        }

        // Mark tokens as valid after successful request
        $this->account->markWbTokensValid();

        // Parse JSON
        $json = $response->json();

        if (!is_array($json) && $json !== null) {
            throw new \RuntimeException('Wildberries API returned invalid JSON');
        }

        return $json ?? [];
    }

    /**
     * Ping API to check connectivity
     */
    public function ping(string $category = 'common'): array
    {
        // Different categories have different ping endpoints
        $pingEndpoints = [
            'common' => '/ping',
            'content' => '/ping',
            'marketplace' => '/ping',
            'prices' => '/ping',
            'statistics' => '/api/v1/supplier/stocks', // Statistics doesn't have ping, use lightweight endpoint
        ];

        $endpoint = $pingEndpoints[$category] ?? '/ping';

        try {
            // For statistics, we need dateFrom parameter
            if ($category === 'statistics') {
                $result = $this->get($category, $endpoint, [
                    'dateFrom' => now()->subDay()->format('Y-m-d'),
                ]);
            } else {
                $result = $this->get($category, $endpoint);
            }

            return [
                'success' => true,
                'category' => $category,
                'message' => 'API доступен',
                'data' => $result,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'category' => $category,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Log outgoing request
     */
    protected function logRequest(string $category, string $method, string $uri, array $options): void
    {
        if (!$this->debugMode) {
            return;
        }

        Log::channel('daily')->debug('WB API Request', [
            'account_id' => $this->account->id,
            'category' => $category,
            'method' => $method,
            'uri' => $uri,
            'options' => $this->sanitizeForLog($options),
        ]);
    }

    /**
     * Log API response
     */
    protected function logResponse(string $category, string $method, string $uri, int $status, string $body, int $durationMs): void
    {
        if (!$this->debugMode) {
            return;
        }

        Log::channel('daily')->debug('WB API Response', [
            'account_id' => $this->account->id,
            'category' => $category,
            'method' => $method,
            'uri' => $uri,
            'status' => $status,
            'duration_ms' => $durationMs,
            'body' => mb_substr($body, 0, 2000),
        ]);
    }

    /**
     * Log error
     */
    protected function logError(string $category, string $method, string $uri, string $error): void
    {
        Log::channel('daily')->error('WB API Error', [
            'account_id' => $this->account->id,
            'category' => $category,
            'method' => $method,
            'uri' => $uri,
            'error' => $error,
        ]);
    }

    /**
     * Sanitize data for logging (hide sensitive info)
     */
    protected function sanitizeForLog(array $data): array
    {
        $sensitiveKeys = ['Authorization', 'token', 'api_key', 'password', 'secret'];

        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $value = '***';
                    break;
                }
            }
        });

        return $data;
    }

    /**
     * Get account
     */
    public function getAccount(): MarketplaceAccount
    {
        return $this->account;
    }
}
