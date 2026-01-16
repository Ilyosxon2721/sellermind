<?php
// file: app/Services/Marketplaces/MarketplaceHttpClient.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;

class MarketplaceHttpClient
{
    protected bool $debugMode;

    public function __construct()
    {
        $this->debugMode = config('app.debug', false);
    }

    /**
     * Make GET request to marketplace API
     */
    public function get(MarketplaceAccount $account, string $url, array $query = []): array
    {
        $this->logRequest($account, 'GET', $url, ['query' => $query]);

        $request = $this->baseRequest($account);

        // Debug: log full URL
        $config = config("marketplaces." . $this->getConfigKey($account->marketplace));
        $fullUrl = ($config['base_url'] ?? '') . $url;
        \Log::debug('Making HTTP GET request', [
            'base_url' => $config['base_url'] ?? '',
            'path' => $url,
            'full_url' => $fullUrl,
            'query' => $query,
        ]);

        $response = $request->get($url, $query);

        return $this->handleResponse($response, $account, 'GET', $url);
    }

    /**
     * Make POST request to marketplace API
     */
    public function post(MarketplaceAccount $account, string $url, array|object $payload = []): array
    {
        $this->logRequest($account, 'POST', $url, ['payload' => (array) $payload]);

        // For Ozon: empty array [] becomes "[]" in JSON, but API expects "{}" (empty object)
        // Use object for empty payloads to send proper JSON object
        $body = (is_array($payload) && empty($payload)) ? new \stdClass() : $payload;

        $response = $this->baseRequest($account)
            ->post($url, $body);

        return $this->handleResponse($response, $account, 'POST', $url);
    }

    /**
     * Make PUT request to marketplace API
     */
    public function put(MarketplaceAccount $account, string $url, array $payload = []): array
    {
        $this->logRequest($account, 'PUT', $url, ['payload' => $payload]);

        $response = $this->baseRequest($account)
            ->put($url, $payload);

        return $this->handleResponse($response, $account, 'PUT', $url);
    }

    /**
     * Make PATCH request to marketplace API
     */
    public function patch(MarketplaceAccount $account, string $url, array $payload = []): array
    {
        $this->logRequest($account, 'PATCH', $url, ['payload' => $payload]);

        $response = $this->baseRequest($account)
            ->patch($url, $payload);

        return $this->handleResponse($response, $account, 'PATCH', $url);
    }

    /**
     * Make DELETE request to marketplace API
     */
    public function delete(MarketplaceAccount $account, string $url): array
    {
        $this->logRequest($account, 'DELETE', $url);

        $response = $this->baseRequest($account)
            ->delete($url);

        return $this->handleResponse($response, $account, 'DELETE', $url);
    }

    /**
     * Build base request with authentication
     */
    protected function baseRequest(MarketplaceAccount $account): PendingRequest
    {
        $marketplace = $account->marketplace;
        $configKey = $this->getConfigKey($marketplace);
        $config = config("marketplaces.{$configKey}");

        if (!$config) {
            throw new \InvalidArgumentException("No config found for marketplace: {$marketplace}");
        }

        $request = Http::timeout(30)
            ->baseUrl($config['base_url'] ?? '');

        // Apply authentication based on marketplace type
        $request = $this->applyAuthentication($request, $account, $config);

        // Force Russian locale for Uzum to avoid Uzbek titles/categories by default
        if ($account->marketplace === 'uzum') {
            $request = $request->withHeaders(['Accept-Language' => 'ru']);
        }

        return $request;
    }

    /**
     * Apply authentication headers based on marketplace config
     */
    protected function applyAuthentication(PendingRequest $request, MarketplaceAccount $account, array $config): PendingRequest
    {
        $credentials = $account->getAllCredentials();
        $authType = $config['auth_type'] ?? 'api_key';

        switch ($authType) {
            case 'api_key':
                // Single API key in header (Wildberries, Uzum)
                $header = $config['auth_header'] ?? 'Authorization';
                $prefix = $config['auth_prefix'] ?? '';

                // For Wildberries: use category-specific token or fallback to api_key
                if ($account->marketplace === 'wb') {
                    // Determine which WB token to use based on base_url
                    $baseUrl = $config['base_url'] ?? '';
                    if (str_contains($baseUrl, 'marketplace-api')) {
                        $apiKey = $credentials['wb_marketplace_token'] ?? $credentials['api_key'] ?? '';
                    } elseif (str_contains($baseUrl, 'content-api')) {
                        $apiKey = $credentials['wb_content_token'] ?? $credentials['api_key'] ?? '';
                    } elseif (str_contains($baseUrl, 'discounts-prices-api')) {
                        $apiKey = $credentials['wb_prices_token'] ?? $credentials['api_key'] ?? '';
                    } elseif (str_contains($baseUrl, 'statistics-api')) {
                        $apiKey = $credentials['wb_statistics_token'] ?? $credentials['api_key'] ?? '';
                    } else {
                        $apiKey = $credentials['api_key'] ?? '';
                    }
                } elseif ($account->marketplace === 'uzum') {
                    $apiKey = $credentials['uzum_access_token']
                        ?? $credentials['uzum_api_key']
                        ?? $credentials['api_key']
                        ?? '';

                    // Debug: log what we're sending
                    \Log::debug('Uzum auth debug', [
                        'account_id' => $account->id,
                        'header' => $header,
                        'prefix' => $prefix,
                        'prefix_from_config' => $config['auth_prefix'] ?? 'NOT_SET',
                        'api_key_length' => strlen($apiKey),
                        'api_key_preview' => $apiKey ? (substr($apiKey, 0, 8) . '...' . substr($apiKey, -4)) : 'EMPTY',
                        'has_uzum_access_token' => !empty($credentials['uzum_access_token']),
                        'has_uzum_api_key' => !empty($credentials['uzum_api_key']),
                        'has_api_key' => !empty($credentials['api_key']),
                    ]);
                } else {
                    $apiKey = $credentials['api_key'] ?? '';
                }

                if ($prefix) {
                    $request = $request->withHeaders([$header => trim("{$prefix} {$apiKey}")]);
                } else {
                    $request = $request->withHeaders([$header => $apiKey]);
                }
                break;

            case 'client_credentials':
                // Client ID + API Key (Ozon)
                $headers = $config['auth_headers'] ?? [];
                if (isset($headers['client_id'])) {
                    $request = $request->withHeaders([
                        $headers['client_id'] => $credentials['client_id'] ?? '',
                        $headers['api_key'] => $credentials['api_key'] ?? '',
                    ]);
                }
                break;

            case 'oauth':
                // OAuth token (Yandex Market)
                $header = $config['auth_header'] ?? 'Authorization';
                $prefix = $config['auth_prefix'] ?? 'OAuth oauth_token=';
                $token = $credentials['oauth_token'] ?? '';

                $request = $request->withHeaders([$header => "{$prefix}{$token}"]);
                break;
        }

        return $request;
    }

    /**
     * Log outgoing request (debug mode)
     */
    protected function logRequest(MarketplaceAccount $account, string $method, string $url, array $options = []): void
    {
        if (!$this->debugMode) {
            return;
        }

        Log::channel('daily')->debug('Marketplace API Request', [
            'marketplace' => $account->marketplace,
            'account_id' => $account->id,
            'method' => $method,
            'url' => $url,
            'options' => $this->sanitizeForLog($options),
        ]);
    }

    /**
     * Handle response and log errors
     */
    protected function handleResponse(Response $response, MarketplaceAccount $account, string $method, string $url): array
    {
        $statusCode = $response->status();
        $body = $response->body();

        // Log successful response in debug mode
        if ($this->debugMode) {
            Log::channel('daily')->debug('Marketplace API Response', [
                'marketplace' => $account->marketplace,
                'account_id' => $account->id,
                'method' => $method,
                'url' => $url,
                'status' => $statusCode,
                'body' => mb_substr($body, 0, 2000),
            ]);
        }

        if ($response->successful()) {
            return $response->json() ?? [];
        }

        // Log error
        Log::channel('daily')->error('Marketplace API Error', [
            'marketplace' => $account->marketplace,
            'account_id' => $account->id,
            'method' => $method,
            'url' => $url,
            'status' => $statusCode,
            'error' => mb_substr($body, 0, 1000),
        ]);

        throw new \RuntimeException(
            "Marketplace API error ({$statusCode}): " . mb_substr($body, 0, 500)
        );
    }

    /**
     * Sanitize options for logging (hide sensitive data)
     */
    protected function sanitizeForLog(array $options): array
    {
        $sensitiveKeys = ['api_key', 'token', 'password', 'secret', 'Authorization'];

        array_walk_recursive($options, function (&$value, $key) use ($sensitiveKeys) {
            foreach ($sensitiveKeys as $sensitive) {
                if (stripos($key, $sensitive) !== false) {
                    $value = '***';
                    break;
                }
            }
        });

        return $options;
    }

    /**
     * Get config key from marketplace code
     */
    protected function getConfigKey(string $marketplace): string
    {
        return match ($marketplace) {
            'wb' => 'wildberries',
            'ym' => 'yandex_market',
            default => $marketplace,
        };
    }

    /**
     * Get marketplace config
     */
    public function getConfig(string $marketplace): array
    {
        $configKey = $this->getConfigKey($marketplace);
        return config("marketplaces.{$configKey}", []);
    }
}
