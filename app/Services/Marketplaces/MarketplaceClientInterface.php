<?php
// file: app/Services/Marketplaces/MarketplaceClientInterface.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use DateTimeInterface;

interface MarketplaceClientInterface
{
    /**
     * Get marketplace code (wb, ozon, uzum, ym)
     */
    public function getMarketplaceCode(): string;

    /**
     * Test connection to marketplace API
     *
     * @return array{success: bool, message: string}
     */
    public function testConnection(MarketplaceAccount $account): array;

    /**
     * Sync products catalog (create/update cards, offers)
     *
     * @param MarketplaceAccount $account
     * @param MarketplaceProduct[] $products
     */
    public function syncProducts(MarketplaceAccount $account, array $products): void;

    /**
     * Update prices on marketplace
     *
     * @param MarketplaceAccount $account
     * @param MarketplaceProduct[] $products
     */
    public function syncPrices(MarketplaceAccount $account, array $products): void;

    /**
     * Update stock levels on marketplace
     *
     * @param MarketplaceAccount $account
     * @param MarketplaceProduct[] $products
     */
    public function syncStocks(MarketplaceAccount $account, array $products): void;

    /**
     * Fetch orders from marketplace in given date range
     *
     * @return array Array of order data to be processed
     */
    public function fetchOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to): array;

    /**
     * Get product info from marketplace by external ID
     *
     * @return array|null Product data or null if not found
     */
    public function getProductInfo(MarketplaceAccount $account, string $externalId): ?array;
}
