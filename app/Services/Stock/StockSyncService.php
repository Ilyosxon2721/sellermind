<?php

namespace App\Services\Stock;

use App\Models\MarketplaceAccount;
use App\Models\ProductVariant;
use App\Models\StockSyncLog;
use App\Models\VariantMarketplaceLink;
use App\Services\Marketplaces\OzonClient;
use App\Services\Marketplaces\UzumClient;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use Illuminate\Support\Facades\Log;

/**
 * Сервис синхронизации остатков между системой и маркетплейсами
 */
class StockSyncService
{
    public function __construct(
        protected YandexMarketClient $ymClient,
        protected OzonClient $ozonClient,
        protected UzumClient $uzumClient
    ) {}

    /**
     * Синхронизировать остаток одного варианта на все связанные маркетплейсы
     */
    public function syncVariantStock(ProductVariant $variant): array
    {
        $results = [];
        $currentStock = $variant->getCurrentStock();

        $links = $variant->activeMarketplaceLinks()
            ->where('sync_stock_enabled', true)
            ->with('account')
            ->get();

        foreach ($links as $link) {
            try {
                $result = $this->syncLinkStock($link, $currentStock);
                $results[] = [
                    'link_id' => $link->id,
                    'marketplace' => $link->account->marketplace,
                    'success' => true,
                    'stock' => $currentStock,
                ];
            } catch (\Exception $e) {
                Log::error('Stock sync failed', [
                    'link_id' => $link->id,
                    'error' => $e->getMessage(),
                ]);
                
                $link->markFailed($e->getMessage());
                
                $results[] = [
                    'link_id' => $link->id,
                    'marketplace' => $link->account->marketplace,
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Синхронизировать остаток для одной связи
     */
    public function syncLinkStock(VariantMarketplaceLink $link, ?int $stock = null): array
    {
        $stock = $stock ?? $link->getCurrentStock();
        $stockBefore = $link->last_stock_synced ?? 0;
        $account = $link->account;

        // Выбор клиента в зависимости от маркетплейса
        $result = match ($account->marketplace) {
            'yandex_market', 'ym' => $this->syncToYandexMarket($account, $link, $stock),
            'ozon' => $this->syncToOzon($account, $link, $stock),
            'wildberries', 'wb' => $this->syncToWildberries($account, $link, $stock),
            'uzum' => $this->syncToUzum($account, $link, $stock),
            default => throw new \RuntimeException("Неподдерживаемый маркетплейс: {$account->marketplace}"),
        };

        // Обновить статус связи
        $link->markSynced($stock);

        // Записать лог
        StockSyncLog::logSuccess($link, 'push', $stockBefore, $stock, $result['request'] ?? null, $result['response'] ?? null);

        return $result;
    }

    /**
     * Синхронизация остатков в Yandex Market
     */
    protected function syncToYandexMarket(MarketplaceAccount $account, VariantMarketplaceLink $link, int $stock): array
    {
        $offerId = $link->external_offer_id ?? $link->marketplaceProduct->external_offer_id;
        
        if (!$offerId) {
            throw new \RuntimeException('Не указан offerId для товара');
        }

        return $this->ymClient->updateStock($account, $offerId, $stock);
    }

    /**
     * Синхронизация остатков в Ozon
     */
    protected function syncToOzon(MarketplaceAccount $account, VariantMarketplaceLink $link, int $stock): array
    {
        $offerId = $link->external_offer_id ?? $link->marketplaceProduct->external_offer_id;

        if (!$offerId) {
            throw new \RuntimeException('Не указан offer_id для товара');
        }

        $credentials = $account->credentials_json ?? [];
        $syncMode = $credentials['stock_sync_mode'] ?? 'basic';
        
        if ($syncMode === 'aggregated') {
            // Aggregated mode: update with aggregated stock to single warehouse
            $warehouseId = $credentials['warehouse_id'] ?? null;
            
            if (!$warehouseId) {
                throw new \RuntimeException('Для суммированной синхронизации не указан целевой склад Ozon');
            }
            
            Log::info('Ozon stock sync - aggregated mode', [
                'offer_id' => $offerId,
                'stock' => $stock,
                'warehouse_id' => $warehouseId,
                'link_id' => $link->id,
            ]);
            
            return $this->ozonClient->updateStock($account, $offerId, $stock, $warehouseId);
        } else {
            // Basic mode: update specific warehouse or default
            $warehouseId = $credentials['warehouse_id'] ?? null;
            
            Log::info('Ozon stock sync - basic mode', [
                'offer_id' => $offerId,
                'stock' => $stock,
                'warehouse_id' => $warehouseId,
                'link_id' => $link->id,
            ]);
            
            return $this->ozonClient->updateStock($account, $offerId, $stock, $warehouseId);
        }
    }

    /**
     * Синхронизация остатков в Wildberries
     */
    protected function syncToWildberries(MarketplaceAccount $account, VariantMarketplaceLink $link, int $stock): array
    {
        // For Wildberries, use the barcode stored in the link (specific WB characteristic)
        // This is CRITICAL because one nmID can have multiple characteristics (sizes/colors) with different barcodes
        $barcode = $link->external_sku;
        
        if (!$barcode) {
            throw new \RuntimeException('У связи не указан barcode (external_sku). Перепривяжите товар для сохранения корректного баркода.');
        }
        
        // Validate that it looks like a barcode (numeric)
        if (!is_numeric($barcode) && !preg_match('/^\d+$/', $barcode)) {
            // Fallback for old links that might have supplier_article instead
            Log::warning('Link has non-numeric external_sku, attempting fallback', [
                'link_id' => $link->id,
                'external_sku' => $barcode,
                'marketplace_product_id' => $link->marketplace_product_id,
            ]);
            
            // Try to find the correct barcode from WildberriesProduct
            $wbProduct = \App\Models\WildberriesProduct::find($link->marketplace_product_id);
            if ($wbProduct && $wbProduct->barcode) {
                $barcode = $wbProduct->barcode;
                Log::info('Using barcode from WildberriesProduct as fallback', ['barcode' => $barcode]);
            } else {
                throw new \RuntimeException("Невозможно определить barcode для синхронизации. Перепривяжите товар.");
            }
        }

        $wbStockService = new \App\Services\Marketplaces\Wildberries\WildberriesStockService();
        $syncMode = $account->credentials_json['sync_mode'] ?? 'basic';
        
        if ($syncMode === 'aggregated') {
            // Aggregated mode: update single target warehouse with aggregated stock
            $warehouseId = $account->credentials_json['warehouse_id'] ?? null;
            
            if (!$warehouseId) {
                throw new \RuntimeException('Для суммированной синхронизации не указан целевой склад WB');
            }
            
            Log::info('Wildberries stock sync - aggregated mode', [
                'barcode' => $barcode,
                'stock' => $stock,
                'warehouse_id' => $warehouseId,
                'link_id' => $link->id,
            ]);
            
            return $wbStockService->updateStock($account, $barcode, $stock, $warehouseId);
        } else {
            // Basic mode: update each mapped WB warehouse with stock from corresponding internal warehouse
            $mappings = \App\Models\MarketplaceWarehouse::where('marketplace_account_id', $account->id)
                ->where('is_active', true)
                ->whereNotNull('marketplace_warehouse_id')
                ->whereNotNull('local_warehouse_id')
                ->get();
            
            if ($mappings->isEmpty()) {
                throw new \RuntimeException('Нет активных маппингов складов для базовой синхронизации. Настройте связи складов в настройках WB.');
            }
            
            $results = [];
            foreach ($mappings as $mapping) {
                try {
                    $result = $wbStockService->updateStock($account, $barcode, $stock, $mapping->marketplace_warehouse_id);
                    $results[] = $result;
                } catch (\Exception $e) {
                    Log::warning('Failed to update stock for WB warehouse', [
                        'mapping_id' => $mapping->id,
                        'wb_warehouse_id' => $mapping->marketplace_warehouse_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
            
            // Return first successful result or last result
            return !empty($results) ? $results[0] : ['success' => false, 'message' => 'No warehouses updated'];
        }
    }

    /**
     * Синхронизация остатков в Uzum
     */
    protected function syncToUzum(MarketplaceAccount $account, VariantMarketplaceLink $link, int $stock): array
    {
        // For Uzum, we need both the product ID and the SKU ID for SKU-level stock updates
        $productId = $link->marketplaceProduct->external_product_id;
        $skuId = $link->external_sku_id;
        
        if (!$productId) {
            throw new \RuntimeException('Не указан external_product_id для товара Uzum');
        }
        
        if (!$skuId) {
            throw new \RuntimeException('Не указан external_sku_id для SKU Uzum. Невозможно синхронизировать остаток на уровне SKU.');
        }

        return $this->uzumClient->updateStock($account, $productId, $skuId, $stock);
    }

    /**
     * Синхронизировать все остатки для компании
     */
    public function syncAllStocksForCompany(int $companyId): array
    {
        $links = VariantMarketplaceLink::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('sync_stock_enabled', true)
            ->with(['variant', 'account'])
            ->get();

        $results = [
            'total' => $links->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($links as $link) {
            try {
                $this->syncLinkStock($link);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'link_id' => $link->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Синхронизировать все остатки для аккаунта маркетплейса
     */
    public function syncAllStocksForAccount(MarketplaceAccount $account): array
    {
        $links = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('is_active', true)
            ->where('sync_stock_enabled', true)
            ->with('variant')
            ->get();

        $results = [
            'total' => $links->count(),
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($links as $link) {
            try {
                $this->syncLinkStock($link);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'link_id' => $link->id,
                    'sku' => $link->external_offer_id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Обработать изменение остатка при получении заказа
     */
    public function handleOrderReceived(string $externalOfferId, int $quantity, MarketplaceAccount $account): void
    {
        // Найти связь по external_offer_id
        $link = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('external_offer_id', $externalOfferId)
            ->where('is_active', true)
            ->with('variant')
            ->first();

        if (!$link || !$link->variant) {
            Log::warning('VariantMarketplaceLink not found for order', [
                'external_offer_id' => $externalOfferId,
                'account_id' => $account->id,
            ]);
            return;
        }

        // Уменьшить остаток в системе (это автоматически вызовет событие StockUpdated)
        $link->variant->decrementStock($quantity);
    }
}
