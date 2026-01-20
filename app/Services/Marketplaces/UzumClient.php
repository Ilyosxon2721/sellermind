<?php
// file: app/Services/Marketplaces/UzumClient.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceShop;
use DateTimeInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UzumClient implements MarketplaceClientInterface
{
    protected MarketplaceHttpClient $http;
    protected IssueDetectorService $issueDetector;

    public function __construct(MarketplaceHttpClient $http, IssueDetectorService $issueDetector)
    {
        $this->http = $http;
        $this->issueDetector = $issueDetector;
    }

    public function getMarketplaceCode(): string
    {
        return 'uzum';
    }

    /**
     * Fetch products for given shop IDs (pull from Uzum)
     */
    public function fetchProducts(MarketplaceAccount $account, array $shopIds = [], int $pageSize = 100): array
    {
        $shopIds = $this->resolveShopIds($account, $shopIds);

        $all = [];

        foreach ($shopIds as $shopId) {
            $page = 0;
            do {
                $response = $this->request(
                    $account,
                    'GET',
                    "/v1/product/shop/{$shopId}",
                    [
                        'page' => $page,
                        'size' => $pageSize,
                    ]
                );

                $list = $response['payload']['productList'] ?? [];
                foreach ($list as $product) {
                    $all[] = $product;
                }

                $page++;
            } while (!empty($list) && count($list) === $pageSize);
        }

        return $all;
    }

    /**
     * Pull full catalog from Uzum for all shops and persist into marketplace_products
     */
    public function syncCatalog(MarketplaceAccount $account): array
    {
        $shopIds = $this->resolveShopIds($account);
        $synced = 0;
        $created = 0;
        $updated = 0;

        Log::info('Uzum syncCatalog starting', [
            'account_id' => $account->id,
            'shop_ids' => $shopIds,
        ]);

        foreach ($shopIds as $shopId) {
            $page = 0;
            do {
                Log::info('Uzum syncCatalog fetching page', [
                    'shop_id' => $shopId,
                    'page' => $page,
                ]);

                $response = $this->request(
                    $account,
                    'GET',
                    "/v1/product/shop/{$shopId}",
                    [
                        'sortBy' => 'DEFAULT',
                        'order' => 'ASC',
                        'size' => 100,
                        'page' => $page,
                        'filter' => 'ALL',
                    ]
                );

                $list = $response['payload']['productList'] ?? $response['productList'] ?? [];

                Log::info('Uzum syncCatalog response', [
                    'shop_id' => $shopId,
                    'page' => $page,
                    'products_count' => count($list),
                    'response_keys' => array_keys($response),
                ]);

                foreach ($list as $product) {
                    $this->storeProduct($account, $shopId, $product);
                    $synced++;
                }

                $page++;
            } while (!empty($list));
        }

        return ['synced' => $synced, 'created' => $created, 'updated' => $updated, 'shops' => $shopIds];
    }

    protected function storeProduct(MarketplaceAccount $account, string|int $shopId, array $product): void
    {
        $externalId = $product['productId'] ?? $product['id'] ?? null;
        if (!$externalId) {
            return;
        }

        $statusValue = $product['status']['value'] ?? ($product['isActive'] ?? false ? 'IN_STOCK' : 'UNKNOWN');

        // Price: product-level price or first SKU price (без деления на 100, Uzum отдаёт суммы)
        $price = null;
        if (isset($product['price'])) {
            $price = (float) $product['price'];
        } elseif (!empty($product['skuList'][0]['price'])) {
            $price = (float) $product['skuList'][0]['price'];
        }

        // Stock: prefer product quantityFbs/Active, otherwise sum of SKUs
        $stock = $product['quantityFbs'] ?? $product['quantityActive'] ?? null;
        if ($stock === null && !empty($product['skuList'])) {
            $stock = collect($product['skuList'])->sum(function ($sku) {
                return ($sku['quantityFbs'] ?? 0)
                    + ($sku['quantityActive'] ?? 0)
                    + ($sku['quantityAdditional'] ?? 0);
            });
        }

        $mp = \App\Models\MarketplaceProduct::updateOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'shop_id' => (string) $shopId,
                'external_product_id' => (string) $externalId,
            ],
            [
                'status' => $statusValue,
                'title' => $product['title'] ?? $product['skuTitle'] ?? null,
                'category' => $product['category'] ?? null,
                'preview_image' => $product['image'] ?? $product['previewImg'] ?? null,
                'last_synced_price' => $price,
                'last_synced_stock' => $stock !== null ? (int) $stock : null,
                'last_synced_at' => now(),
                'raw_payload' => $product,
            ]
        );

        // For completeness, save SKU-level info into raw_payload (already there), external_offer_id
        if (!$mp->external_offer_id && !empty($product['skuList'][0]['skuId'])) {
            $mp->external_offer_id = (string) $product['skuList'][0]['skuId'];
            $mp->save();
        }
    }

    /**
     * Преобразование наших внутренних статусов в список внешних статусов Uzum
     */
    protected function externalStatusesFromInternal(?array $internalStatuses): array
    {
        // Полный список статусов, поддерживаемых API Uzum Market
        $default = [
            'CREATED',
            'PACKING',
            'PENDING_DELIVERY',
            'DELIVERING',
            'ACCEPTED_AT_DP',
            'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
            'DELIVERED',
            'COMPLETED',
            'CANCELED',
            'PENDING_CANCELLATION',
            'RETURNED',
        ];

        if (!$internalStatuses || empty($internalStatuses)) {
            return $default;
        }

        $map = [
            'new' => ['CREATED'],
            'in_assembly' => ['PACKING'],
            'in_supply' => ['PENDING_DELIVERY'],
            'accepted_uzum' => ['DELIVERING', 'ACCEPTED_AT_DP', 'DELIVERED'],
            'waiting_pickup' => ['DELIVERED_TO_CUSTOMER_DELIVERY_POINT'],
            'issued' => ['COMPLETED'],
            'cancelled' => ['CANCELED', 'PENDING_CANCELLATION'],
            'returns' => ['RETURNED'],
        ];

        $result = [];
        foreach ($internalStatuses as $st) {
            $result = array_merge($result, $map[$st] ?? []);
        }

        return $result ?: $default;
    }

    /**
     * Fetch list of shops for the account
     */
    public function fetchShops(MarketplaceAccount $account): array
    {
        $response = $this->request($account, 'GET', '/v1/shops');
        // Uzum API returns shops in payload array
        return $response['payload'] ?? $response ?? [];
    }

    /**
     * Try to resolve shop IDs from account field, DB cache or API (and persist them)
     */
    protected function resolveShopIds(MarketplaceAccount $account, array $shopIds = []): array
    {
        // 1. from explicit argument
        $shopIds = $this->normalizeShopIds($shopIds);
        if (!empty($shopIds)) {
            return $shopIds;
        }

        // 2. from credentials_json['shop_ids'] - user selected shops
        $credentialsJson = $account->credentials_json ?? [];
        if (!empty($credentialsJson['shop_ids']) && is_array($credentialsJson['shop_ids'])) {
            Log::debug('Uzum resolveShopIds: raw shop_ids from credentials_json', [
                'account_id' => $account->id,
                'raw_shop_ids' => $credentialsJson['shop_ids'],
            ]);
            $shopIds = $this->normalizeShopIds($credentialsJson['shop_ids']);
            if (!empty($shopIds)) {
                Log::info('Uzum resolveShopIds: normalized shop_ids from credentials_json', [
                    'account_id' => $account->id,
                    'shop_ids' => $shopIds,
                ]);
                return $shopIds;
            }
        }

        // 3. from account->shop_id (legacy field, backwards compatibility)
        $shopIds = $this->normalizeShopIds(
            is_array($account->shop_id) ? $account->shop_id : explode(',', (string) $account->shop_id)
        );
        if (!empty($shopIds)) {
            return $shopIds;
        }

        // 4. from DB cache
        $dbShops = MarketplaceShop::where('marketplace_account_id', $account->id)->get();
        if ($dbShops->isNotEmpty()) {
            return $this->normalizeShopIds($dbShops->pluck('external_id')->all());
        }

        // pull from API and persist
        try {
            $apiShops = $this->fetchShops($account);
            $this->storeShops($account, $apiShops);
            $ids = $this->normalizeShopIds(array_column($apiShops, 'id'));

            if (empty($account->shop_id) && !empty($ids)) {
                $account->update(['shop_id' => implode(',', $ids)]);
            }

            if (!empty($ids)) {
                return $ids;
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to fetch Uzum shops automatically', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        throw new \RuntimeException('Магазин не найден. Откройте настройки Uzum и выберите магазин из списка.');
    }

    /**
     * Persist shops list in DB cache
     */
    protected function storeShops(MarketplaceAccount $account, array $shops): void
    {
        foreach ($shops as $shop) {
            if (!isset($shop['id'])) {
                continue;
            }
            MarketplaceShop::updateOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'external_id' => (string) $shop['id'],
                ],
                [
                    'name' => $shop['name'] ?? null,
                    'raw_payload' => $shop,
                ]
            );
        }
    }

    /**
     * Normalize shop IDs to a clean array of strings/ints
     */
    protected function normalizeShopIds(array $ids): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            if ($id === null || $id === '') {
                continue;
            }

            // Handle comma-separated strings (e.g., "12697,16980,17490")
            if (is_string($id) && str_contains($id, ',')) {
                $parts = explode(',', $id);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part !== '' && is_numeric($part)) {
                        $normalized[] = (int) $part;
                    }
                }
            } elseif (is_numeric($id)) {
                $normalized[] = (int) $id;
            } elseif (is_string($id) && $id !== '') {
                $normalized[] = $id;
            }
        }

        // Remove duplicates while preserving order
        return array_values(array_unique($normalized));
    }

    /**
     * Ping API to check connectivity (health-check)
     * Uses seller info endpoint - lightweight and validates API key
     */
    public function ping(MarketplaceAccount $account): array
    {
        // Always try /v1/shops first - it's the most reliable endpoint
        $paths = ['/v1/shops'];

        foreach ($paths as $path) {
            $start = microtime(true);
            try {
                $response = $this->request($account, 'GET', $path);
                $duration = round((microtime(true) - $start) * 1000);

                \Log::info('Uzum ping success', [
                    'account_id' => $account->id,
                    'path' => $path,
                    'duration_ms' => $duration,
                    'response_keys' => is_array($response) ? array_keys($response) : 'not_array',
                ]);

                return [
                    'success' => true,
                    'message' => 'Uzum Market API доступен',
                    'response_time_ms' => $duration,
                    'data' => $response,
                    'endpoint' => $path,
                ];
            } catch (\Exception $e) {
                $lastError = $e->getMessage();

                \Log::warning('Uzum ping failed', [
                    'account_id' => $account->id,
                    'path' => $path,
                    'error' => $lastError,
                ]);

                continue;
            }
        }

        return [
            'success' => false,
            'message' => 'Ошибка пинга Uzum: ' . ($lastError ?? 'endpoint not found'),
            'response_time_ms' => null,
        ];
    }

    public function testConnection(MarketplaceAccount $account): array
    {
        return $this->ping($account);
    }

    public function syncProducts(MarketplaceAccount $account, array $products): void
    {
        // TODO: Implement Uzum product sync
        //
        // Uzum Seller OpenAPI endpoints (based on documentation):
        // - POST /v1/products - создание товара
        // - PUT /v1/products/{id} - обновление товара
        // - GET /v1/products - список товаров
        //
        // Необходимые данные для создания товара:
        // - sku (артикул продавца)
        // - title
        // - description
        // - category_id
        // - brand
        // - price
        // - stock
        // - images
        // - attributes

        foreach ($products as $marketplaceProduct) {
            try {
                // TODO: Map internal product to Uzum product format
                // TODO: Create or update product via API
                // TODO: Update MarketplaceProduct with external_product_id

                $marketplaceProduct->markAsSynced();
            } catch (\Exception $e) {
                $marketplaceProduct->markAsFailed($e->getMessage());
            }
        }
    }

    public function syncPrices(MarketplaceAccount $account, array $products): void
    {
        // TODO: Implement Uzum price sync
        //
        // Uzum Price API endpoints:
        // - PUT /v1/products/{id}/price - обновление цены товара
        // - POST /v1/products/prices - массовое обновление цен
        //
        // Формат:
        // {
        //   "items": [
        //     {
        //       "product_id": "123456",
        //       "price": 100000,
        //       "old_price": 120000
        //     }
        //   ]
        // }

        $priceUpdates = [];

        foreach ($products as $marketplaceProduct) {
            if (!$marketplaceProduct->external_product_id) {
                continue;
            }

            $product = $marketplaceProduct->product;
            if (!$product) {
                continue;
            }

            // TODO: Get price from product (Uzum uses tiyin = 1/100 sum)
            // $priceUpdates[] = [
            //     'product_id' => $marketplaceProduct->external_product_id,
            //     'price' => (int) ($product->price * 100), // Convert to tiyin
            //     'old_price' => (int) (($product->old_price ?? $product->price) * 100),
            // ];
        }

        if (empty($priceUpdates)) {
            return;
        }

        // TODO: Send price updates to Uzum API
        // $this->http->post($account, '/v1/products/prices', ['items' => $priceUpdates]);
    }

    /**
     * Update stock for a specific SKU within an Uzum product
     * 
     * Uzum API: POST /v2/fbs/sku/stocks
     * 
     * @param MarketplaceAccount $account
     * @param string $productId External product ID
     * @param string $skuId External SKU ID within the product
     * @param int $stock Stock quantity to set
     * @return array Response data
     */
    public function updateStock(MarketplaceAccount $account, string $productId, string $skuId, int $stock): array
    {
        // Get SKU details from database to fill required fields
        $marketplaceProduct = \App\Models\MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->where('external_product_id', $productId)
            ->first();
            
        $skuData = null;
        if ($marketplaceProduct && !empty($marketplaceProduct->raw_payload['skuList'])) {
            foreach ($marketplaceProduct->raw_payload['skuList'] as $sku) {
                if (isset($sku['skuId']) && (string)$sku['skuId'] === (string)$skuId) {
                    $skuData = $sku;
                    break;
                }
            }
        }
        
        // Uzum API v2 endpoint for stock updates
        $path = '/v2/fbs/sku/stocks';
        
        $requestData = [
            'skuAmountList' => [
                [
                    'skuId' => (int)$skuId,
                    'skuTitle' => $skuData['skuTitle'] ?? $skuData['skuFullTitle'] ?? '',
                    'productTitle' => $marketplaceProduct->title ?? '',
                    'barcode' => $skuData['barcode'] ?? '',
                    'amount' => $stock,
                    'fbsLinked' => true,
                    'dbsLinked' => false,
                ]
            ]
        ];

        try {
            $response = $this->request($account, 'POST', $path, [], $requestData);
            
            Log::info('Uzum stock update successful', [
                'product_id' => $productId,
                'sku_id' => $skuId,
                'stock' => $stock,
                'response' => $response,
            ]);
            
            return [
                'success' => true,
                'stock' => $stock,
                'sku_id' => $skuId,
                'product_id' => $productId,
                'request' => $requestData,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Uzum stock update failed', [
                'product_id' => $productId,
                'sku_id' => $skuId,
                'stock' => $stock,
                'request' => $requestData,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException("Не удалось обновить остаток товара. Попробуйте позже или проверьте настройки API.");
        }
    }

    public function syncStocks(MarketplaceAccount $account, array $products): void
    {
        // This method is for batch stock sync if needed in the future
        // For now, we use updateStock for individual SKU-level updates
        
        $stockUpdates = [];

        foreach ($products as $marketplaceProduct) {
            if (!$marketplaceProduct->external_product_id) {
                continue;
            }

            // Get all active links for this product
            $links = \App\Models\VariantMarketplaceLink::query()
                ->where('marketplace_product_id', $marketplaceProduct->id)
                ->where('is_active', true)
                ->where('sync_stock_enabled', true)
                ->with('variant')
                ->get();

            foreach ($links as $link) {
                if (!$link->external_sku_id || !$link->variant) {
                    continue;
                }

                try {
                    $stock = $link->variant->getCurrentStock();
                    $this->updateStock(
                        $account,
                        $marketplaceProduct->external_product_id,
                        $link->external_sku_id,
                        $stock
                    );
                } catch (\Exception $e) {
                    Log::warning('Uzum stock sync failed for SKU', [
                        'product_id' => $marketplaceProduct->external_product_id,
                        'sku_id' => $link->external_sku_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
    }

    /**
     * Загрузка заказов Uzum (FBS; DBS/FBO могут иметь другие эндпоинты — пока не вызываем, чтобы не ловить 404)
     */
    public function fetchOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to): array
    {
        return $this->fetchOrdersByStatuses($account, $from, $to, null);
    }

    /**
     * Загрузка заказов Uzum с фильтром по статусам (если переданы)
     *
     * @param array|null $internalStatuses Список наших внутренних статусов (new, in_assembly, in_supply, waiting_pickup, issued, cancelled, returns)
     */
    public function fetchOrdersByStatuses(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to, ?array $internalStatuses = null): array
    {
        $orders = [];
        $size = 50; // API ограничивает максимум 50
        $fromMs = $from?->getTimestamp() ? ($from->getTimestamp() * 1000) : null;
        $toMs = $to?->getTimestamp() ? ($to->getTimestamp() * 1000) : null;

        $shopIds = $this->resolveShopIds($account);

        // Загружаем нужные статусы (или все основные)
        $statuses = $this->externalStatusesFromInternal($internalStatuses);

        $path = '/v2/fbs/orders';

        Log::info('Uzum fetchOrdersByStatuses starting', [
            'account_id' => $account->id,
            'shop_ids' => $shopIds,
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
            'internal_statuses' => $internalStatuses,
            'external_statuses_count' => count($statuses),
            'external_statuses' => $statuses,
        ]);

        // Активные статусы и отмены/возвраты (заказы в работе + отмененные + возвраты) - загружаем ВСЕ без фильтрации по дате
        // ВАЖНО: CANCELED, PENDING_CANCELLATION и RETURNED должны загружаться без фильтра по дате,
        // чтобы обновлять старые заказы, которые были отменены или возвращены
        $activeStatuses = [
            'CREATED',
            'PACKING',
            'PENDING_DELIVERY',
            'DELIVERING',
            'ACCEPTED_AT_DP',
            'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
            'CANCELED',  // Для обновления отмененных заказов
            'PENDING_CANCELLATION',  // Для обновления заказов в процессе отмены
            'RETURNED',  // Для обновления возвращённых заказов
        ];

        foreach ($statuses as $status) {
            $isActiveStatus = in_array($status, $activeStatuses);

            // Делаем отдельные запросы для каждого магазина
            foreach ($shopIds as $shopId) {
                $page = 0;

                try {
                    $stopStatus = false;
                    do {
                        $query = [
                            'page' => $page,
                            'size' => $size,
                            'status' => $status,
                            'shopIds' => $shopId, // API expects 'shopIds' (plural)
                        ];

                        Log::info('Uzum API fetching orders for status', [
                            'status' => $status,
                            'shopId' => $shopId,
                            'page' => $page,
                            'size' => $size,
                            'is_active_status' => $isActiveStatus,
                        ]);

                        $response = $this->request($account, 'GET', $path, $query);
                        $payload = $response['payload'] ?? [];
                        $list = $payload['orders'] ?? $payload['list'] ?? [];

                        Log::info('Uzum API response for status', [
                            'status' => $status,
                            'shopId' => $shopId,
                            'page' => $page,
                            'orders_received' => count($list),
                            'response_keys' => array_keys($response),
                            'payload_keys' => is_array($payload) ? array_keys($payload) : 'not_array',
                            'raw_response_sample' => mb_substr(json_encode($response), 0, 500),
                        ]);

                        foreach ($list as $orderData) {
                            // Для активных статусов загружаем все заказы без фильтрации по дате
                            if (!$isActiveStatus) {
                                $created = $orderData['dateCreated'] ?? null;
                                if ($fromMs && $created && is_numeric($created) && $created < $fromMs) {
                                    $stopStatus = true;
                                    continue;
                                }
                                if ($toMs && $created && is_numeric($created) && $created > $toMs) {
                                    // Слишком свежий — продолжаем, но не прерываем
                                }
                            }
                            $orders[] = $this->mapOrderData($orderData, 'fbs');
                        }

                        $page++;
                    } while (!$stopStatus && !empty($list) && count($list) === $size);
                } catch (\Throwable $e) {
                    Log::warning('Uzum fetchOrders status failed', [
                        'status' => $status,
                        'shopId' => $shopId,
                        'path' => $path,
                        'error' => $e->getMessage(),
                    ]);
                    // продолжим остальные магазины/статусы
                }
            } // end foreach shopId
        } // end foreach status

        Log::info('Uzum fetchOrdersByStatuses completed', [
            'account_id' => $account->id,
            'total_orders_fetched' => count($orders),
            'statuses_requested' => count($statuses),
        ]);

        return $orders;
    }

    public function getProductInfo(MarketplaceAccount $account, string $externalId): ?array
    {
        // TODO: Implement Uzum product info fetch
        //
        // GET /v1/products/{id}

        try {
            // $response = $this->http->get($account, "/v1/products/{$externalId}");
            //
            // return $response['data'] ?? null;

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Получить PDF этикетку заказа Uzum
     */
    public function getOrderLabel(MarketplaceAccount $account, string $orderId, string $size = 'LARGE'): ?array
    {
        $size = strtoupper($size);
        if (!in_array($size, ['LARGE', 'BIG'])) {
            $size = 'LARGE';
        }

        $path = "/v1/fbs/order/{$orderId}/labels/print";
        $response = $this->request($account, 'GET', $path, ['size' => $size]);
        $doc = $response['payload']['document'] ?? null;
        if (!$doc) {
            return null;
        }

        // Документ приходит в base64 (PDF)
        return [
            'binary' => base64_decode($doc),
            'base64' => $doc,
        ];
    }

    /**
     * Подтвердить заказ (status -> PACKING)
     */
    public function confirmOrder(MarketplaceAccount $account, string $orderId): ?array
    {
        $path = "/v1/fbs/order/{$orderId}/confirm";
        $response = $this->request($account, 'POST', $path);
        $payload = $response['payload'] ?? null;
        if (!$payload) {
            return null;
        }
        return $this->mapOrderData($payload, 'fbs');
    }

    /**
     * Low-level Uzum request wrapper (base URL + auth headers)
     */
    protected function request(
        MarketplaceAccount $account,
        string $method,
        string $path,
        array $query = [],
        array $body = []
    ): array {
        $baseUrl = config('uzum.base_url', config('marketplaces.uzum.base_url'));
        $timeout = (int) config('uzum.timeout', 30);
        $headers = array_merge([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ], $this->trimHeaders($account->getUzumAuthHeaders()));

        $url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');

        Log::info('Uzum API request', [
            'account_id' => $account->id,
            'method' => $method,
            'url' => $url,
            'query' => $this->sanitizeForLog($query),
        ]);

        $client = Http::timeout($timeout)->withHeaders($headers);

        $upper = strtoupper($method);
        if ($upper === 'GET' && !empty($query)) {
            $url = $url . '?' . $this->buildQuery($query);
            $response = $client->get($url);
        } else {
            $response = match ($upper) {
                'GET' => $client->get($url),
                'POST' => $client->post($url, $body),
                'PUT' => $client->put($url, $body),
                'PATCH' => $client->patch($url, $body),
                'DELETE' => $client->delete($url, $query),
                default => throw new \InvalidArgumentException("Unsupported HTTP method: {$method}"),
            };
        }

        $status = $response->status();
        $rawBody = $response->body();

        Log::info('Uzum API response', [
            'account_id' => $account->id,
            'method' => $method,
            'url' => $url,
            'status' => $status,
            'body' => mb_substr($rawBody, 0, 1000),
        ]);

        if (!$response->successful()) {
            $errorInfo = $this->extractError($rawBody);

            // 400 Bad Request - неверные параметры запроса
            if ($status === 400) {
                Log::error('Uzum 400 Bad Request', [
                    'account_id' => $account->id,
                    'url' => $url,
                    'query' => $this->sanitizeForLog($query),
                    'body' => $this->sanitizeForLog($body),
                    'response_body' => mb_substr($rawBody, 0, 2000),
                    'error_info' => $errorInfo,
                ]);
            }

            // 401 Unauthorized - проблема с токеном
            if ($status === 401) {
                $this->issueDetector->handleApiError(
                    $account,
                    401,
                    $rawBody,
                    ['url' => $url, 'method' => $method],
                    'Uzum API request'
                );
            }

            if ($status === 403) {
                Log::warning('Uzum 403 response', [
                    'account_id' => $account->id,
                    'url' => $url,
                    'body' => mb_substr($rawBody, 0, 1000),
                    'headers' => $this->sanitizeForLog($headers),
                ]);

                // Регистрируем проблему в системе
                $this->issueDetector->handleApiError(
                    $account,
                    403,
                    $rawBody,
                    ['url' => $url, 'method' => $method],
                    'Uzum API request'
                );

                throw new \RuntimeException("Доступ запрещён. Проверьте, что токен Uzum активен и имеет необходимые права.");
            }

            // 429 Too Many Requests - rate limit exceeded
            if ($status === 429) {
                Log::warning('Uzum rate limit hit', [
                    'account_id' => $account->id,
                    'url' => $url,
                ]);

                throw new \RuntimeException("Слишком много запросов. Подождите минуту и попробуйте снова.");
            }

            $message = $this->formatUserFriendlyError($status, $errorInfo, $rawBody);
            throw new \RuntimeException($message);
        }

        $json = $response->json();
        return is_array($json) ? $json : [];
    }

    protected function sanitizeForLog(array $data): array
    {
        $sensitive = ['token', 'api_key', 'authorization', 'secret'];
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

    protected function trimHeaders(array $headers): array
    {
        return collect($headers)->map(function ($value, $key) {
            return is_string($value) ? trim($value) : $value;
        })->toArray();
    }

    protected function buildQuery(array $params): string
    {
        $parts = [];

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $item) {
                    $parts[] = rawurlencode($key) . '=' . rawurlencode($item);
                }
            } else {
                $parts[] = rawurlencode($key) . '=' . rawurlencode($value);
            }
        }

        return implode('&', $parts);
    }

    protected function extractError(?string $rawBody): ?string
    {
        if (!$rawBody) {
            return null;
        }

        $json = json_decode($rawBody, true);
        if (!is_array($json)) {
            return null;
        }

        $code = $json['errors'][0]['code'] ?? $json['code'] ?? null;
        $message = $json['errors'][0]['message'] ?? $json['message'] ?? null;

        if ($code && $message) {
            return "{$code}: {$message}";
        }
        return $message ?? $code;
    }

    /**
     * Форматирует сообщение об ошибке в понятном для пользователя виде
     */
    protected function formatUserFriendlyError(int $status, ?string $errorInfo, ?string $rawBody): string
    {
        // Известные коды ошибок Uzum API
        $knownErrors = [
            'open-api-001' => 'Неверный токен. Проверьте API-ключ в настройках.',
            'open-api-002' => 'Токен истёк. Создайте новый API-ключ в личном кабинете Uzum.',
            'open-api-003' => 'У токена нет прав для этой операции. Проверьте настройки доступа в Uzum.',
            'open-api-004' => 'Магазин не найден. Проверьте ID магазина в настройках.',
            'open-api-005' => 'Товар не найден.',
            'open-api-006' => 'Заказ не найден.',
        ];

        // Проверяем известные коды ошибок
        if ($errorInfo) {
            foreach ($knownErrors as $code => $userMessage) {
                if (stripos($errorInfo, $code) !== false) {
                    return $userMessage;
                }
            }
        }

        // Ошибки по HTTP статусу
        $statusMessages = [
            400 => 'Неверный запрос. Проверьте данные и попробуйте снова.',
            401 => 'Ошибка авторизации. Проверьте токен API в настройках Uzum.',
            404 => 'Ресурс не найден. Возможно, неверный ID или токен.',
            500 => 'Ошибка сервера Uzum. Попробуйте позже.',
            502 => 'Сервер Uzum временно недоступен. Попробуйте через несколько минут.',
            503 => 'Сервис Uzum на обслуживании. Попробуйте позже.',
            504 => 'Сервер Uzum не ответил вовремя. Попробуйте позже.',
        ];

        if (isset($statusMessages[$status])) {
            return $statusMessages[$status];
        }

        // Если ничего не подошло, показываем общее сообщение
        if ($errorInfo) {
            // Убираем технические детали для пользователя
            $cleanMessage = preg_replace('/^[\w-]+:\s*/', '', $errorInfo);
            return "Ошибка Uzum: {$cleanMessage}";
        }

        return "Ошибка соединения с Uzum ({$status}). Попробуйте позже.";
    }

    /**
     * Map Uzum order data to standard format
     */
    public function mapOrderData(array $orderData, ?string $deliveryType = null): array
    {
        $items = [];
        foreach ($orderData['orderItems'] ?? [] as $item) {
            $items[] = [
                'external_offer_id' => isset($item['skuId']) ? (string)$item['skuId'] : (string)($item['productId'] ?? ''),
                'name' => $item['skuTitle'] ?? $item['productTitle'] ?? null,
                'quantity' => $item['amount'] ?? 1,
                'price' => isset($item['sellerPrice']) ? (float) $item['sellerPrice'] : null,
                'total_price' => isset($item['sellerPrice']) ? ((float) $item['sellerPrice']) * ($item['amount'] ?? 1) : null,
                'raw_payload' => $item,
            ];
        }

        $statusNormalized = $this->mapOrderStatus($orderData['status'] ?? null);
        $deliveryInfo = $orderData['deliveryInfo'] ?? [];
        $address = $deliveryInfo['address'] ?? [];

        return [
            'external_order_id' => isset($orderData['id']) ? (string)$orderData['id'] : null,
            'status' => $statusNormalized,
            'status_normalized' => $statusNormalized,
            'customer_name' => $deliveryInfo['customerFullname'] ?? null,
            'customer_phone' => $deliveryInfo['customerPhone'] ?? null,
            'total_amount' => isset($orderData['price']) ? (float) $orderData['price'] : 0,
            'currency' => 'UZS',
            'ordered_at' => $orderData['dateCreated'] ?? null,
            'items' => $items,
            'raw_payload' => $orderData,
            // Узум специфичные поля (кладем в общие колонки)
            'wb_delivery_type' => $deliveryType ?? strtolower($orderData['deliveryType'] ?? ''),
            'delivery_address_full' => $address['fullAddress'] ?? null,
            'delivery_city' => $address['city'] ?? null,
            'delivery_street' => $address['street'] ?? null,
            'delivery_home' => $address['house'] ?? null,
            'delivery_flat' => $address['apartment'] ?? null,
            'delivery_longitude' => $address['longitude'] ?? null,
            'delivery_latitude' => $address['latitude'] ?? null,
            'wb_status_group' => $this->mapStatusGroup($statusNormalized),
            'wb_delivery_type' => $deliveryType ?? strtolower($orderData['scheme'] ?? ''),
        ];
    }

    protected function mapOrderStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        // Маппинг статусов API Uzum Market на внутренние статусы
        $map = [
            // Новые
            'CREATED' => 'new',
            // Сборка
            'PACKING' => 'in_assembly',
            // В поставке
            'PENDING_DELIVERY' => 'in_supply',
            // Приняты Узум (доставлены в ПВЗ, но еще не выданы клиенту)
            'DELIVERING' => 'accepted_uzum',
            'ACCEPTED_AT_DP' => 'accepted_uzum',
            'DELIVERED' => 'accepted_uzum', // Доставлен в ПВЗ
            // Ждут выдачи клиенту
            'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => 'waiting_pickup',
            // Завершено (выдано клиенту)
            'COMPLETED' => 'issued',
            // Отмены / возвраты
            'CANCELED' => 'cancelled',
            'PENDING_CANCELLATION' => 'cancelled',
            'RETURNED' => 'returns',
        ];

        $upper = strtoupper($status);
        return $map[$upper] ?? strtolower($status);
    }

    protected function mapStatusGroup(?string $status): ?string
    {
        return match ($status) {
            'new' => 'new',
            'in_assembly' => 'assembling',
            'in_supply' => 'in_supply',
            'waiting_pickup' => 'waiting_pickup',
            'issued' => 'issued',
            'cancelled' => 'canceled',
            'returns' => 'returns',
            default => null,
        };
    }

    // ========== Finance Orders API (для аналитики) ==========

    /**
     * Fetch finance orders from Uzum Finance API
     * Используется для аналитики, дашборда, отчётов
     * Содержит все типы заказов: FBO/FBS/DBS/EDBS
     *
     * @param MarketplaceAccount $account
     * @param array $shopIds Shop IDs to fetch orders for
     * @param int $page Page number (0-based)
     * @param int $size Page size (max 100)
     * @param bool $group Group by order (false = individual items)
     * @return array ['orderItems' => [...], 'totalElements' => int]
     */
    public function fetchFinanceOrders(
        MarketplaceAccount $account,
        array $shopIds = [],
        int $page = 0,
        int $size = 100,
        bool $group = false,
        ?int $dateFromMs = null,
        ?int $dateToMs = null
    ): array {
        $shopIds = $this->resolveShopIds($account, $shopIds);

        if (empty($shopIds)) {
            Log::warning('Uzum fetchFinanceOrders: no shop IDs', ['account_id' => $account->id]);
            return ['orderItems' => [], 'totalElements' => 0];
        }

        $allItems = [];
        $totalElements = 0;

        foreach ($shopIds as $shopId) {
            try {
                // shopIds должен быть массивом согласно API документации
                $query = [
                    'page' => $page,
                    'size' => min($size, 100),
                    'group' => $group ? 'true' : 'false',
                    'shopIds' => [$shopId],
                ];

                // Добавляем фильтр по дате если указан
                if ($dateFromMs !== null) {
                    $query['dateFrom'] = $dateFromMs;
                }
                if ($dateToMs !== null) {
                    $query['dateTo'] = $dateToMs;
                }

                $response = $this->request($account, 'GET', '/v1/finance/orders', $query);

                $items = $response['orderItems'] ?? [];
                $total = $response['totalElements'] ?? 0;

                Log::info('Uzum fetchFinanceOrders response', [
                    'account_id' => $account->id,
                    'shop_id' => $shopId,
                    'page' => $page,
                    'items_count' => count($items),
                    'total_elements' => $total,
                ]);

                $allItems = array_merge($allItems, $items);
                $totalElements = max($totalElements, $total);
            } catch (\Throwable $e) {
                Log::warning('Uzum fetchFinanceOrders failed for shop', [
                    'account_id' => $account->id,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'orderItems' => $allItems,
            'totalElements' => $totalElements,
        ];
    }

    /**
     * Fetch all finance orders with pagination
     * Автоматически обходит все страницы
     *
     * @param MarketplaceAccount $account
     * @param array $shopIds Shop IDs
     * @param int $maxPages Max pages to fetch (0 = unlimited)
     * @return array All order items
     */
    public function fetchAllFinanceOrders(
        MarketplaceAccount $account,
        array $shopIds = [],
        int $maxPages = 0
    ): array {
        $shopIds = $this->resolveShopIds($account, $shopIds);
        $allItems = [];
        $size = 100;

        foreach ($shopIds as $shopId) {
            $page = 0;
            $pagesLoaded = 0;

            do {
                try {
                    $response = $this->fetchFinanceOrders($account, [$shopId], $page, $size);
                    $items = $response['orderItems'] ?? [];
                    $totalElements = $response['totalElements'] ?? 0;

                    $allItems = array_merge($allItems, $items);
                    $pagesLoaded++;
                    $page++;

                    // Добавим небольшую задержку чтобы не превысить rate limit
                    if (!empty($items)) {
                        usleep(200000); // 200ms
                    }

                    // Проверка лимита страниц
                    if ($maxPages > 0 && $pagesLoaded >= $maxPages) {
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Uzum fetchAllFinanceOrders page failed', [
                        'account_id' => $account->id,
                        'shop_id' => $shopId,
                        'page' => $page,
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
            } while (!empty($items) && count($items) === $size);
        }

        Log::info('Uzum fetchAllFinanceOrders completed', [
            'account_id' => $account->id,
            'total_items' => count($allItems),
        ]);

        return $allItems;
    }

    /**
     * Map finance order item to model data
     *
     * @param array $item Raw order item from API
     * @return array Mapped data for UzumFinanceOrder model
     */
    public function mapFinanceOrderData(array $item): array
    {
        // Дата из миллисекунд в timestamp
        $orderDate = isset($item['date']) ? $this->convertTimestamp($item['date']) : null;
        $dateIssued = isset($item['dateIssued']) ? $this->convertTimestamp($item['dateIssued']) : null;

        // Получаем URL изображения (берём первый доступный размер)
        $imageUrl = null;
        $photo = $item['productImage']['photo'] ?? [];
        foreach (['540', '480', '240', '800', '720'] as $size) {
            if (isset($photo[$size]['high'])) {
                $imageUrl = $photo[$size]['high'];
                break;
            }
        }

        return [
            'uzum_id' => $item['id'] ?? null,
            'order_id' => $item['orderId'] ?? null,
            'shop_id' => $item['shopId'] ?? null,
            'product_id' => $item['productId'] ?? null,
            'sku_title' => $item['skuTitle'] ?? $item['productTitle'] ?? null,
            'product_image_url' => $imageUrl,
            'status' => $item['status'] ?? null,
            'status_normalized' => $this->mapFinanceOrderStatus($item['status'] ?? null),
            'sell_price' => $item['sellPrice'] ?? 0,
            'purchase_price' => $item['purchasePrice'] ?? null,
            'commission' => $item['commission'] ?? 0,
            'seller_profit' => $item['sellerProfit'] ?? 0,
            'logistic_delivery_fee' => $item['logisticDeliveryFee'] ?? 0,
            'withdrawn_profit' => $item['withdrawnProfit'] ?? 0,
            'amount' => $item['amount'] ?? 0,
            'amount_returns' => $item['amountReturns'] ?? 0,
            'order_date' => $orderDate,
            'date_issued' => $dateIssued,
            'comment' => $item['comment'] ?? null,
            'return_cause' => $item['returnCause'] ?? null,
            'raw_data' => $item,
        ];
    }

    /**
     * Map finance order status to normalized status
     */
    protected function mapFinanceOrderStatus(?string $status): ?string
    {
        if (!$status) {
            return null;
        }

        return match (strtoupper($status)) {
            'PROCESSING' => 'processing',
            'COMPLETED' => 'delivered',
            'CANCELED' => 'cancelled',
            default => strtolower($status),
        };
    }

    /**
     * Convert Uzum timestamp (milliseconds) to Carbon datetime
     */
    protected function convertTimestamp($timestamp): ?\Carbon\Carbon
    {
        if (!$timestamp) {
            return null;
        }

        // Uzum returns timestamps in milliseconds
        $seconds = $timestamp > 9999999999 ? $timestamp / 1000 : $timestamp;

        return \Carbon\Carbon::createFromTimestamp((int) $seconds);
    }

    // ========== Finance Expenses API ==========

    /**
     * Fetch finance expenses from Uzum Finance API
     * Расходы маркетплейса: комиссии, логистика, штрафы и т.д.
     *
     * API endpoint: GET /v1/finance/expenses
     * Response structure: { payload: { payments: [...] } }
     * Payment item: { id, name, source, shopId, paymentPrice, amount, dateCreated, dateUpdated, status }
     *
     * @param MarketplaceAccount $account
     * @param array $shopIds Shop IDs to fetch expenses for
     * @param int|null $dateFromMs Start date in milliseconds
     * @param int|null $dateToMs End date in milliseconds
     * @param int $page Page number (0-based)
     * @param int $size Page size (max 100)
     * @return array ['expenses' => [...], 'totalElements' => int]
     */
    public function fetchFinanceExpenses(
        MarketplaceAccount $account,
        array $shopIds = [],
        ?int $dateFromMs = null,
        ?int $dateToMs = null,
        int $page = 0,
        int $size = 100
    ): array {
        $shopIds = $this->resolveShopIds($account, $shopIds);

        if (empty($shopIds)) {
            Log::warning('Uzum fetchFinanceExpenses: no shop IDs', ['account_id' => $account->id]);
            return ['expenses' => [], 'totalElements' => 0];
        }

        $allExpenses = [];
        $totalElements = 0;

        foreach ($shopIds as $shopId) {
            try {
                $query = [
                    'page' => $page,
                    'size' => min($size, 100),
                    'shopIds' => [$shopId],
                ];

                if ($dateFromMs !== null) {
                    $query['dateFrom'] = $dateFromMs;
                }
                if ($dateToMs !== null) {
                    $query['dateTo'] = $dateToMs;
                }

                $response = $this->request($account, 'GET', '/v1/finance/expenses', $query);

                // API returns: { payload: { payments: [...] } }
                $payload = $response['payload'] ?? $response;
                $expenses = $payload['payments'] ?? $payload['expenses'] ?? [];
                $total = $response['totalElements'] ?? count($expenses);

                Log::info('Uzum fetchFinanceExpenses response', [
                    'account_id' => $account->id,
                    'shop_id' => $shopId,
                    'page' => $page,
                    'expenses_count' => count($expenses),
                    'total_elements' => $total,
                    'response_keys' => array_keys($response),
                    'sample' => !empty($expenses) ? array_slice($expenses, 0, 2) : [],
                ]);

                $allExpenses = array_merge($allExpenses, $expenses);
                $totalElements = max($totalElements, $total);
            } catch (\Throwable $e) {
                Log::warning('Uzum fetchFinanceExpenses failed for shop', [
                    'account_id' => $account->id,
                    'shop_id' => $shopId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'expenses' => $allExpenses,
            'totalElements' => $totalElements,
        ];
    }

    /**
     * Fetch all finance expenses with pagination
     * Автоматически обходит все страницы
     *
     * @param MarketplaceAccount $account
     * @param array $shopIds Shop IDs
     * @param int|null $dateFromMs Start date in milliseconds
     * @param int|null $dateToMs End date in milliseconds
     * @param int $maxPages Max pages to fetch (0 = unlimited)
     * @return array All expense items
     */
    public function fetchAllFinanceExpenses(
        MarketplaceAccount $account,
        array $shopIds = [],
        ?int $dateFromMs = null,
        ?int $dateToMs = null,
        int $maxPages = 0
    ): array {
        $shopIds = $this->resolveShopIds($account, $shopIds);
        $allExpenses = [];
        $size = 100;

        foreach ($shopIds as $shopId) {
            $page = 0;
            $pagesLoaded = 0;

            do {
                try {
                    $response = $this->fetchFinanceExpenses($account, [$shopId], $dateFromMs, $dateToMs, $page, $size);
                    $expenses = $response['expenses'] ?? [];

                    $allExpenses = array_merge($allExpenses, $expenses);
                    $pagesLoaded++;
                    $page++;

                    // Rate limit protection
                    if (!empty($expenses)) {
                        usleep(200000); // 200ms
                    }

                    if ($maxPages > 0 && $pagesLoaded >= $maxPages) {
                        break;
                    }
                } catch (\Throwable $e) {
                    Log::warning('Uzum fetchAllFinanceExpenses page failed', [
                        'account_id' => $account->id,
                        'shop_id' => $shopId,
                        'page' => $page,
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
            } while (!empty($expenses) && count($expenses) === $size);
        }

        Log::info('Uzum fetchAllFinanceExpenses completed', [
            'account_id' => $account->id,
            'total_expenses' => count($allExpenses),
        ]);

        return $allExpenses;
    }

    /**
     * Map finance expense item to structured data
     *
     * @param array $expense Raw expense item from API
     * @return array Mapped expense data
     */
    public function mapFinanceExpenseData(array $expense): array
    {
        $expenseDate = isset($expense['date']) ? $this->convertTimestamp($expense['date']) : null;

        return [
            'expense_id' => $expense['id'] ?? null,
            'shop_id' => $expense['shopId'] ?? null,
            'type' => $expense['type'] ?? null,
            'type_label' => $this->mapExpenseTypeLabel($expense['type'] ?? null),
            'category' => $expense['category'] ?? null,
            'amount' => $expense['amount'] ?? 0,
            'expense_date' => $expenseDate,
            'description' => $expense['description'] ?? null,
            'order_id' => $expense['orderId'] ?? null,
            'product_id' => $expense['productId'] ?? null,
            'raw_data' => $expense,
        ];
    }

    /**
     * Map expense type to Russian label
     */
    protected function mapExpenseTypeLabel(?string $type): string
    {
        if (!$type) {
            return 'Прочее';
        }

        return match (strtoupper($type)) {
            'COMMISSION' => 'Комиссия маркетплейса',
            'LOGISTICS', 'DELIVERY' => 'Логистика',
            'STORAGE' => 'Хранение',
            'ADVERTISING', 'ADS' => 'Реклама',
            'PENALTY' => 'Штраф',
            'RETURN' => 'Возврат',
            'SERVICE' => 'Услуги',
            'FEE' => 'Сбор',
            default => $type,
        };
    }

    /**
     * Get expenses summary for a period
     *
     * API returns payments with 'source' field for categorization.
     * Known Uzum sources (in Uzbek):
     * - Marketing = Реклама (advertising)
     * - Logistika = Логистика (logistics)
     * - Ombor = Хранение (storage)
     * - Obuna = Подписка (subscription/services)
     * - Uzum Market = Штрафы и комиссии (penalties/commission)
     * - Fotostudiya = Фотостудия (other services)
     * - Tovarlarni tayyorlash markazi = Подготовка товаров (other services)
     *
     * Note: Uzum expenses API dateFrom/dateTo filters are unreliable (return empty results).
     * We fetch all expenses and filter locally by dateService field.
     *
     * @param MarketplaceAccount $account
     * @param \DateTimeInterface $from
     * @param \DateTimeInterface $to
     * @return array Summary of expenses by type
     */
    public function getExpensesSummary(
        MarketplaceAccount $account,
        \DateTimeInterface $from,
        \DateTimeInterface $to
    ): array {
        $dateFromMs = $from->getTimestamp() * 1000;
        $dateToMs = $to->getTimestamp() * 1000;

        // Fetch ALL expenses without date filter (API date filters are unreliable)
        // Then filter locally by dateService field
        $allExpenses = $this->fetchAllFinanceExpenses($account, [], null, null);

        // Filter by dateService (the actual service date, not creation date)
        $expenses = array_filter($allExpenses, function($expense) use ($dateFromMs, $dateToMs) {
            $dateService = $expense['dateService'] ?? $expense['dateCreated'] ?? 0;
            return $dateService >= $dateFromMs && $dateService <= $dateToMs;
        });

        $summary = [
            'total' => 0,
            'commission' => 0,
            'logistics' => 0,
            'storage' => 0,
            'advertising' => 0,
            'penalties' => 0,
            'returns' => 0,
            'other' => 0,
            'items_count' => count($expenses),
            'currency' => 'UZS',
            'sources' => [], // Debug: track all unique sources with amounts
        ];

        foreach ($expenses as $expense) {
            // API returns 'paymentPrice' for amount, 'source' for category
            $amount = abs((float) ($expense['paymentPrice'] ?? $expense['amount'] ?? 0));
            $source = $expense['source'] ?? '';
            $sourceLower = strtolower($source);
            $name = strtolower($expense['name'] ?? '');

            // Track unique sources with amounts for debugging
            if ($source) {
                if (!isset($summary['sources'][$source])) {
                    $summary['sources'][$source] = 0;
                }
                $summary['sources'][$source] += $amount;
            }

            $summary['total'] += $amount;

            // Categorize by Uzum source field (Uzbek language)
            // Marketing = Реклама, targibot = продвижение
            if ($sourceLower === 'marketing' || str_contains($name, 'targibot') || str_contains($name, 'reklama')) {
                $summary['advertising'] += $amount;
            }
            // Logistika = Логистика
            elseif ($sourceLower === 'logistika' || str_contains($name, 'logistika') || str_contains($name, 'yetkazish')) {
                $summary['logistics'] += $amount;
            }
            // Ombor = Склад/Хранение, saqlash = хранение
            elseif ($sourceLower === 'ombor' || str_contains($name, 'saqlash') || str_contains($name, 'ombor')) {
                $summary['storage'] += $amount;
            }
            // Uzum Market = Штрафы (jarima = штраф)
            elseif ($sourceLower === 'uzum market' || str_contains($name, 'jarima') || str_contains($name, 'штраф')) {
                $summary['penalties'] += $amount;
            }
            // Obuna = Подписка, treat as commission/service fee
            elseif ($sourceLower === 'obuna') {
                $summary['commission'] += $amount;
            }
            // Qaytarish = Возврат
            elseif (str_contains($name, 'qaytarish') || str_contains($name, 'возврат') || str_contains($name, 'return')) {
                $summary['returns'] += $amount;
            }
            // Everything else goes to 'other'
            else {
                $summary['other'] += $amount;
            }
        }

        Log::info('Uzum getExpensesSummary completed', [
            'account_id' => $account->id,
            'from' => $from->format('Y-m-d'),
            'to' => $to->format('Y-m-d'),
            'items_count' => $summary['items_count'],
            'total' => $summary['total'],
            'by_source' => $summary['sources'],
        ]);

        return $summary;
    }

}
