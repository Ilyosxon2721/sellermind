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

        foreach ($shopIds as $shopId) {
            $page = 0;
            do {
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

                foreach ($list as $product) {
                    $this->storeProduct($account, $shopId, $product);
                    $synced++;
                }

                $page++;
            } while (!empty($list));
        }

        return ['synced' => $synced, 'shops' => $shopIds];
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
        // from explicit argument or account->shop_id
        $shopIds = $this->normalizeShopIds($shopIds);
        if (!empty($shopIds)) {
            return $shopIds;
        }

        $shopIds = $this->normalizeShopIds(
            is_array($account->shop_id) ? $account->shop_id : explode(',', (string) $account->shop_id)
        );
        if (!empty($shopIds)) {
            return $shopIds;
        }

        // from DB cache
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

        throw new \RuntimeException('Не найдено ни одного магазина Uzum. Зайдите в настройки Uzum, обновите токен или выберите магазин.');
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
        $ids = array_values(array_filter($ids, fn ($v) => $v !== null && $v !== ''));
        return array_map(fn ($v) => is_numeric($v) ? (int) $v : (string) $v, $ids);
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
            
            throw new \RuntimeException("Ошибка обновления остатка Uzum SKU {$skuId}: {$e->getMessage()}");
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
            'internal_statuses' => $internalStatuses,
            'external_statuses_count' => count($statuses),
            'external_statuses' => $statuses,
        ]);

        // Активные статусы и отмены (заказы в работе + отмененные) - загружаем ВСЕ без фильтрации по дате
        // ВАЖНО: CANCELED и PENDING_CANCELLATION тоже должны загружаться без фильтра по дате,
        // чтобы обновлять старые заказы, которые были отменены
        $activeStatuses = [
            'CREATED',
            'PACKING',
            'PENDING_DELIVERY',
            'DELIVERING',
            'ACCEPTED_AT_DP',
            'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
            'CANCELED',  // Добавлено для обновления отмененных заказов
            'PENDING_CANCELLATION',  // Добавлено для обновления заказов в процессе отмены
        ];

        foreach ($statuses as $status) {
            $page = 0;
            $isActiveStatus = in_array($status, $activeStatuses);

            try {
                $stopStatus = false;
                do {
                    $query = [
                        'page' => $page,
                        'size' => $size,
                        'status' => $status,
                        'shopIds' => $shopIds,
                    ];

                    Log::info('Uzum API fetching orders for status', [
                        'status' => $status,
                        'page' => $page,
                        'size' => $size,
                        'shopIds_count' => count($shopIds),
                        'is_active_status' => $isActiveStatus,
                    ]);

                    $response = $this->request($account, 'GET', $path, $query);
                    $payload = $response['payload'] ?? [];
                    $list = $payload['orders'] ?? $payload['list'] ?? [];

                    Log::info('Uzum API response for status', [
                        'status' => $status,
                        'page' => $page,
                        'orders_received' => count($list),
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
                    'path' => $path,
                    'error' => $e->getMessage(),
                ]);
                // продолжим остальные статусы
            }
        }

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

                $hint = 'Проверьте, что токен Uzum активен и имеет доступ к заказам/финансам. ' .
                    'Uzum требует отправлять токен без Bearer-префикса.';

                $message = $errorInfo ?: 'Access denied';
                throw new \RuntimeException("Uzum API 403: {$message}. {$hint}");
            }

            // 429 Too Many Requests - rate limit exceeded
            if ($status === 429) {
                Log::warning('Uzum rate limit hit', [
                    'account_id' => $account->id,
                    'url' => $url,
                ]);

                throw new \RuntimeException("Uzum API rate limit (429): Превышен лимит запросов. Подождите минуту и попробуйте снова.");
            }

            $message = $errorInfo ?: mb_substr(trim($rawBody), 0, 300);
            throw new \RuntimeException("Uzum API error ({$status}): {$message}");
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

    protected function extractShopIds(MarketplaceAccount $account): array
    {
        $shopIds = [];
        // Приоритет: shop_id из аккаунта
        if (!empty($account->shop_id)) {
            $ids = is_array($account->shop_id) ? $account->shop_id : explode(',', (string) $account->shop_id);
            $shopIds = array_values(array_filter(array_map('trim', $ids), fn ($v) => $v !== ''));
        }
        // Если shop_id не задан, пробуем взять из таблицы shops
        if (empty($shopIds)) {
            $shopIds = \App\Models\MarketplaceShop::where('marketplace_account_id', $account->id)
                ->pluck('external_id')
                ->filter()
                ->values()
                ->all();
        }
        return array_values(array_filter(array_map(fn ($v) => is_numeric($v) ? (int) $v : $v, $shopIds)));
    }
}
