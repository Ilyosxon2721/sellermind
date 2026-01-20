<?php
// file: app/Services/Marketplaces/OzonClient.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use DateTimeInterface;

class OzonClient implements MarketplaceClientInterface
{
    protected MarketplaceHttpClient $http;

    public function __construct(MarketplaceHttpClient $http)
    {
        $this->http = $http;
    }

    public function getMarketplaceCode(): string
    {
        return 'ozon';
    }

    /**
     * Ping API to check connectivity (health-check)
     * Uses warehouse list endpoint - lightweight and validates credentials
     */
    public function ping(MarketplaceAccount $account): array
    {
        try {
            // Use warehouse list - lightweight endpoint that validates Client-Id and Api-Key
            $response = $this->http->post($account, '/v1/warehouse/list', []);

            return [
                'success' => true,
                'message' => 'Ozon API доступен',
                'response_time_ms' => null,
                'data' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
                'response_time_ms' => null,
            ];
        }
    }

    public function testConnection(MarketplaceAccount $account): array
    {
        try {
            // TODO: Use actual Ozon endpoint to verify credentials
            // POST /v1/warehouse/list - returns warehouse list
            $response = $this->http->post($account, '/v1/warehouse/list', []);

            return [
                'success' => true,
                'message' => 'Соединение с Ozon успешно установлено',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . $e->getMessage(),
            ];
        }
    }

    public function syncProducts(MarketplaceAccount $account, array $products): void
    {
        /**
         * OZON Product Import API
         * Endpoint: POST /v2/product/import
         *
         * Allows creating and updating products in batch
         * Max 100 products per request
         */

        $batchSize = 100;
        $batches = array_chunk($products, $batchSize);

        foreach ($batches as $batch) {
            $importItems = [];

            foreach ($batch as $marketplaceProduct) {
                try {
                    $product = $marketplaceProduct->product;
                    if (!$product) {
                        $marketplaceProduct->markAsFailed('Product not found');
                        continue;
                    }

                    // Map internal product to OZON format
                    $importItem = $this->mapProductToOzonFormat($marketplaceProduct, $product);
                    if ($importItem) {
                        $importItems[] = $importItem;
                    }
                } catch (\Exception $e) {
                    $marketplaceProduct->markAsFailed('Mapping error: ' . $e->getMessage());
                }
            }

            if (empty($importItems)) {
                continue;
            }

            try {
                // Import products to OZON
                $response = $this->http->post($account, '/v2/product/import', [
                    'items' => $importItems
                ]);

                // Check import status
                $taskId = $response['result']['task_id'] ?? null;
                if ($taskId) {
                    // Wait and check status
                    sleep(2);
                    $this->checkImportStatus($account, $taskId, $batch);
                }
            } catch (\Exception $e) {
                foreach ($batch as $marketplaceProduct) {
                    $marketplaceProduct->markAsFailed('API error: ' . $e->getMessage());
                }
            }
        }
    }

    /**
     * Map internal product to OZON format
     */
    protected function mapProductToOzonFormat($marketplaceProduct, $product): ?array
    {
        // Get category_id from marketplace product or product mapping
        $categoryId = $marketplaceProduct->external_category_id ?? null;
        if (!$categoryId) {
            throw new \Exception('Category ID not set. Please set category before importing.');
        }

        // Get barcode
        $barcode = $product->barcode ?? $product->sku;

        // Prepare images
        $images = [];
        if ($product->main_image) {
            $images[] = $product->main_image;
        }
        if ($product->images && is_array($product->images)) {
            $images = array_merge($images, $product->images);
        }

        // Build product data
        $data = [
            'offer_id' => $product->sku, // Using SKU as offer_id
            'name' => $product->name,
            'category_id' => (int) $categoryId,
            'price' => (string) $product->price,
        ];

        // Add barcode if available
        if ($barcode) {
            $data['barcode'] = $barcode;
        }

        // Add description if available
        if ($product->description) {
            $data['description'] = strip_tags($product->description);
        }

        // Add images
        if (!empty($images)) {
            $data['images'] = array_slice($images, 0, 10); // OZON allows max 10 images
            $data['primary_image'] = $images[0];
        }

        // Add dimensions if available
        if ($product->weight) {
            $data['weight'] = (int) $product->weight;
            $data['weight_unit'] = 'g'; // grams
        }

        if ($product->length && $product->width && $product->height) {
            $data['depth'] = (int) $product->length;
            $data['width'] = (int) $product->width;
            $data['height'] = (int) $product->height;
            $data['dimension_unit'] = 'mm'; // millimeters
        }

        // Add attributes (will need to be customized based on category)
        $data['attributes'] = $this->getProductAttributes($product, $categoryId);

        return $data;
    }

    /**
     * Get product attributes for OZON
     */
    protected function getProductAttributes($product, $categoryId): array
    {
        $attributes = [];

        // Basic attributes that most categories require
        if ($product->brand) {
            $attributes[] = [
                'id' => 85, // Brand attribute ID
                'value' => $product->brand
            ];
        }

        // TODO: Add more attributes based on category
        // This will need to be enhanced with category-specific attribute mapping

        return $attributes;
    }

    /**
     * Check import task status
     */
    protected function checkImportStatus(MarketplaceAccount $account, int $taskId, array $products): void
    {
        try {
            $response = $this->http->post($account, '/v1/product/import/info', [
                'task_id' => $taskId
            ]);

            $result = $response['result'] ?? [];
            $items = $result['items'] ?? [];

            // Update marketplace products with results
            foreach ($items as $item) {
                $offerId = $item['offer_id'] ?? null;
                $productId = $item['product_id'] ?? null;
                $status = $item['status'] ?? null;
                $errors = $item['errors'] ?? [];

                // Find marketplace product by offer_id (SKU)
                foreach ($products as $marketplaceProduct) {
                    if ($marketplaceProduct->product && $marketplaceProduct->product->sku === $offerId) {
                        if ($status === 'imported' && $productId) {
                            $marketplaceProduct->external_product_id = $productId;
                            $marketplaceProduct->external_offer_id = $offerId;
                            $marketplaceProduct->markAsSynced();
                        } else {
                            $errorMsg = !empty($errors) ? json_encode($errors) : 'Import failed';
                            $marketplaceProduct->markAsFailed($errorMsg);
                        }
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but don't fail all products
            \Log::error('OZON import status check failed: ' . $e->getMessage());
        }
    }

    public function syncPrices(MarketplaceAccount $account, array $products): void
    {
        /**
         * OZON Price Import API
         * Endpoint: POST /v1/product/import/prices
         *
         * Updates product prices in batch
         * Max 1000 products per request
         */

        $batchSize = 1000;
        $batches = array_chunk($products, $batchSize);

        foreach ($batches as $batch) {
            $priceUpdates = [];

            foreach ($batch as $marketplaceProduct) {
                // Need either product_id or offer_id
                if (!$marketplaceProduct->external_product_id && !$marketplaceProduct->external_offer_id) {
                    continue;
                }

                $product = $marketplaceProduct->product;
                if (!$product) {
                    continue;
                }

                $priceData = [
                    'offer_id' => $marketplaceProduct->external_offer_id ?? $product->sku,
                    'price' => (string) $product->price,
                ];

                // Add product_id if available
                if ($marketplaceProduct->external_product_id) {
                    $priceData['product_id'] = (int) $marketplaceProduct->external_product_id;
                }

                // Add old price if available (for strikethrough price)
                if ($product->old_price && $product->old_price > $product->price) {
                    $priceData['old_price'] = (string) $product->old_price;
                }

                // Add minimum price if available (OZON will not allow selling below this)
                if ($product->min_price) {
                    $priceData['min_price'] = (string) $product->min_price;
                }

                $priceUpdates[] = $priceData;
            }

            if (empty($priceUpdates)) {
                continue;
            }

            try {
                $response = $this->http->post($account, '/v1/product/import/prices', [
                    'prices' => $priceUpdates
                ]);

                // OZON returns updated prices immediately
                $updatedPrices = $response['result'] ?? [];

                // Mark as synced
                foreach ($batch as $marketplaceProduct) {
                    $marketplaceProduct->last_price_sync_at = now();
                    $marketplaceProduct->save();
                }
            } catch (\Exception $e) {
                \Log::error('OZON price sync failed: ' . $e->getMessage(), [
                    'account_id' => $account->id,
                    'products_count' => count($priceUpdates)
                ]);
                throw $e;
            }
        }
    }

    public function syncStocks(MarketplaceAccount $account, array $products): void
    {
        /**
         * OZON Stock Update API
         * Endpoint: POST /v2/products/stocks (for FBS - Fulfillment by Seller)
         *
         * Updates product stock quantities
         * Max 100 products per request
         */

        $batchSize = 100;
        $batches = array_chunk($products, $batchSize);

        foreach ($batches as $batch) {
            $stockUpdates = [];

            foreach ($batch as $marketplaceProduct) {
                // Need either product_id or offer_id
                if (!$marketplaceProduct->external_product_id && !$marketplaceProduct->external_offer_id) {
                    continue;
                }

                $product = $marketplaceProduct->product;
                if (!$product) {
                    continue;
                }

                $stockData = [
                    'offer_id' => $marketplaceProduct->external_offer_id ?? $product->sku,
                    'stock' => (int) ($product->stock_quantity ?? 0),
                ];

                // Add product_id if available
                if ($marketplaceProduct->external_product_id) {
                    $stockData['product_id'] = (int) $marketplaceProduct->external_product_id;
                }

                // Add warehouse_id if specified in account settings
                $warehouseId = $account->credentials['warehouse_id'] ?? null;
                if ($warehouseId) {
                    $stockData['warehouse_id'] = (int) $warehouseId;
                }

                $stockUpdates[] = $stockData;
            }

            if (empty($stockUpdates)) {
                continue;
            }

            try {
                $response = $this->http->post($account, '/v2/products/stocks', [
                    'stocks' => $stockUpdates
                ]);

                // OZON returns updated stocks
                $updatedStocks = $response['result'] ?? [];

                // Mark as synced
                foreach ($batch as $marketplaceProduct) {
                    $marketplaceProduct->last_stock_sync_at = now();
                    $marketplaceProduct->save();
                }
            } catch (\Exception $e) {
                \Log::error('OZON stock sync failed: ' . $e->getMessage(), [
                    'account_id' => $account->id,
                    'products_count' => count($stockUpdates)
                ]);
                throw $e;
            }
        }
    }

    /**
     * Обновить остаток одного товара
     * POST /v2/products/stocks
     */
    public function updateStock(MarketplaceAccount $account, string $offerId, int $stock, ?int $warehouseId = null): array
    {
        // Get warehouse_id from account settings if not provided
        $credentials = $account->getDecryptedCredentials();
        $warehouseId = $warehouseId ?? ($credentials['warehouse_id'] ?? null);

        $stockData = [
            'offer_id' => $offerId,
            'stock' => max(0, $stock),
        ];

        if ($warehouseId) {
            $stockData['warehouse_id'] = (int) $warehouseId;
        }

        $request = ['stocks' => [$stockData]];

        $response = $this->http->post($account, '/v2/products/stocks', $request);

        return [
            'success' => true,
            'request' => $request,
            'response' => $response,
        ];
    }

    /**
     * Получить список складов Ozon
     * POST /v1/warehouse/list
     */
    public function getWarehouses(MarketplaceAccount $account): array
    {
        try {
            $response = $this->http->post($account, '/v1/warehouse/list', []);
            return $response['result'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    public function fetchOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to): array
    {
        /**
         * OZON Orders API
         * Endpoint: POST /v3/posting/fbs/list (for FBS orders)
         * Endpoint: POST /v2/posting/fbo/list (for FBO orders)
         *
         * Fetches orders within date range
         * Max 1000 orders per request
         */

        $orders = [];

        try {
            // Fetch FBS orders (Fulfillment by Seller)
            $fbsOrders = $this->fetchFBSOrders($account, $from, $to);
            $orders = array_merge($orders, $fbsOrders);

            // Fetch FBO orders (Fulfillment by Ozon) - if needed
            // $fboOrders = $this->fetchFBOOrders($account, $from, $to);
            // $orders = array_merge($orders, $fboOrders);

        } catch (\Exception $e) {
            throw new \RuntimeException("Failed to fetch OZON orders: " . $e->getMessage());
        }

        return $orders;
    }

    /**
     * Fetch FBS orders
     */
    protected function fetchFBSOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $orders = [];
        $limit = 1000;
        $offset = 0;
        $hasMore = true;

        while ($hasMore) {
            try {
                $response = $this->http->post($account, '/v3/posting/fbs/list', [
                    'filter' => [
                        'since' => $from->format('Y-m-d\TH:i:s\Z'),
                        'to' => $to->format('Y-m-d\TH:i:s\Z'),
                    ],
                    'limit' => $limit,
                    'offset' => $offset,
                    'with' => [
                        'analytics_data' => true,
                        'financial_data' => true,
                    ]
                ]);

                $postings = $response['result']['postings'] ?? [];

                foreach ($postings as $posting) {
                    $orders[] = $this->mapOrderData($posting, 'fbs');
                }

                // Check if there are more orders
                $hasMore = count($postings) === $limit;
                $offset += $limit;

                // Safety limit to avoid infinite loop
                if ($offset > 10000) {
                    \Log::warning('OZON FBS orders pagination limit reached', [
                        'account_id' => $account->id,
                        'offset' => $offset
                    ]);
                    break;
                }
            } catch (\Exception $e) {
                \Log::error('OZON FBS orders fetch failed: ' . $e->getMessage(), [
                    'account_id' => $account->id,
                    'offset' => $offset
                ]);
                throw $e;
            }
        }

        return $orders;
    }

    /**
     * Fetch FBO orders
     */
    protected function fetchFBOOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to): array
    {
        $orders = [];
        $limit = 1000;
        $offset = 0;
        $hasMore = true;

        while ($hasMore) {
            try {
                $response = $this->http->post($account, '/v2/posting/fbo/list', [
                    'filter' => [
                        'since' => $from->format('Y-m-d\TH:i:s\Z'),
                        'to' => $to->format('Y-m-d\TH:i:s\Z'),
                    ],
                    'limit' => $limit,
                    'offset' => $offset,
                    'with' => [
                        'analytics_data' => true,
                        'financial_data' => true,
                    ]
                ]);

                $postings = $response['result'] ?? [];

                foreach ($postings as $posting) {
                    $orders[] = $this->mapOrderData($posting, 'fbo');
                }

                // Check if there are more orders
                $hasMore = count($postings) === $limit;
                $offset += $limit;

                // Safety limit
                if ($offset > 10000) {
                    break;
                }
            } catch (\Exception $e) {
                \Log::error('OZON FBO orders fetch failed: ' . $e->getMessage());
                throw $e;
            }
        }

        return $orders;
    }

    /**
     * Get detailed info for a single product
     * @deprecated Use getProductsInfo() for batch operations
     */
    public function getProductInfo(MarketplaceAccount $account, string $externalId): ?array
    {
        // Use batch method for consistency
        $results = $this->getProductsInfo($account, [(int) $externalId]);
        return $results[0] ?? null;
    }

    /**
     * Get detailed info for multiple products (batch)
     * POST /v3/product/info/list
     * Max 1000 products per request
     */
    public function getProductsInfo(MarketplaceAccount $account, array $productIds): array
    {
        if (empty($productIds)) {
            return [];
        }

        try {
            // Convert all IDs to integers
            $productIds = array_map('intval', array_values($productIds));

            // Limit to 1000 products per request
            $productIds = array_slice($productIds, 0, 1000);

            $response = $this->http->post($account, '/v3/product/info/list', [
                'product_id' => $productIds,
            ]);

            // v3 API returns items directly, not in result.items
            return $response['items'] ?? [];
        } catch (\Exception $e) {
            \Log::error('Ozon getProductsInfo failed: ' . $e->getMessage(), [
                'account_id' => $account->id,
                'product_ids_count' => count($productIds),
                'exception' => $e,
            ]);
            return [];
        }
    }

    /**
     * Get list of products from Ozon
     * POST /v3/product/list (updated endpoint - v2 is deprecated)
     */
    public function getProducts(MarketplaceAccount $account, array $filters = [], int $limit = 100, string $lastId = ''): array
    {
        try {
            $request = [
                // OZON API requires filter to be an object, not array
                'filter' => empty($filters) ? new \stdClass() : $filters,
                'limit' => min($limit, 1000), // Max 1000 per request
            ];

            if ($lastId) {
                $request['last_id'] = $lastId;
            }

            \Log::debug('Ozon getProducts API request', [
                'account_id' => $account->id,
                'request' => $request,
            ]);

            $response = $this->http->post($account, '/v3/product/list', $request);

            \Log::debug('Ozon getProducts API response', [
                'account_id' => $account->id,
                'response' => $response,
                'items_count' => count($response['result']['items'] ?? []),
                'total' => $response['result']['total'] ?? 0,
            ]);

            return [
                'items' => $response['result']['items'] ?? [],
                'total' => $response['result']['total'] ?? 0,
                'last_id' => $response['result']['last_id'] ?? '',
            ];
        } catch (\Exception $e) {
            \Log::error('Ozon getProducts failed: ' . $e->getMessage(), [
                'account_id' => $account->id,
                'filters' => $filters,
                'exception' => $e,
            ]);
            throw $e;
        }
    }

    /**
     * Sync product catalog from Ozon API to local database
     * Optimized version using batch API calls
     */
    public function syncCatalog(MarketplaceAccount $account): array
    {
        $synced = 0;
        $created = 0;
        $updated = 0;
        $lastId = '';
        $hasMore = true;

        \Log::info('Starting Ozon catalog sync (optimized)', [
            'account_id' => $account->id,
            'account_name' => $account->name,
        ]);

        try {
            // Fetch all products with pagination
            while ($hasMore) {
                \Log::info('Fetching Ozon products page', [
                    'account_id' => $account->id,
                    'last_id' => $lastId,
                ]);

                $result = $this->getProducts($account, [], 1000, $lastId);
                $items = $result['items'] ?? [];

                \Log::info('Received products from Ozon API', [
                    'account_id' => $account->id,
                    'items_count' => count($items),
                    'total' => $result['total'] ?? 0,
                    'last_id' => $result['last_id'] ?? '',
                ]);

                if (empty($items)) {
                    \Log::info('No more products to fetch from Ozon', [
                        'account_id' => $account->id,
                        'synced_so_far' => $synced,
                    ]);
                    break;
                }

                // Extract product IDs for batch request
                $productIds = [];
                $itemsByProductId = [];

                foreach ($items as $item) {
                    $productId = $item['product_id'] ?? null;
                    if ($productId) {
                        $productIds[] = (int) $productId;
                        $itemsByProductId[$productId] = $item;
                    }
                }

                if (empty($productIds)) {
                    \Log::warning('No valid product IDs in batch', ['account_id' => $account->id]);
                    break;
                }

                \Log::info('Fetching detailed info for products (batch)', [
                    'account_id' => $account->id,
                    'product_count' => count($productIds),
                ]);

                // Get detailed info for all products in one batch request
                $productsInfo = $this->getProductsInfo($account, $productIds);

                \Log::info('Received detailed product info', [
                    'account_id' => $account->id,
                    'info_count' => count($productsInfo),
                ]);

                // Create a map of product info by product_id for quick lookup
                $infoByProductId = [];
                foreach ($productsInfo as $productInfo) {
                    $pid = $productInfo['id'] ?? null;
                    if ($pid) {
                        $infoByProductId[$pid] = $productInfo;
                    }
                }

                // Process each product
                foreach ($items as $item) {
                    $productId = $item['product_id'] ?? null;
                    $offerId = $item['offer_id'] ?? null;

                    if (!$productId) {
                        \Log::warning('Skipping product without ID', ['item' => $item]);
                        continue;
                    }

                    // Get product info from batch results
                    $productInfo = $infoByProductId[$productId] ?? null;

                    if (!$productInfo) {
                        \Log::warning('No detailed info for product', [
                            'product_id' => $productId,
                            'offer_id' => $offerId,
                        ]);
                        // Continue anyway with basic data from list
                        $productInfo = [];
                    }

                    // Extract product data (v3 API structure)
                    $productData = [
                        'marketplace_account_id' => $account->id,
                        'external_product_id' => $productId,
                        'external_offer_id' => $offerId,
                        'name' => $productInfo['name'] ?? null,
                        'status' => $productInfo['statuses']['status_name'] ?? 'unknown',
                        'barcode' => !empty($productInfo['barcodes']) ? $productInfo['barcodes'][0] : null,
                        'price' => $productInfo['price'] ?? null,
                        'vat' => $productInfo['vat'] ?? null,
                        'category_id' => $productInfo['description_category_id'] ?? null,
                        'images' => !empty($productInfo['images']) ? $productInfo['images'] : [], // Laravel cast 'array' handles JSON encoding
                        'description' => null, // v3 API doesn't include description in product/info/list
                        'last_synced_at' => now(),
                    ];

                    // Update or create product
                    $ozonProduct = \App\Models\OzonProduct::updateOrCreate(
                        [
                            'marketplace_account_id' => $account->id,
                            'external_product_id' => $productId,
                        ],
                        $productData
                    );

                    $synced++;
                    if ($ozonProduct->wasRecentlyCreated) {
                        $created++;
                    } else {
                        $updated++;
                    }
                }

                \Log::info('Batch processed', [
                    'account_id' => $account->id,
                    'batch_size' => count($items),
                    'total_synced' => $synced,
                    'created' => $created,
                    'updated' => $updated,
                ]);

                // Check if there are more products
                $lastId = $result['last_id'] ?? '';
                $hasMore = !empty($lastId) && count($items) >= 1000;

                \Log::info('Pagination status', [
                    'last_id' => $lastId,
                    'has_more' => $hasMore,
                    'synced' => $synced,
                ]);

                // Safety limit to avoid infinite loop
                if ($synced > 50000) {
                    \Log::warning('Ozon catalog sync limit reached', [
                        'account_id' => $account->id,
                        'synced' => $synced
                    ]);
                    break;
                }

                // Small delay to avoid rate limiting (only if more pages exist)
                if ($hasMore) {
                    usleep(500000); // 500ms between batch requests
                }
            }

            \Log::info('Ozon catalog synced successfully', [
                'account_id' => $account->id,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ]);

            return [
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ];
        } catch (\Exception $e) {
            \Log::error('Ozon catalog sync failed: ' . $e->getMessage(), [
                'account_id' => $account->id,
                'synced_before_error' => $synced,
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Map OZON order data to standard format
     */
    protected function mapOrderData(array $orderData, string $type = 'fbs'): array
    {
        $products = $orderData['products'] ?? [];
        $items = [];
        $totalAmount = 0;

        foreach ($products as $product) {
            $price = (float) ($product['price'] ?? 0);
            $quantity = (int) ($product['quantity'] ?? 1);
            $itemTotal = $price * $quantity;
            $totalAmount += $itemTotal;

            $items[] = [
                'sku' => $product['offer_id'] ?? null,
                'name' => $product['name'] ?? '',
                'quantity' => $quantity,
                'price' => $price,
                'total' => $itemTotal,
                'external_product_id' => $product['sku'] ?? null,
            ];
        }

        // Map OZON status to internal status
        $status = $this->mapOrderStatus($orderData['status'] ?? '');

        return [
            'external_order_id' => $orderData['posting_number'] ?? $orderData['order_number'] ?? null,
            'status' => $status,
            'total_amount' => $totalAmount,
            'currency' => 'RUB',
            'ordered_at' => $orderData['in_process_at'] ?? $orderData['created_at'] ?? now(),
            'delivery_date' => $orderData['shipment_date'] ?? null,
            'customer' => [
                'name' => $orderData['customer']['name'] ?? null,
                'address' => $orderData['customer']['address'] ?? null,
            ],
            'delivery_type' => $type, // fbs or fbo
            'items' => $items,
            'raw_payload' => $orderData,
        ];
    }

    /**
     * Map OZON order status to internal status
     */
    protected function mapOrderStatus(string $ozonStatus): string
    {
        $statusMap = [
            'awaiting_packaging' => 'pending',
            'awaiting_deliver' => 'processing',
            'arbitration' => 'processing',
            'client_arbitration' => 'processing',
            'delivering' => 'shipped',
            'driver_pickup' => 'shipped',
            'delivered' => 'completed',
            'cancelled' => 'cancelled',
            'not_accepted' => 'cancelled',
        ];

        return $statusMap[$ozonStatus] ?? 'pending';
    }

    /**
     * Отменить отправление FBS
     * POST /v2/posting/fbs/cancel
     */
    public function cancelPosting(
        MarketplaceAccount $account,
        string $postingNumber,
        int $cancelReasonId,
        string $cancelReasonMessage = ''
    ): array {
        return $this->http->post($account, '/v2/posting/fbs/cancel', [
            'posting_number' => $postingNumber,
            'cancel_reason_id' => $cancelReasonId,
            'cancel_reason_message' => $cancelReasonMessage,
        ]);
    }

    /**
     * Получить список причин отмены
     * POST /v2/posting/fbs/cancel-reason/list
     */
    public function getCancelReasons(MarketplaceAccount $account): array
    {
        $response = $this->http->post($account, '/v2/posting/fbs/cancel-reason/list', []);
        return $response['reasons'] ?? [];
    }

    /**
     * Передать отправление к отгрузке
     * POST /v2/posting/fbs/awaiting-delivery
     */
    public function markAsAwaitingDelivery(
        MarketplaceAccount $account,
        string $postingNumber
    ): array {
        return $this->http->post($account, '/v2/posting/fbs/awaiting-delivery', [
            'posting_number' => $postingNumber,
        ]);
    }

    /**
     * Собрать заказ (готов к отгрузке)
     * POST /v4/posting/fbs/ship
     */
    public function shipPosting(
        MarketplaceAccount $account,
        array $packages
    ): array {
        return $this->http->post($account, '/v4/posting/fbs/ship', [
            'packages' => $packages,
        ]);
    }

    /**
     * Получить этикетку для печати (PDF)
     * POST /v2/posting/fbs/package-label
     */
    public function getPackageLabel(
        MarketplaceAccount $account,
        array $postingNumbers
    ): string {
        \Log::info('Ozon getPackageLabel called', [
            'account_id' => $account->id,
            'posting_numbers' => $postingNumbers,
        ]);

        $response = $this->http->post($account, '/v2/posting/fbs/package-label', [
            'posting_number' => $postingNumbers,
        ]);

        \Log::info('Ozon getPackageLabel response', [
            'account_id' => $account->id,
            'has_result' => isset($response['result']),
            'response_keys' => array_keys($response),
        ]);

        // Возвращает base64 encoded PDF
        if (empty($response['result'])) {
            \Log::error('Ozon getPackageLabel: empty result', [
                'account_id' => $account->id,
                'response' => $response,
            ]);
            throw new \RuntimeException('Не удалось получить этикетку от Ozon API: ' . json_encode($response));
        }

        return $response['result'];
    }

    /**
     * Создать задание на формирование этикеток (для больших объемов)
     * POST /v2/posting/fbs/package-label/create
     */
    public function createPackageLabelTask(
        MarketplaceAccount $account,
        array $postingNumbers
    ): array {
        return $this->http->post($account, '/v2/posting/fbs/package-label/create', [
            'posting_number' => $postingNumbers,
        ]);
    }

    /**
     * Получить полную информацию об отправлении
     * POST /v3/posting/fbs/get
     */
    public function getPostingDetails(
        MarketplaceAccount $account,
        string $postingNumber,
        bool $withAnalytics = true
    ): array {
        return $this->http->post($account, '/v3/posting/fbs/get', [
            'posting_number' => $postingNumber,
            'with' => [
                'analytics_data' => $withAnalytics,
                'financial_data' => true,
                'translit' => false,
            ],
        ]);
    }

    // ==================== FINANCE METHODS ====================

    /**
     * Получить список транзакций за период
     * POST /v3/finance/transaction/list
     *
     * @param MarketplaceAccount $account
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getFinanceTransactions(
        MarketplaceAccount $account,
        DateTimeInterface $from,
        DateTimeInterface $to,
        int $page = 1,
        int $pageSize = 1000
    ): array {
        return $this->http->post($account, '/v3/finance/transaction/list', [
            'filter' => [
                'date' => [
                    'from' => $from->format('Y-m-d\TH:i:s.000\Z'),
                    'to' => $to->format('Y-m-d\TH:i:s.999\Z'),
                ],
                'posting_number' => '',
                'transaction_type' => 'all',
            ],
            'page' => $page,
            'page_size' => $pageSize,
        ]);
    }

    /**
     * Получить все транзакции за период с пагинацией
     *
     * @param MarketplaceAccount $account
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return array
     */
    public function getAllFinanceTransactions(
        MarketplaceAccount $account,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array {
        $allTransactions = [];
        $page = 1;
        $pageSize = 1000;

        do {
            $response = $this->getFinanceTransactions($account, $from, $to, $page, $pageSize);
            $transactions = $response['result']['operations'] ?? [];

            if (empty($transactions)) {
                break;
            }

            $allTransactions = array_merge($allTransactions, $transactions);
            $page++;

            // Safety limit
            if ($page > 100) {
                \Log::warning('Ozon finance transactions pagination limit reached', [
                    'account_id' => $account->id,
                    'total' => count($allTransactions),
                ]);
                break;
            }
        } while (count($transactions) === $pageSize);

        return $allTransactions;
    }

    /**
     * Получить итоговую сумму транзакций
     * POST /v3/finance/transaction/totals
     *
     * @param MarketplaceAccount $account
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return array
     */
    public function getFinanceTransactionTotals(
        MarketplaceAccount $account,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array {
        return $this->http->post($account, '/v3/finance/transaction/totals', [
            'filter' => [
                'date' => [
                    'from' => $from->format('Y-m-d\TH:i:s.000\Z'),
                    'to' => $to->format('Y-m-d\TH:i:s.999\Z'),
                ],
                'posting_number' => '',
                'transaction_type' => 'all',
            ],
        ]);
    }

    /**
     * Получить сводку расходов маркетплейса
     *
     * @param MarketplaceAccount $account
     * @param DateTimeInterface $from
     * @param DateTimeInterface $to
     * @return array
     */
    public function getExpensesSummary(
        MarketplaceAccount $account,
        DateTimeInterface $from,
        DateTimeInterface $to
    ): array {
        $summary = [
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'returns' => 0,
            'other' => 0,
            'total' => 0,
            'currency' => 'RUB',
        ];

        try {
            $transactions = $this->getAllFinanceTransactions($account, $from, $to);

            foreach ($transactions as $transaction) {
                $type = $transaction['operation_type'] ?? '';
                $amount = abs((float) ($transaction['amount'] ?? 0));
                $services = $transaction['services'] ?? [];

                // Process services array for detailed breakdown
                foreach ($services as $service) {
                    $serviceName = $service['name'] ?? '';
                    $serviceAmount = abs((float) ($service['price'] ?? 0));

                    if (stripos($serviceName, 'комисси') !== false || stripos($serviceName, 'commission') !== false) {
                        $summary['commission'] += $serviceAmount;
                    } elseif (stripos($serviceName, 'логист') !== false || stripos($serviceName, 'доставк') !== false || stripos($serviceName, 'delivery') !== false) {
                        $summary['logistics'] += $serviceAmount;
                    } elseif (stripos($serviceName, 'хранен') !== false || stripos($serviceName, 'storage') !== false) {
                        $summary['storage'] += $serviceAmount;
                    } elseif (stripos($serviceName, 'реклам') !== false || stripos($serviceName, 'продвиж') !== false || stripos($serviceName, 'advertising') !== false) {
                        $summary['advertising'] += $serviceAmount;
                    } elseif (stripos($serviceName, 'штраф') !== false || stripos($serviceName, 'penalty') !== false) {
                        $summary['penalties'] += $serviceAmount;
                    } else {
                        $summary['other'] += $serviceAmount;
                    }
                }

                // Handle operation types
                if (stripos($type, 'return') !== false || stripos($type, 'возврат') !== false) {
                    $summary['returns'] += $amount;
                }
            }

            $summary['total'] = $summary['commission']
                + $summary['logistics']
                + $summary['storage']
                + $summary['advertising']
                + $summary['penalties']
                + $summary['other'];

            \Log::info('Ozon expenses summary calculated', [
                'account_id' => $account->id,
                'period' => $from->format('Y-m-d') . ' - ' . $to->format('Y-m-d'),
                'transactions_count' => count($transactions),
                'summary' => $summary,
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get Ozon expenses summary', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        return $summary;
    }

    /**
     * Получить отчёт о реализации товаров
     * POST /v1/finance/realization
     *
     * @param MarketplaceAccount $account
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getRealizationReport(
        MarketplaceAccount $account,
        int $year,
        int $month
    ): array {
        return $this->http->post($account, '/v1/finance/realization', [
            'date' => sprintf('%04d-%02d', $year, $month),
        ]);
    }
}
