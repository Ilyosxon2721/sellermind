<?php
// file: app/Services/Marketplaces/Wildberries/WildberriesAnalyticsService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for Wildberries analytics and reports
 *
 * WB Analytics API:
 * - POST /api/analytics/v3/sales-funnel/products - Sales funnel by products
 * - POST /api/analytics/v3/sales-funnel/products/history - Sales funnel history
 * - POST /api/analytics/v3/sales-funnel/grouped/history - Grouped sales funnel
 * - GET /api/v2/search-report/report - Search analytics main page
 * - GET /api/v2/search-report/product/search-texts - Search texts by product
 * - GET /api/v2/stocks-report/products/groups - Stocks report by groups
 * - GET /api/v1/analytics/excise-report - Excise report
 * - GET /api/v1/analytics/antifraud-details - Self-buyouts (antifraud)
 * - GET /api/v1/analytics/incorrect-attachments - Incorrect products
 * - GET /api/v1/analytics/banned-products/blocked - Blocked products
 * - GET /api/v1/analytics/banned-products/shadowed - Shadowed products
 */
class WildberriesAnalyticsService
{
    protected WildberriesHttpClient $httpClient;

    public function __construct(WildberriesHttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * Get sales funnel statistics for products (period)
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param array $nmIds Product IDs (optional)
     * @return array
     */
    public function getSalesFunnel(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo,
        array $nmIds = []
    ): array {
        try {
            $payload = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            if (!empty($nmIds)) {
                $payload['nmIDs'] = array_map('intval', $nmIds);
            }

            $response = $this->httpClient->post('analytics', '/api/analytics/v3/sales-funnel/products', $payload);

            Log::info('WB sales funnel fetched', [
                'account_id' => $account->id,
                'date_from' => $dateFrom->format('Y-m-d'),
                'date_to' => $dateTo->format('Y-m-d'),
                'products_count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB sales funnel', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get sales funnel history by days
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param array $nmIds Product IDs
     * @return array
     */
    public function getSalesFunnelHistory(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo,
        array $nmIds
    ): array {
        try {
            $payload = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
                'nmIDs' => array_map('intval', $nmIds),
            ];

            $response = $this->httpClient->post('analytics', '/api/analytics/v3/sales-funnel/products/history', $payload);

            Log::info('WB sales funnel history fetched', [
                'account_id' => $account->id,
                'nm_ids' => count($nmIds),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB sales funnel history', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get grouped sales funnel history
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @param string $groupBy Grouping: 'brand', 'subject', 'vendor_code'
     * @return array
     */
    public function getGroupedSalesFunnelHistory(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo,
        string $groupBy = 'brand'
    ): array {
        try {
            $payload = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
                'groupBy' => $groupBy,
            ];

            $response = $this->httpClient->post('analytics', '/api/analytics/v3/sales-funnel/grouped/history', $payload);

            Log::info('WB grouped sales funnel history fetched', [
                'account_id' => $account->id,
                'group_by' => $groupBy,
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB grouped sales funnel history', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get search analytics report (main page)
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function getSearchReport(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        try {
            $params = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            $response = $this->httpClient->get('analytics', '/api/v2/search-report/report', $params);

            Log::info('WB search report fetched', [
                'account_id' => $account->id,
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB search report', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get search texts for specific product
     *
     * @param MarketplaceAccount $account
     * @param int $nmId Product ID
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function getProductSearchTexts(
        MarketplaceAccount $account,
        int $nmId,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        try {
            $params = [
                'nmID' => $nmId,
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            $response = $this->httpClient->get('analytics', '/api/v2/search-report/product/search-texts', $params);

            Log::info('WB product search texts fetched', [
                'account_id' => $account->id,
                'nm_id' => $nmId,
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB product search texts', [
                'account_id' => $account->id,
                'nm_id' => $nmId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get stocks report by product groups
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function getStocksReportGroups(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        try {
            $params = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            $response = $this->httpClient->get('analytics', '/api/v2/stocks-report/products/groups', $params);

            Log::info('WB stocks report groups fetched', [
                'account_id' => $account->id,
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB stocks report groups', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get excise goods report
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function getExciseReport(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        try {
            $params = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            $response = $this->httpClient->get('analytics', '/api/v1/analytics/excise-report', $params);

            Log::info('WB excise report fetched', [
                'account_id' => $account->id,
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB excise report', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get antifraud details (self-buyouts)
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function getAntifraudDetails(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        try {
            $params = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            $response = $this->httpClient->get('analytics', '/api/v1/analytics/antifraud-details', $params);

            Log::info('WB antifraud details fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB antifraud details', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get incorrect attachments report (product substitution)
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function getIncorrectAttachments(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        try {
            $params = [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'dateTo' => $dateTo->format('Y-m-d'),
            ];

            $response = $this->httpClient->get('analytics', '/api/v1/analytics/incorrect-attachments', $params);

            Log::info('WB incorrect attachments fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB incorrect attachments', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get blocked products
     *
     * @param MarketplaceAccount $account
     * @return array
     */
    public function getBlockedProducts(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('analytics', '/api/v1/analytics/banned-products/blocked');

            Log::info('WB blocked products fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB blocked products', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get shadowed products (hidden from catalog)
     *
     * @param MarketplaceAccount $account
     * @return array
     */
    public function getShadowedProducts(MarketplaceAccount $account): array
    {
        try {
            $response = $this->httpClient->get('analytics', '/api/v1/analytics/banned-products/shadowed');

            Log::info('WB shadowed products fetched', [
                'account_id' => $account->id,
                'count' => count($response ?? []),
            ]);

            return $response ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB shadowed products', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get comprehensive analytics dashboard data
     *
     * @param MarketplaceAccount $account
     * @param Carbon $dateFrom
     * @param Carbon $dateTo
     * @return array
     */
    public function getDashboardData(
        MarketplaceAccount $account,
        Carbon $dateFrom,
        Carbon $dateTo
    ): array {
        $dashboard = [];

        // Sales funnel
        try {
            $dashboard['sales_funnel'] = $this->getSalesFunnel($account, $dateFrom, $dateTo);
        } catch (\Exception $e) {
            $dashboard['sales_funnel'] = ['error' => $e->getMessage()];
        }

        // Stocks report
        try {
            $dashboard['stocks'] = $this->getStocksReportGroups($account, $dateFrom, $dateTo);
        } catch (\Exception $e) {
            $dashboard['stocks'] = ['error' => $e->getMessage()];
        }

        // Antifraud (self-buyouts)
        try {
            $dashboard['antifraud'] = $this->getAntifraudDetails($account, $dateFrom, $dateTo);
        } catch (\Exception $e) {
            $dashboard['antifraud'] = ['error' => $e->getMessage()];
        }

        // Product issues
        try {
            $dashboard['blocked_products'] = $this->getBlockedProducts($account);
            $dashboard['shadowed_products'] = $this->getShadowedProducts($account);
            $dashboard['incorrect_attachments'] = $this->getIncorrectAttachments($account, $dateFrom, $dateTo);
        } catch (\Exception $e) {
            $dashboard['product_issues'] = ['error' => $e->getMessage()];
        }

        Log::info('WB dashboard data compiled', [
            'account_id' => $account->id,
        ]);

        return $dashboard;
    }
}
