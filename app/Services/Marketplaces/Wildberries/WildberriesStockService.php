<?php

// file: app/Services/Marketplaces/Wildberries/WildberriesStockService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceStockLog;
use App\Models\MarketplaceWarehouse;
use App\Models\WildberriesProduct;
use App\Models\WildberriesStock;
use App\Models\WildberriesWarehouse;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Service for syncing Wildberries stock data.
 *
 * WB Statistics API:
 * - GET /api/v1/supplier/stocks - get warehouse stocks
 *
 * WB Marketplace API (FBS):
 * - PUT /api/v3/stocks/{warehouseId} - update stocks for FBS warehouse
 */
class WildberriesStockService
{
    /**
     * Get or create HTTP client for specific account
     */
    protected function getHttpClient(MarketplaceAccount $account): WildberriesHttpClient
    {
        return new WildberriesHttpClient($account);
    }

    /**
     * Sync stocks from WB Statistics API
     *
     * @param  \DateTimeInterface|null  $from  Start date for sync (default: yesterday)
     * @return array Sync results
     */
    public function syncStocks(MarketplaceAccount $account, ?\DateTimeInterface $from = null): array
    {
        $synced = 0;
        $errors = [];
        $warehousesCreated = 0;
        $productsUpdated = 0;
        $productsLinkedToLocal = 0;

        $dateFrom = $from ?? now()->subDay();

        Log::info('Starting WB stocks sync', [
            'account_id' => $account->id,
            'date_from' => $dateFrom->format('Y-m-d'),
        ]);

        try {
            // Fetch stocks from Statistics API
            $response = $this->getHttpClient($account)->get('statistics', '/api/v1/supplier/stocks', [
                'dateFrom' => $dateFrom->format('Y-m-d'),
            ]);

            // Response is an array of stock items
            $stockItems = $response;

            if (! is_array($stockItems)) {
                throw new \RuntimeException('Invalid stocks response format');
            }

            // Process each stock item
            foreach ($stockItems as $stockData) {
                try {
                    $result = $this->processStockItem($account, $stockData);

                    if ($result['warehouse_created']) {
                        $warehousesCreated++;
                    }

                    if ($result['product_updated']) {
                        $productsUpdated++;
                    }

                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'nm_id' => $stockData['nmId'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // After syncing WB stocks, align to local products
            $productsLinkedToLocal = $this->syncLocalProductsFromStocks($account);

            Log::info('WB stocks sync completed', [
                'account_id' => $account->id,
                'synced' => $synced,
                'warehouses_created' => $warehousesCreated,
                'products_updated' => $productsUpdated,
                'products_linked_to_local' => $productsLinkedToLocal,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('WB stocks sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $errors[] = ['sync_error' => $e->getMessage()];
        }

        return [
            'synced' => $synced,
            'warehouses_created' => $warehousesCreated,
            'products_updated' => $productsUpdated,
            'products_linked_to_local' => $productsLinkedToLocal,
            'errors' => $errors,
        ];
    }

    /**
     * Process a single stock item from WB API
     */
    protected function processStockItem(MarketplaceAccount $account, array $stockData): array
    {
        $warehouseCreated = false;
        $productUpdated = false;

        // Find or create warehouse
        $warehouseName = $stockData['warehouseName'] ?? 'Unknown';
        $warehouse = WildberriesWarehouse::firstOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'warehouse_name' => $warehouseName,
            ],
            [
                'warehouse_type' => $stockData['isSupply'] ?? false ? 'FBS' : 'FBO',
                'is_active' => true,
            ]
        );

        if ($warehouse->wasRecentlyCreated) {
            $warehouseCreated = true;
        }

        // Find or create product
        $nmId = $stockData['nmId'] ?? null;
        $barcode = $stockData['barcode'] ?? null;

        if (! $nmId && ! $barcode) {
            throw new \RuntimeException('Stock data missing nmId and barcode');
        }

        $product = WildberriesProduct::where('marketplace_account_id', $account->id)
            ->where(function ($q) use ($nmId, $barcode) {
                if ($nmId) {
                    $q->where('nm_id', $nmId);
                }
                if ($barcode) {
                    $q->orWhere('barcode', $barcode);
                }
            })
            ->first();

        // If product doesn't exist, create a minimal record
        if (! $product) {
            $product = WildberriesProduct::create([
                'marketplace_account_id' => $account->id,
                'nm_id' => $nmId,
                'barcode' => $barcode,
                'supplier_article' => $stockData['supplierArticle'] ?? null,
                'brand' => $stockData['brand'] ?? null,
                'subject_name' => $stockData['subject'] ?? null,
                'tech_size' => $stockData['techSize'] ?? null,
                'is_active' => true,
            ]);
            $productUpdated = true;
        }

        // Update or create stock record
        $stock = WildberriesStock::updateOrCreate(
            [
                'wildberries_product_id' => $product->id,
                'wildberries_warehouse_id' => $warehouse->id,
            ],
            [
                'marketplace_account_id' => $account->id,
                'quantity' => $stockData['quantity'] ?? 0,
                'quantity_full' => $stockData['quantityFull'] ?? 0,
                'in_way_to_client' => $stockData['inWayToClient'] ?? 0,
                'in_way_from_client' => $stockData['inWayFromClient'] ?? 0,
                'sku' => $stockData['supplierArticle'] ?? null,
                'last_change_date' => isset($stockData['lastChangeDate'])
                    ? Carbon::parse($stockData['lastChangeDate'])
                    : now(),
            ]
        );

        // Update product's total stock
        $product->syncStockTotal();

        return [
            'warehouse_created' => $warehouseCreated,
            'product_updated' => $productUpdated,
            'stock' => $stock,
        ];
    }

    /**
     * Align stocks with local products (Sellermind AI) via MarketplaceProduct links.
     * Maps by external_product_id (nmID) and updates MarketplaceProduct + Product.stock_quantity.
     */
    public function syncLocalProductsFromStocks(MarketplaceAccount $account): int
    {
        // Map nmID => stock_total from WB products
        $wbProducts = WildberriesProduct::where('marketplace_account_id', $account->id)
            ->get(['nm_id', 'stock_total'])
            ->keyBy('nm_id');

        if ($wbProducts->isEmpty()) {
            return 0;
        }

        $linked = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('product_id')
            ->whereNotNull('external_product_id')
            ->with('product')
            ->get();

        $updatedCount = 0;

        foreach ($linked as $mp) {
            $nmId = $mp->external_product_id;
            if (! $nmId || ! $wbProducts->has($nmId)) {
                continue;
            }

            $stockTotal = (int) ($wbProducts[$nmId]->stock_total ?? 0);

            // Update marketplace_product cached stock
            $mp->update([
                'last_synced_stock' => $stockTotal,
                'last_synced_at' => now(),
            ]);

            // Update local Product.stock_quantity if field exists
            if ($mp->product && $mp->product->isFillable('stock_quantity')) {
                $mp->product->update(['stock_quantity' => $stockTotal]);
            }

            $updatedCount++;
        }

        Log::info('WB local stock sync completed', [
            'account_id' => $account->id,
            'updated_links' => $updatedCount,
        ]);

        return $updatedCount;
    }

    /**
     * Push stock updates to WB (FBS only)
     *
     * @param  int  $warehouseId  WB warehouse ID
     * @param  array  $stocks  Array of ['sku' => '', 'amount' => 0]
     * @return array API response
     */
    public function pushStocks(MarketplaceAccount $account, int $warehouseId, array $stocks): array
    {
        if (empty($stocks)) {
            return ['success' => true, 'message' => 'No stocks to update'];
        }

        Log::info('Pushing stocks to WB', [
            'account_id' => $account->id,
            'warehouse_id' => $warehouseId,
            'stocks_count' => count($stocks),
        ]);

        try {
            // WB Marketplace API: PUT /api/v3/stocks/{warehouseId}
            $response = $this->getHttpClient($account)->put(
                'marketplace',
                "/api/v3/stocks/{$warehouseId}",
                ['stocks' => $stocks]
            );

            return [
                'success' => true,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to push stocks to WB', [
                'account_id' => $account->id,
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Push stocks for all linked products (local -> WB) across FBS warehouses.
     *
     * @param  array<int>|null  $productIds
     * @return array summary
     */
    public function pushLinkedProducts(MarketplaceAccount $account, ?array $productIds = null): array
    {
        $warehouses = WildberriesWarehouse::forAccount($account->id)
            ->active()
            ->whereNotNull('warehouse_id')
            ->where(function ($q) {
                $q->whereNull('warehouse_type')->orWhere('warehouse_type', 'FBS');
            })
            ->get();

        if ($warehouses->isEmpty()) {
            return ['warehouses' => 0, 'pushed' => 0, 'errors' => 0, 'error_messages' => ['Нет активных складов WB (FBS)']];
        }

        // Use mapping table if available
        $mapping = MarketplaceWarehouse::where('marketplace_account_id', $account->id)
            ->whereNotNull('wildberries_warehouse_id')
            ->where('is_active', true)
            ->pluck('wildberries_warehouse_id');
        if ($mapping->isNotEmpty()) {
            $warehouses = $warehouses->whereIn('warehouse_id', $mapping->all());
        }

        $links = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('product_id')
            ->whereNotNull('external_product_id')
            ->with('product')
            ->when($productIds, fn ($q) => $q->whereIn('product_id', $productIds))
            ->get();

        $pushed = 0;
        $errors = 0;
        $errorMessages = [];

        foreach ($warehouses as $warehouse) {
            $stocks = $this->buildStocksPayload($links);
            foreach ($stocks->chunk(1000) as $chunk) {
                try {
                    $this->pushStocks($account, (int) $warehouse->warehouse_id, $chunk->values()->all());
                    $pushed += $chunk->count();
                    $this->logStock($account->id, null, (int) $warehouse->warehouse_id, 'push', 'success', $chunk->values()->all(), null);
                } catch (\Exception $e) {
                    $errors++;
                    $errorMessages[] = "Warehouse {$warehouse->warehouse_id}: ".$e->getMessage();
                    $this->logStock($account->id, null, (int) $warehouse->warehouse_id, 'push', 'error', $chunk->values()->all(), $e->getMessage());
                }
            }
        }

        return [
            'warehouses' => $warehouses->count(),
            'pushed' => $pushed,
            'errors' => $errors,
            'error_messages' => $errorMessages,
        ];
    }

    /**
     * Build WB stocks payload from links
     */
    protected function buildStocksPayload(Collection $links): Collection
    {
        return $links->map(function (MarketplaceProduct $link) {
            $qty = (int) ($link->product->stock_quantity ?? 0);
            $sku = $link->external_offer_id
                ?: $link->external_sku
                ?: $link->product->sku
                ?: $link->external_product_id;

            return [
                'sku' => (string) $sku,
                'amount' => $qty,
            ];
        })->filter(fn ($item) => ! empty($item['sku']));
    }

    protected function logStock(int $accountId, ?int $mpId, ?int $wbWarehouseId, string $direction, string $status, $payload = null, ?string $message = null): void
    {
        try {
            MarketplaceStockLog::create([
                'marketplace_account_id' => $accountId,
                'marketplace_product_id' => $mpId,
                'wildberries_warehouse_id' => $wbWarehouseId,
                'direction' => $direction,
                'status' => $status,
                'payload' => $payload,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to log stock sync', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Get FBS warehouses for the account
     */
    public function getWarehouses(MarketplaceAccount $account): array
    {
        try {
            Log::info('WB getWarehouses: fetching warehouses from API', [
                'account_id' => $account->id,
                'has_marketplace_token' => ! empty($account->wb_marketplace_token),
                'has_api_key' => ! empty($account->api_key),
            ]);

            $warehouses = $this->getHttpClient($account)->get('marketplace', '/api/v3/warehouses');

            Log::info('WB getWarehouses: API response', [
                'account_id' => $account->id,
                'warehouses_count' => is_array($warehouses) ? count($warehouses) : 0,
                'warehouses' => is_array($warehouses) ? array_slice($warehouses, 0, 5) : $warehouses,
            ]);

            return $warehouses;
        } catch (\Exception $e) {
            Log::error('Failed to get WB warehouses', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [];
        }
    }

    /**
     * Sync FBS warehouses from Marketplace API to database
     * This updates warehouse_id field which is required for stock push operations
     *
     * @return array Sync results
     */
    public function syncWarehouses(MarketplaceAccount $account): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        try {
            $warehouses = $this->getWarehouses($account);

            if (empty($warehouses)) {
                // Try alternative: extract warehouse info from existing stock data
                Log::info('Marketplace API returned no warehouses, trying to extract from stock data', [
                    'account_id' => $account->id,
                ]);

                $result = $this->extractWarehousesFromStocks($account);

                if ($result['found'] > 0) {
                    return [
                        'created' => 0,
                        'updated' => $result['found'],
                        'errors' => [],
                        'note' => 'Warehouses extracted from stock data (officeId). This may have limitations for FBS operations.',
                    ];
                }

                Log::warning('No FBS warehouses returned from WB API', [
                    'account_id' => $account->id,
                ]);

                return [
                    'created' => 0,
                    'updated' => 0,
                    'errors' => ['Marketplace API did not return any warehouses. This may indicate: 1) No FBS warehouses configured, 2) API token lacks permissions, or 3) Account is FBO-only.'],
                ];
            }

            foreach ($warehouses as $warehouseData) {
                try {
                    $warehouseId = $warehouseData['id'] ?? null;
                    $name = $warehouseData['name'] ?? 'Unknown';

                    if (! $warehouseId) {
                        $errors[] = 'Warehouse missing id: '.json_encode($warehouseData);

                        continue;
                    }

                    // Try to find existing warehouse by name or warehouse_id
                    $warehouse = WildberriesWarehouse::where('marketplace_account_id', $account->id)
                        ->where(function ($q) use ($warehouseId, $name) {
                            $q->where('warehouse_id', $warehouseId)
                                ->orWhere('warehouse_name', $name);
                        })
                        ->first();

                    if ($warehouse) {
                        // Update existing warehouse
                        $warehouse->update([
                            'warehouse_id' => $warehouseId,
                            'warehouse_name' => $name,
                            'warehouse_type' => 'FBS',
                            'address' => $warehouseData['address'] ?? null,
                            'is_active' => true,
                        ]);
                        $updated++;
                    } else {
                        // Create new warehouse
                        WildberriesWarehouse::create([
                            'marketplace_account_id' => $account->id,
                            'warehouse_id' => $warehouseId,
                            'warehouse_name' => $name,
                            'warehouse_type' => 'FBS',
                            'address' => $warehouseData['address'] ?? null,
                            'is_active' => true,
                        ]);
                        $created++;
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error processing warehouse '{$name}': ".$e->getMessage();
                }
            }

            Log::info('WB warehouses sync completed', [
                'account_id' => $account->id,
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors),
            ]);

        } catch (\Exception $e) {
            Log::error('WB warehouses sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            $errors[] = 'Sync failed: '.$e->getMessage();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Extract warehouse data from stock records
     * Uses officeId from stock data as a fallback warehouse_id
     */
    protected function extractWarehousesFromStocks(MarketplaceAccount $account): array
    {
        $found = 0;

        try {
            // Get unique warehouses from stock data
            $stockData = $this->getHttpClient($account)->get('statistics', '/api/v1/supplier/stocks', [
                'dateFrom' => now()->subDays(7)->format('Y-m-d'),
            ]);

            if (empty($stockData)) {
                return ['found' => 0];
            }

            // Group by warehouse and office
            $warehouseMap = [];
            foreach ($stockData as $item) {
                $whName = $item['warehouseName'] ?? null;
                $officeId = $item['office'] ?? $item['officeId'] ?? null;

                if ($whName && $officeId) {
                    $warehouseMap[$whName] = $officeId;
                }
            }

            // Update warehouses with officeId as warehouse_id
            foreach ($warehouseMap as $name => $officeId) {
                $warehouse = WildberriesWarehouse::where('marketplace_account_id', $account->id)
                    ->where('warehouse_name', $name)
                    ->first();

                if ($warehouse && ! $warehouse->warehouse_id) {
                    $warehouse->update([
                        'warehouse_id' => $officeId,
                        'office_id' => $officeId,
                    ]);
                    $found++;
                }
            }

            Log::info('Extracted warehouse IDs from stock data', [
                'account_id' => $account->id,
                'found' => $found,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to extract warehouses from stock data', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        return ['found' => $found];
    }

    /**
     * Обновить остаток одного товара
     * PUT /api/v3/stocks/{warehouseId}
     */
    public function updateStock(MarketplaceAccount $account, string $sku, int $stock, ?int $warehouseId = null): array
    {
        // Get warehouse_id from account settings if not provided
        if (! $warehouseId) {
            $credentials = $account->getAllCredentials();
            $warehouseId = $credentials['warehouse_id'] ?? null;

            // Try to get from database (from synced warehouses)
            if (! $warehouseId) {
                $dbWarehouse = WildberriesWarehouse::forAccount($account->id)
                    ->active()
                    ->whereNotNull('warehouse_id')
                    ->where(function ($q) {
                        $q->whereNull('warehouse_type')->orWhere('warehouse_type', 'FBS');
                    })
                    ->orderBy('is_active', 'desc')
                    ->first();

                if ($dbWarehouse) {
                    $warehouseId = $dbWarehouse->warehouse_id;
                }
            }

            // If still no warehouse, try to get from API
            if (! $warehouseId) {
                $warehouses = $this->getWarehouses($account);
                if (! empty($warehouses)) {
                    $warehouseId = $warehouses[0]['id'] ?? null;
                }
            }
        }

        if (! $warehouseId) {
            throw new \RuntimeException('Не найден склад для обновления остатков. Убедитесь, что у вас есть активный FBS склад или укажите warehouse_id в настройках аккаунта.');
        }

        $stockData = [
            'sku' => $sku,
            'amount' => max(0, $stock),
        ];

        $request = ['stocks' => [$stockData]];

        Log::info('WB Stock Update Request', [
            'account_id' => $account->id,
            'warehouse_id' => $warehouseId,
            'sku' => $sku,
            'stock' => $stock,
            'request' => $request,
        ]);

        $response = $this->getHttpClient($account)->put(
            'marketplace',
            "/api/v3/stocks/{$warehouseId}",
            $request
        );

        Log::info('WB Stock Update Response', [
            'account_id' => $account->id,
            'warehouse_id' => $warehouseId,
            'response' => $response,
        ]);

        return [
            'success' => true,
            'warehouse_id' => $warehouseId,
            'request' => $request,
            'response' => $response,
        ];
    }

    /**
     * Получить ID склада по умолчанию
     */
    public function getDefaultWarehouseId(MarketplaceAccount $account): ?int
    {
        $credentials = $account->getAllCredentials();
        $warehouseId = $credentials['warehouse_id'] ?? null;

        // Try to get from database (from synced warehouses)
        if (! $warehouseId) {
            $dbWarehouse = WildberriesWarehouse::forAccount($account->id)
                ->active()
                ->whereNotNull('warehouse_id')
                ->where(function ($q) {
                    $q->whereNull('warehouse_type')->orWhere('warehouse_type', 'FBS');
                })
                ->orderBy('is_active', 'desc')
                ->first();

            if ($dbWarehouse) {
                $warehouseId = $dbWarehouse->warehouse_id;
            }
        }

        // Try to get from API
        if (! $warehouseId) {
            $warehouses = $this->getWarehouses($account);
            if (! empty($warehouses)) {
                $warehouseId = $warehouses[0]['id'] ?? null;
            }
        }

        return $warehouseId ? (int) $warehouseId : null;
    }
}
