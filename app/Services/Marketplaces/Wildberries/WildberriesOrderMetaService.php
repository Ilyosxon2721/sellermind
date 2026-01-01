<?php
// file: app/Services/Marketplaces/Wildberries/WildberriesOrderMetaService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing Wildberries order metadata (marking codes, expiration dates, etc.)
 *
 * WB Marketplace API:
 * - GET /api/marketplace/v3/orders/meta - Get order metadata
 * - GET /api/v3/orders/{orderId}/meta - Get specific order metadata
 * - POST /api/v3/orders/{orderId}/meta/sgtin - Attach SGTIN marking code
 * - POST /api/v3/orders/{orderId}/meta/uin - Attach UIN
 * - POST /api/v3/orders/{orderId}/meta/imei - Attach IMEI
 * - POST /api/v3/orders/{orderId}/meta/gtin - Attach GTIN
 * - POST /api/v3/orders/{orderId}/meta/expiration - Attach expiration date
 * - DELETE /api/v3/orders/{orderId}/meta - Delete order metadata
 */
class WildberriesOrderMetaService
{
    protected WildberriesHttpClient $httpClient;

    public function __construct(WildberriesHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get metadata for multiple orders
     *
     * @param MarketplaceAccount $account
     * @param array $orderIds Array of order IDs
     * @return array
     */
    public function getOrdersMeta(MarketplaceAccount $account, array $orderIds = []): array
    {
        try {
            $params = [];
            if (!empty($orderIds)) {
                $params['orders'] = $orderIds;
            }

            $response = $this->httpClient->get('marketplace', '/api/marketplace/v3/orders/meta', $params);

            Log::info('WB orders metadata fetched', [
                'account_id' => $account->id,
                'count' => count($response['orders'] ?? []),
            ]);

            return $response['orders'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB orders metadata', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get metadata for a specific order
     *
     * @param MarketplaceAccount $account
     * @param int $orderId
     * @return array|null
     */
    public function getOrderMeta(MarketplaceAccount $account, int $orderId): ?array
    {
        try {
            $response = $this->httpClient->get('marketplace', "/api/v3/orders/{$orderId}/meta");

            Log::info('WB order metadata fetched', [
                'account_id' => $account->id,
                'order_id' => $orderId,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB order metadata', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Attach SGTIN marking code to order
     * Used for: alcohol, tobacco, perfumes, etc.
     *
     * @param MarketplaceAccount $account
     * @param int $orderId
     * @param string $sgtin SGTIN marking code
     * @return bool
     */
    public function attachSGTIN(MarketplaceAccount $account, int $orderId, string $sgtin): bool
    {
        try {
            $this->httpClient->post('marketplace', "/api/v3/orders/{$orderId}/meta/sgtin", [
                'sgtin' => $sgtin,
            ]);

            Log::info('WB order SGTIN attached', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'sgtin' => $sgtin,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to attach WB order SGTIN', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Attach UIN (Unique Identification Number) to order
     * Used for: furs, jewelry, etc.
     *
     * @param MarketplaceAccount $account
     * @param int $orderId
     * @param string $uin UIN code
     * @return bool
     */
    public function attachUIN(MarketplaceAccount $account, int $orderId, string $uin): bool
    {
        try {
            $this->httpClient->post('marketplace', "/api/v3/orders/{$orderId}/meta/uin", [
                'uin' => $uin,
            ]);

            Log::info('WB order UIN attached', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'uin' => $uin,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to attach WB order UIN', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Attach IMEI to order
     * Used for: electronics, phones, tablets, etc.
     *
     * @param MarketplaceAccount $account
     * @param int $orderId
     * @param string $imei IMEI code
     * @return bool
     */
    public function attachIMEI(MarketplaceAccount $account, int $orderId, string $imei): bool
    {
        try {
            $this->httpClient->post('marketplace', "/api/v3/orders/{$orderId}/meta/imei", [
                'imei' => $imei,
            ]);

            Log::info('WB order IMEI attached', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'imei' => $imei,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to attach WB order IMEI', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Attach GTIN to order
     * Used for: general product identification
     *
     * @param MarketplaceAccount $account
     * @param int $orderId
     * @param string $gtin GTIN code
     * @return bool
     */
    public function attachGTIN(MarketplaceAccount $account, int $orderId, string $gtin): bool
    {
        try {
            $this->httpClient->post('marketplace', "/api/v3/orders/{$orderId}/meta/gtin", [
                'gtin' => $gtin,
            ]);

            Log::info('WB order GTIN attached', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'gtin' => $gtin,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to attach WB order GTIN', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Attach expiration date to order
     * Used for: food products, cosmetics, medicines, etc.
     *
     * @param MarketplaceAccount $account
     * @param int $orderId
     * @param string $expirationDate Date in format YYYY-MM-DD
     * @return bool
     */
    public function attachExpiration(MarketplaceAccount $account, int $orderId, string $expirationDate): bool
    {
        try {
            $this->httpClient->post('marketplace', "/api/v3/orders/{$orderId}/meta/expiration", [
                'expirationDate' => $expirationDate,
            ]);

            Log::info('WB order expiration date attached', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'expiration_date' => $expirationDate,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to attach WB order expiration date', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Delete order metadata
     *
     * @param MarketplaceAccount $account
     * @param int $orderId
     * @return bool
     */
    public function deleteOrderMeta(MarketplaceAccount $account, int $orderId): bool
    {
        try {
            $this->httpClient->delete('marketplace', "/api/v3/orders/{$orderId}/meta");

            Log::info('WB order metadata deleted', [
                'account_id' => $account->id,
                'order_id' => $orderId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete WB order metadata', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Batch attach metadata to multiple orders
     *
     * @param MarketplaceAccount $account
     * @param array $metaData Array of ['order_id' => int, 'type' => 'sgtin|uin|imei|gtin|expiration', 'value' => string]
     * @return array Results with success/error for each order
     */
    public function batchAttachMeta(MarketplaceAccount $account, array $metaData): array
    {
        $results = [];

        foreach ($metaData as $meta) {
            $orderId = $meta['order_id'] ?? null;
            $type = $meta['type'] ?? null;
            $value = $meta['value'] ?? null;

            if (!$orderId || !$type || !$value) {
                $results[] = [
                    'order_id' => $orderId,
                    'success' => false,
                    'error' => 'Missing required fields',
                ];
                continue;
            }

            try {
                $success = match ($type) {
                    'sgtin' => $this->attachSGTIN($account, $orderId, $value),
                    'uin' => $this->attachUIN($account, $orderId, $value),
                    'imei' => $this->attachIMEI($account, $orderId, $value),
                    'gtin' => $this->attachGTIN($account, $orderId, $value),
                    'expiration' => $this->attachExpiration($account, $orderId, $value),
                    default => throw new \InvalidArgumentException("Unknown meta type: {$type}"),
                };

                $results[] = [
                    'order_id' => $orderId,
                    'success' => $success,
                    'type' => $type,
                ];
            } catch (\Exception $e) {
                $results[] = [
                    'order_id' => $orderId,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }
}
