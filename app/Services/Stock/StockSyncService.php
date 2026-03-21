<?php

namespace App\Services\Stock;

use App\Models\MarketplaceAccount;
use App\Models\ProductVariant;
use App\Models\StockSyncLog;
use App\Models\VariantMarketplaceLink;
use App\Services\Marketplaces\OzonClient;
use App\Services\Marketplaces\UzumClient;
use App\Services\Marketplaces\YandexMarket\YandexMarketClient;
use App\Services\Uzum\Api\UzumApiManager;
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
        // Для YM API нужен shopSku (артикул), проверяем сначала external_sku
        $offerId = $link->external_sku ?? $link->external_offer_id ?? $link->marketplaceProduct?->external_offer_id;

        if (! $offerId) {
            throw new \RuntimeException('Не указан offerId/external_sku для товара YM');
        }

        return $this->ymClient->updateStock($account, $offerId, $stock);
    }

    /**
     * Синхронизация остатков в Ozon
     */
    protected function syncToOzon(MarketplaceAccount $account, VariantMarketplaceLink $link, int $stock): array
    {
        // Для Ozon API нужен offer_id (артикул), который хранится в external_sku
        // external_offer_id содержит product_id, который НЕ подходит для API остатков
        $offerId = $link->external_sku ?? $link->external_offer_id ?? $link->marketplaceProduct?->external_offer_id;

        if (! $offerId) {
            throw new \RuntimeException('Не указан offer_id/external_sku для товара Ozon');
        }

        $credentials = $account->credentials_json ?? [];
        $syncMode = $credentials['stock_sync_mode'] ?? 'basic';

        if ($syncMode === 'aggregated') {
            // Aggregated mode: update with aggregated stock to single warehouse
            $warehouseId = $credentials['warehouse_id'] ?? null;

            if (! $warehouseId) {
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

        if (! $barcode) {
            throw new \RuntimeException('У связи не указан barcode (external_sku). Перепривяжите товар для сохранения корректного баркода.');
        }

        // Validate that it looks like a barcode (numeric)
        if (! is_numeric($barcode) && ! preg_match('/^\d+$/', $barcode)) {
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
                throw new \RuntimeException('Невозможно определить barcode для синхронизации. Перепривяжите товар.');
            }
        }

        $wbStockService = new \App\Services\Marketplaces\Wildberries\WildberriesStockService;
        $syncMode = $account->credentials_json['sync_mode'] ?? 'basic';

        if ($syncMode === 'aggregated') {
            // Aggregated mode: update single target warehouse with aggregated stock
            $warehouseId = $account->credentials_json['warehouse_id'] ?? null;

            if (! $warehouseId) {
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
            return ! empty($results) ? $results[0] : ['success' => false, 'message' => 'No warehouses updated'];
        }
    }

    /**
     * Синхронизация остатков в Uzum через новый UzumApiManager (StockPlugin)
     */
    protected function syncToUzum(MarketplaceAccount $account, VariantMarketplaceLink $link, int $stock): array
    {
        $mpProduct = $link->marketplaceProduct;

        if (! $mpProduct) {
            throw new \RuntimeException('Не найден связанный MarketplaceProduct для Uzum');
        }

        $skuId = $link->external_sku_id;

        if (! $skuId) {
            $skuId = $this->findUzumSkuId($link, $mpProduct);
        }

        if (! $skuId) {
            throw new \RuntimeException('Не указан external_sku_id для SKU Uzum. Перепривяжите товар.');
        }

        $uzum = new UzumApiManager($account);

        // Ищем barcode, skuTitle и fbsLinked/dbsLinked из raw_payload
        $barcode = null;
        $skuTitle = null;
        $fbsLinked = true;
        $dbsLinked = true;
        foreach ($mpProduct->raw_payload['skuList'] ?? [] as $sku) {
            if (isset($sku['skuId']) && (string) $sku['skuId'] === (string) $skuId) {
                $barcode = isset($sku['barcode']) ? (string) $sku['barcode'] : null;
                $skuTitle = $sku['skuTitle'] ?? $sku['skuFullTitle'] ?? null;
                break;
            }
        }

        // GET текущих остатков от Uzum — получаем fbsAllowed/dbsAllowed и проверяем регистрацию SKU
        $skuFoundInApi = false;
        try {
            $currentStocks = $uzum->stocks()->get();
            $skuListFromApi = $currentStocks['skuAmountList']
                ?? $currentStocks['payload']['skuAmountList']
                ?? [];
            if (is_array($skuListFromApi)) {
                foreach ($skuListFromApi as $sku) {
                    if (isset($sku['skuId']) && (string) $sku['skuId'] === (string) $skuId) {
                        $skuFoundInApi = true;
                        $barcode = $barcode ?? (isset($sku['barcode']) ? (string) $sku['barcode'] : null);
                        $skuTitle = $skuTitle ?? ($sku['skuTitle'] ?? null);
                        // fbsAllowed/dbsAllowed — что разрешено для товара (FBS или DBS)
                        // Используем Allowed, а не Linked: Linked может быть false даже если Allowed=true
                        $fbsAllowed = isset($sku['fbsAllowed']) ? (bool) $sku['fbsAllowed'] : null;
                        $dbsAllowed = isset($sku['dbsAllowed']) ? (bool) $sku['dbsAllowed'] : null;
                        if ($fbsAllowed !== null || $dbsAllowed !== null) {
                            $fbsLinked = $fbsAllowed ?? true;
                            $dbsLinked = $dbsAllowed ?? true;
                        }
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Uzum GET stocks failed', ['error' => $e->getMessage()]);
        }

        // SKU не найден в FBS/DBS системе — синхронизация невозможна
        if (! $skuFoundInApi) {
            throw new \RuntimeException(
                "SKU {$skuId} не подключён к FBS/DBS в Uzum. " .
                "Активируйте схему продаж для этого товара в кабинете Uzum."
            );
        }

        if (! $barcode) {
            throw new \RuntimeException("Не найден barcode для SKU {$skuId}. Обновите данные товара из маркетплейса.");
        }

        $productTitle = $mpProduct->title ?? '';
        $skuTitle = $skuTitle ?? $productTitle;

        $result = $uzum->stocks()->updateOne((int) $skuId, $stock, $barcode, $skuTitle, $productTitle, $fbsLinked, $dbsLinked);
        $updatedRecords = $result['payload']['updatedRecords'] ?? $result['updatedRecords'] ?? 0;

        if ($updatedRecords === 0) {
            throw new \RuntimeException(
                "Uzum не обновил остаток для SKU {$skuId} (updatedRecords=0). " .
                "Проверьте статус товара в кабинете Uzum — возможно он заблокирован или архивирован."
            );
        }

        return ['success' => true, 'sku_id' => $skuId, 'stock' => $stock, 'updated_records' => $updatedRecords, 'response' => $result];
    }

    /**
     * Обновить остаток через продуктовый API Uzum (PUT /v1/product/{productId})
     * Используется когда SKU не зарегистрирован в FBS/DBS системе.
     * Обновляет quantityFbs в skuList продукта.
     */
    protected function syncToUzumViaProductUpdate(
        UzumApiManager $uzum,
        $mpProduct,
        string $skuId,
        int $stock,
    ): array {
        $productId = $mpProduct->external_product_id;
        $rawPayload = $mpProduct->raw_payload ?? [];
        $skuList = $rawPayload['skuList'] ?? [];

        if (empty($skuList)) {
            throw new \RuntimeException("Нет данных skuList в raw_payload для продукта {$productId}. Обновите каталог из Uzum.");
        }

        // Обновляем quantityFbs для нужного SKU
        $found = false;
        foreach ($skuList as &$sku) {
            if (isset($sku['skuId']) && (string) $sku['skuId'] === $skuId) {
                $sku['quantityFbs'] = $stock;
                $found = true;
                break;
            }
        }
        unset($sku);

        if (! $found) {
            throw new \RuntimeException("SKU {$skuId} не найден в данных продукта {$productId}.");
        }

        // Строим минимальный payload для PUT /v1/product/{productId}
        $payload = [
            'shopId' => (int) ($mpProduct->shop_id ?? 0),
            'title' => $rawPayload['title'] ?? $mpProduct->title ?? '',
            'description' => $rawPayload['description'] ?? '',
            'brand' => $rawPayload['brand'] ?? '',
            'vendorCode' => $rawPayload['vendorCode'] ?? $rawPayload['sellerItemCode'] ?? '',
            'categoryId' => (int) ($rawPayload['categoryId'] ?? $rawPayload['category']['id'] ?? 0),
            'skuList' => array_map(fn ($s) => [
                'skuId' => $s['skuId'] ?? null,
                'skuTitle' => $s['skuTitle'] ?? '',
                'article' => $s['article'] ?? $s['sellerItemCode'] ?? '',
                'barcode' => $s['barcode'] ?? '',
                'price' => (int) ($s['price'] ?? $s['marketPrice'] ?? 0),
                'quantityFbs' => (int) ($s['quantityFbs'] ?? 0),
                'characteristics' => $s['characteristics'] ?? $s['characteristicsList'] ?? [],
            ], $skuList),
        ];

        Log::error('DEBUG Uzum syncToUzumViaProductUpdate', [
            'product_id' => $productId,
            'sku_id' => $skuId,
            'stock' => $stock,
            'payload_preview' => ['shopId' => $payload['shopId'], 'title' => $payload['title'], 'sku_count' => count($payload['skuList'])],
        ]);

        $result = $uzum->api()->call(
            \App\Services\Uzum\Api\UzumEndpoints::PRODUCT_UPDATE,
            params: ['productId' => $productId],
            body: $payload,
        );

        Log::error('DEBUG Uzum productUpdate response', [
            'product_id' => $productId,
            'sku_id' => $skuId,
            'response' => $result,
        ]);

        return ['success' => true, 'method' => 'product_update', 'sku_id' => $skuId, 'stock' => $stock, 'response' => $result];
    }

    /**
     * Найти Uzum SKU ID для связи
     * Пробуем найти по баркоду или берём первый из списка
     */
    protected function findUzumSkuId(VariantMarketplaceLink $link, $mpProduct): ?string
    {
        $skuList = $mpProduct->raw_payload['skuList'] ?? [];

        if (empty($skuList)) {
            // Пробуем использовать external_offer_id если он содержит skuId
            if ($mpProduct->external_offer_id) {
                Log::info('Uzum: Using external_offer_id as skuId fallback', [
                    'link_id' => $link->id,
                    'external_offer_id' => $mpProduct->external_offer_id,
                ]);

                return (string) $mpProduct->external_offer_id;
            }

            return null;
        }

        // 1. Пробуем найти по баркоду из связи
        $linkBarcode = $link->marketplace_barcode ?? $link->variant?->barcode ?? null;

        if ($linkBarcode) {
            foreach ($skuList as $sku) {
                $skuBarcode = isset($sku['barcode']) ? (string) $sku['barcode'] : null;
                if ($skuBarcode && $skuBarcode === (string) $linkBarcode) {
                    $foundSkuId = isset($sku['skuId']) ? (string) $sku['skuId'] : null;
                    if ($foundSkuId) {
                        Log::info('Uzum: Found skuId by barcode match', [
                            'link_id' => $link->id,
                            'barcode' => $linkBarcode,
                            'sku_id' => $foundSkuId,
                        ]);

                        // Сохраняем найденный skuId в link для будущих синхронизаций
                        $link->update(['external_sku_id' => $foundSkuId]);

                        return $foundSkuId;
                    }
                }
            }
        }

        // 2. Пробуем найти по external_sku (может содержать skuTitle или другой идентификатор)
        $externalSku = $link->external_sku;
        if ($externalSku) {
            foreach ($skuList as $sku) {
                $skuTitle = $sku['skuTitle'] ?? $sku['skuFullTitle'] ?? null;
                if ($skuTitle && stripos($skuTitle, $externalSku) !== false) {
                    $foundSkuId = isset($sku['skuId']) ? (string) $sku['skuId'] : null;
                    if ($foundSkuId) {
                        Log::info('Uzum: Found skuId by title match', [
                            'link_id' => $link->id,
                            'external_sku' => $externalSku,
                            'sku_id' => $foundSkuId,
                        ]);

                        $link->update(['external_sku_id' => $foundSkuId]);

                        return $foundSkuId;
                    }
                }
            }
        }

        // 3. Если ничего не нашли, берём первый SKU из списка
        $firstSku = $skuList[0] ?? null;
        if ($firstSku && isset($firstSku['skuId'])) {
            $foundSkuId = (string) $firstSku['skuId'];
            Log::warning('Uzum: Using first skuId as fallback (may be incorrect for multi-SKU products)', [
                'link_id' => $link->id,
                'sku_id' => $foundSkuId,
                'total_skus' => count($skuList),
            ]);

            // Сохраняем только если это единственный SKU (однозначное соответствие)
            if (count($skuList) === 1) {
                $link->update([
                    'external_sku_id' => $foundSkuId,
                    'marketplace_barcode' => $firstSku['barcode'] ?? null,
                ]);
            }

            return $foundSkuId;
        }

        return null;
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

        if (! $link || ! $link->variant) {
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
