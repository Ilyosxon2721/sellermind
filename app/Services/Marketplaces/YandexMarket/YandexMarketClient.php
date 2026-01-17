<?php

namespace App\Services\Marketplaces\YandexMarket;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Services\Marketplaces\MarketplaceClientInterface;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;

/**
 * Клиент для интеграции с Yandex Market Partner API
 */
class YandexMarketClient implements MarketplaceClientInterface
{
    protected YandexMarketHttpClient $http;

    public function __construct(YandexMarketHttpClient $http)
    {
        $this->http = $http;
    }

    public function getMarketplaceCode(): string
    {
        return 'ym';
    }

    /**
     * Проверка подключения к API
     */
    public function ping(MarketplaceAccount $account): array
    {
        $start = microtime(true);

        try {
            // Получаем информацию о кампаниях
            $response = $this->http->get($account, '/campaigns');
            $duration = round((microtime(true) - $start) * 1000);

            $campaigns = $response['campaigns'] ?? [];

            // Также получим список бизнесов
            $businesses = $this->getBusinesses($account);
            $configuredBusinessId = $this->http->getBusinessId($account);

            return [
                'success' => true,
                'message' => 'Yandex Market API доступен',
                'response_time_ms' => $duration,
                'campaigns_count' => count($campaigns),
                'campaigns' => array_map(fn($c) => [
                    'id' => $c['id'] ?? null,
                    'name' => $c['domain'] ?? $c['clientId'] ?? 'Campaign',
                ], array_slice($campaigns, 0, 5)),
                'businesses_count' => count($businesses),
                'businesses' => array_map(fn($b) => [
                    'id' => $b['id'] ?? null,
                    'name' => $b['name'] ?? 'Business',
                ], array_slice($businesses, 0, 5)),
                'configured_business_id' => $configuredBusinessId,
                'business_id_valid' => $configuredBusinessId && in_array($configuredBusinessId, array_column($businesses, 'id')),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Ошибка подключения: ' . $e->getMessage(),
                'response_time_ms' => round((microtime(true) - $start) * 1000),
            ];
        }
    }

    /**
     * Тест подключения (алиас для ping)
     */
    public function testConnection(MarketplaceAccount $account): array
    {
        return $this->ping($account);
    }

    /**
     * Получить список бизнесов, доступных для API-ключа
     */
    public function getBusinesses(MarketplaceAccount $account): array
    {
        try {
            $response = $this->http->get($account, '/businesses');
            return $response['businesses'] ?? [];
        } catch (\Exception $e) {
            Log::warning('YandexMarket: Failed to fetch businesses', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Загрузка товаров из Yandex Market
     */
    public function fetchProducts(MarketplaceAccount $account, array $shopIds = [], int $pageSize = 100): array
    {
        $businessId = $this->http->getBusinessId($account);

        if (!$businessId) {
            // Попробуем получить business_id автоматически
            $businesses = $this->getBusinesses($account);
            if (!empty($businesses)) {
                $businessId = $businesses[0]['id'] ?? null;
                Log::info('YandexMarket: Auto-detected business_id', [
                    'account_id' => $account->id,
                    'business_id' => $businessId,
                    'available_businesses' => count($businesses),
                ]);
            }

            if (!$businessId) {
                throw new \RuntimeException('Business ID не настроен для аккаунта и не удалось определить автоматически');
            }
        }

        $all = [];
        $pageToken = null;

        do {
            $body = [
                'limit' => min($pageSize, 200),
            ];
            
            if ($pageToken) {
                $body['page_token'] = $pageToken;
            }

            $response = $this->http->post(
                $account,
                "/businesses/{$businessId}/offer-mappings",
                $body
            );

            $offers = $response['result']['offerMappings'] ?? [];
            
            foreach ($offers as $offer) {
                $all[] = $offer;
            }

            $pageToken = $response['result']['paging']['nextPageToken'] ?? null;

        } while ($pageToken && count($offers) > 0);

        return $all;
    }

    /**
     * Синхронизация каталога товаров
     */
    public function syncCatalog(MarketplaceAccount $account): array
    {
        $products = $this->fetchProducts($account);
        $synced = 0;

        foreach ($products as $offerMapping) {
            $this->storeProduct($account, $offerMapping);
            $synced++;
        }

        // Sync stocks after products
        $this->syncStocksFromMarketplace($account);

        return [
            'synced' => $synced,
            'marketplace' => 'yandex_market',
        ];
    }

    /**
     * Fetch stocks from YM and update local products
     */
    protected function syncStocksFromMarketplace(MarketplaceAccount $account): void
    {
        try {
            // Get all offer IDs from our database for this account
            $offerIds = MarketplaceProduct::where('marketplace_account_id', $account->id)
                ->whereNotNull('external_offer_id')
                ->pluck('external_offer_id')
                ->toArray();
            
            if (empty($offerIds)) {
                return;
            }
            
            // Fetch stocks in batches of 500 (YM API limit)
            $batches = array_chunk($offerIds, 500);
            
            foreach ($batches as $batchOfferIds) {
                $warehouses = $this->getStocks($account, $batchOfferIds);
                
                foreach ($warehouses as $warehouse) {
                    $offers = $warehouse['offers'] ?? [];
                    foreach ($offers as $offer) {
                        $offerId = $offer['offerId'] ?? null;
                        $stocks = $offer['stocks'] ?? [];
                        
                        // Get AVAILABLE stock type, or sum all
                        $totalStock = 0;
                        foreach ($stocks as $stock) {
                            if (($stock['type'] ?? '') === 'AVAILABLE') {
                                $totalStock = (int) ($stock['count'] ?? 0);
                                break;
                            }
                        }
                        // If no AVAILABLE found, sum FIT
                        if ($totalStock === 0) {
                            foreach ($stocks as $stock) {
                                if (($stock['type'] ?? '') === 'FIT') {
                                    $totalStock = (int) ($stock['count'] ?? 0);
                                    break;
                                }
                            }
                        }
                        
                        if ($offerId) {
                            MarketplaceProduct::where('marketplace_account_id', $account->id)
                                ->where('external_offer_id', (string) $offerId)
                                ->update(['last_synced_stock' => $totalStock]);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to sync stocks from YM: ' . $e->getMessage());
        }
    }

    /**
     * Сохранить товар в БД
     */
    protected function storeProduct(MarketplaceAccount $account, array $offerMapping): void
    {
        $offer = $offerMapping['offer'] ?? [];
        $mapping = $offerMapping['mapping'] ?? [];
        
        $offerId = $offer['offerId'] ?? null;
        if (!$offerId) {
            return;
        }

        $marketSku = $mapping['marketSku'] ?? null;
        $status = $this->mapProductStatus($offerMapping);

        // Цена
        $price = null;
        if (isset($offer['basicPrice']['value'])) {
            $price = (float) $offer['basicPrice']['value'];
        }

        MarketplaceProduct::updateOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'external_offer_id' => (string) $offerId,
            ],
            [
                'external_product_id' => $marketSku ? (string) $marketSku : null,
                'status' => $status,
                'title' => $offer['name'] ?? null,
                'category' => $mapping['categoryName'] ?? null,
                'preview_image' => $offer['pictures'][0] ?? null,
                'last_synced_price' => $price,
                'last_synced_at' => now(),
                'raw_payload' => $offerMapping,
            ]
        );
    }

    /**
     * Маппинг статуса товара
     */
    protected function mapProductStatus(array $offerMapping): string
    {
        $awaitingModerationMapping = $offerMapping['awaitingModerationMapping'] ?? null;
        $rejectedMapping = $offerMapping['rejectedMapping'] ?? null;
        $mapping = $offerMapping['mapping'] ?? null;

        if ($rejectedMapping) {
            return 'rejected';
        }
        if ($awaitingModerationMapping) {
            return 'moderation';
        }
        if ($mapping && isset($mapping['marketSku'])) {
            return 'active';
        }
        
        return 'draft';
    }

    /**
     * Загрузка заказов
     */
    public function fetchOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to, bool $includeTest = true): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен для аккаунта');
        }

        $allOrders = [];

        // Fetch real orders (without date filter for reliability)
        $realOrders = $this->fetchOrdersWithoutDateFilter($account, $campaignId, false);
        $allOrders = array_merge($allOrders, $realOrders);

        // Fetch test orders if requested
        if ($includeTest) {
            $testOrders = $this->fetchOrdersWithoutDateFilter($account, $campaignId, true);
            $allOrders = array_merge($allOrders, $testOrders);
        }

        return $allOrders;
    }

    /**
     * Fetch orders with specific params
     */
    protected function fetchOrdersWithParams(MarketplaceAccount $account, string $campaignId, DateTimeInterface $from, DateTimeInterface $to, bool $fake): array
    {
        $orders = [];
        $page = 1;
        $pageSize = 50;

        do {
            $params = [
                'fromDate' => $from->format('d-m-Y'),
                'toDate' => $to->format('d-m-Y'),
                'page' => $page,
                'pageSize' => $pageSize,
            ];

            if ($fake) {
                $params['fake'] = 'true';
            }

            try {
                $response = $this->http->get(
                    $account,
                    "/campaigns/{$campaignId}/orders",
                    $params
                );

                $orderList = $response['orders'] ?? [];

                foreach ($orderList as $order) {
                    $orders[] = $this->mapOrderData($order);
                }

                $page++;
                $hasMore = count($orderList) === $pageSize;
            } catch (\Exception $e) {
                // If date range is invalid, try without date filter
                if ($page === 1 && strpos($e->getMessage(), 'interval') !== false) {
                    return $this->fetchOrdersWithoutDateFilter($account, $campaignId, $fake);
                }
                throw $e;
            }

        } while ($hasMore);

        return $orders;
    }

    /**
     * Fetch orders without date filter (fallback)
     */
    protected function fetchOrdersWithoutDateFilter(MarketplaceAccount $account, string $campaignId, bool $fake): array
    {
        $orders = [];
        $page = 1;
        $pageSize = 50;

        do {
            $params = [
                'page' => $page,
                'pageSize' => $pageSize,
            ];

            if ($fake) {
                $params['fake'] = 'true';
            }

            $response = $this->http->get(
                $account,
                "/campaigns/{$campaignId}/orders",
                $params
            );

            $orderList = $response['orders'] ?? [];

            foreach ($orderList as $order) {
                $orders[] = $this->mapOrderData($order);
            }

            $page++;
            $hasMore = count($orderList) === $pageSize;

        } while ($hasMore);

        return $orders;
    }

    /**
     * Маппинг данных заказа в стандартный формат
     */
    public function mapOrderData(array $orderData): array
    {
        $items = [];
        $calculatedTotal = 0;
        
        foreach ($orderData['items'] ?? [] as $item) {
            $price = (float) ($item['buyerPrice'] ?? $item['price'] ?? 0);
            $count = (int) ($item['count'] ?? 1);
            $itemTotal = $price * $count;
            $calculatedTotal += $itemTotal;
            
            $items[] = [
                'external_offer_id' => (string) ($item['offerId'] ?? ''),
                'name' => $item['offerName'] ?? null,
                'quantity' => $count,
                'price' => $price,
                'total_price' => $itemTotal,
                'raw_payload' => $item,
            ];
        }

        $buyer = $orderData['buyer'] ?? [];
        $delivery = $orderData['delivery'] ?? [];
        
        // Build customer name from available fields
        $customerName = trim(($buyer['firstName'] ?? '') . ' ' . ($buyer['lastName'] ?? ''));
        if (empty($customerName) && isset($delivery['address']['recipient'])) {
            $customerName = $delivery['address']['recipient'];
        }

        // Use order total if available, otherwise use calculated total
        $totalAmount = isset($orderData['total']) ? (float) $orderData['total'] : $calculatedTotal;

        return [
            'external_order_id' => isset($orderData['id']) ? (string) $orderData['id'] : null,
            'status' => $orderData['status'] ?? null,
            'status_normalized' => $this->mapOrderStatus($orderData['status'] ?? null),
            'substatus' => $orderData['substatus'] ?? null,
            'customer_name' => $customerName,
            'customer_phone' => $buyer['phone'] ?? null,
            'total_amount' => $totalAmount,
            'currency' => $orderData['currency'] ?? 'RUR',
            'ordered_at' => $orderData['creationDate'] ?? null,
            'items' => $items,
            'delivery_type' => $delivery['type'] ?? null,
            'delivery_service' => $delivery['serviceName'] ?? null,
            'raw_payload' => $orderData,
        ];
    }

    /**
     * Маппинг статусов заказа Yandex → SellerMind
     */
    protected function mapOrderStatus(?string $status): string
    {
        return match ($status) {
            'PROCESSING' => 'new',
            'DELIVERY' => 'shipped',
            'PICKUP' => 'waiting_pickup',
            'DELIVERED' => 'delivered',
            'CANCELLED' => 'cancelled',
            'RETURNED' => 'returned',
            'UNPAID' => 'pending',
            'PENDING' => 'pending',
            'RESERVED' => 'new',
            default => 'unknown',
        };
    }

    /**
     * Синхронизация заказов - сохранение в БД
     */
    public function syncOrders(MarketplaceAccount $account, ?\DateTimeInterface $from = null, ?\DateTimeInterface $to = null): array
    {
        $from = $from ?? now()->subDays(30);
        $to = $to ?? now();

        $ordersData = $this->fetchOrders($account, $from, $to);
        $synced = 0;
        $errors = [];

        foreach ($ordersData as $orderData) {
            try {
                $this->storeOrder($account, $orderData);
                $synced++;
            } catch (\Exception $e) {
                $errors[] = [
                    'order_id' => $orderData['external_order_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'synced' => $synced,
            'total' => count($ordersData),
            'errors' => $errors,
            'marketplace' => 'yandex_market',
        ];
    }

    /**
     * Сохранить заказ в БД
     */
    protected function storeOrder(MarketplaceAccount $account, array $orderData): void
    {
        $orderId = $orderData['external_order_id'] ?? null;
        if (!$orderId) {
            return;
        }

        // Check if order already exists
        $existingOrder = \App\Models\YandexMarketOrder::where('marketplace_account_id', $account->id)
            ->where('order_id', $orderId)
            ->first();

        $isNewOrder = !$existingOrder;
        $status = $orderData['status'] ?? null;

        // Save order
        $order = \App\Models\YandexMarketOrder::updateOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'order_id' => $orderId,
            ],
            [
                'status' => $status,
                'status_normalized' => $orderData['status_normalized'] ?? null,
                'substatus' => $orderData['substatus'] ?? null,
                'total_price' => $orderData['total_amount'] ?? 0,
                'currency' => $orderData['currency'] ?? 'RUR',
                'customer_name' => $orderData['customer_name'] ?? null,
                'customer_phone' => $orderData['customer_phone'] ?? null,
                'delivery_type' => $orderData['delivery_type'] ?? null,
                'delivery_service' => $orderData['delivery_service'] ?? null,
                'items_count' => count($orderData['items'] ?? []),
                'order_data' => $orderData['raw_payload'] ?? $orderData,
                'created_at_ym' => isset($orderData['ordered_at']) ? \Carbon\Carbon::parse($orderData['ordered_at']) : null,
            ]
        );

        // Process stock for new orders in PROCESSING status
        if ($isNewOrder && in_array($status, ['PROCESSING', 'DELIVERY', 'PICKUP'])) {
            $this->processOrderStock($account, $orderData['items'] ?? [], 'decrement');
        }

        // If order was cancelled, restore stock
        if (!$isNewOrder && $existingOrder->status !== 'CANCELLED' && $status === 'CANCELLED') {
            $this->processOrderStock($account, $orderData['items'] ?? [], 'increment');
        }
    }

    /**
     * Обработать остатки при получении/отмене заказа
     */
    protected function processOrderStock(MarketplaceAccount $account, array $items, string $action = 'decrement'): void
    {
        foreach ($items as $item) {
            $offerId = $item['offerId'] ?? $item['shopSku'] ?? null;
            $count = $item['count'] ?? 1;

            if (!$offerId) {
                continue;
            }

            // Find linked variant by external_offer_id
            $link = \App\Models\VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $account->id)
                ->where('external_offer_id', $offerId)
                ->where('is_active', true)
                ->with('variant')
                ->first();

            if ($link && $link->variant) {
                \Illuminate\Support\Facades\Log::info("Order stock {$action}", [
                    'offer_id' => $offerId,
                    'count' => $count,
                    'variant_id' => $link->variant->id,
                    'current_stock' => $link->variant->stock_default,
                ]);

                if ($action === 'decrement') {
                    $link->variant->decrementStock($count);
                } else {
                    $link->variant->incrementStock($count);
                }
            }
        }
    }

    /**
     * Синхронизация товаров (заглушка)
     */
    public function syncProducts(MarketplaceAccount $account, array $products): void
    {
        // TODO: Реализовать отправку товаров в Yandex Market
        foreach ($products as $marketplaceProduct) {
            try {
                // TODO: Map и отправить
                $marketplaceProduct->markAsSynced();
            } catch (\Exception $e) {
                $marketplaceProduct->markAsFailed($e->getMessage());
            }
        }
    }

    /**
     * Синхронизация цен
     */
    public function syncPrices(MarketplaceAccount $account, array $products): void
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        $offers = [];

        foreach ($products as $marketplaceProduct) {
            if (!$marketplaceProduct->external_offer_id) {
                continue;
            }

            $product = $marketplaceProduct->product;
            if (!$product || !$product->price) {
                continue;
            }

            $offers[] = [
                'offerId' => $marketplaceProduct->external_offer_id,
                'price' => [
                    'value' => (float) $product->price,
                    'currencyId' => 'RUR',
                ],
            ];
        }

        if (empty($offers)) {
            return;
        }

        // Разбиваем на батчи по 500
        foreach (array_chunk($offers, 500) as $batch) {
            $this->http->post(
                $account,
                "/campaigns/{$campaignId}/offer-prices/updates",
                ['offers' => $batch]
            );
        }
    }

    /**
     * Синхронизация остатков
     */
    public function syncStocks(MarketplaceAccount $account, array $products): void
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        $skus = [];

        foreach ($products as $marketplaceProduct) {
            if (!$marketplaceProduct->external_offer_id) {
                continue;
            }

            $product = $marketplaceProduct->product;
            $stock = $product?->stock_quantity ?? 0;

            $skus[] = [
                'sku' => $marketplaceProduct->external_offer_id,
                'items' => [
                    [
                        'count' => (int) $stock,
                        'type' => 'FIT',
                        'updatedAt' => now()->toIso8601String(),
                    ],
                ],
            ];
        }

        if (empty($skus)) {
            return;
        }

        // Разбиваем на батчи по 500
        foreach (array_chunk($skus, 500) as $batch) {
            $this->http->put(
                $account,
                "/campaigns/{$campaignId}/offers/stocks",
                ['skus' => $batch]
            );
        }
    }

    /**
     * Получить информацию о товаре
     */
    public function getProductInfo(MarketplaceAccount $account, string $offerId): ?array
    {
        $businessId = $this->http->getBusinessId($account);
        
        if (!$businessId) {
            return null;
        }

        try {
            $response = $this->http->post(
                $account,
                "/businesses/{$businessId}/offer-mappings",
                [
                    'offerIds' => [$offerId],
                ]
            );

            $mappings = $response['result']['offerMappings'] ?? [];
            return $mappings[0] ?? null;

        } catch (\Exception $e) {
            Log::warning('Failed to get YandexMarket product info', [
                'offer_id' => $offerId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Изменить статус заказа
     */
    public function updateOrderStatus(MarketplaceAccount $account, string $orderId, string $status, ?string $substatus = null): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        $body = [
            'order' => [
                'status' => $status,
            ],
        ];

        if ($substatus) {
            $body['order']['substatus'] = $substatus;
        }

        return $this->http->put(
            $account,
            "/campaigns/{$campaignId}/orders/{$orderId}/status",
            $body
        );
    }

    /**
     * Получить список кампаний (магазинов)
     */
    public function getCampaigns(MarketplaceAccount $account): array
    {
        $response = $this->http->get($account, '/campaigns');
        return $response['campaigns'] ?? [];
    }

    /**
     * Получить информацию о кампании
     */
    public function getCampaignInfo(MarketplaceAccount $account, string $campaignId): ?array
    {
        try {
            $response = $this->http->get($account, "/campaigns/{$campaignId}");
            return $response['campaign'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ================== ORDER ACTIONS ==================

    /**
     * Изменить статус заказа на "Готов к отгрузке"
     * PUT /campaigns/{campaignId}/orders/{orderId}/status
     */
    public function setOrderReadyToShip(MarketplaceAccount $account, string $orderId): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        $response = $this->http->put(
            $account,
            "/campaigns/{$campaignId}/orders/{$orderId}/status",
            [
                'order' => [
                    'status' => 'PROCESSING',
                    'substatus' => 'READY_TO_SHIP',
                ]
            ]
        );

        // Update local order status
        \App\Models\YandexMarketOrder::where('marketplace_account_id', $account->id)
            ->where('order_id', $orderId)
            ->update([
                'status' => 'PROCESSING',
                'substatus' => 'READY_TO_SHIP',
                'status_normalized' => 'ready_to_ship',
            ]);

        return $response;
    }

    /**
     * Получить ярлыки для заказа (PDF)
     * GET /campaigns/{campaignId}/orders/{orderId}/delivery/labels
     * 
     * @param string $format - A6, A7 (default), A4
     * @return string - PDF content (binary)
     */
    public function getOrderLabels(MarketplaceAccount $account, string $orderId, string $format = 'A7'): string
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        // Make raw request for PDF
        return $this->http->getRaw(
            $account,
            "/campaigns/{$campaignId}/orders/{$orderId}/delivery/labels",
            ['format' => $format]
        );
    }

    /**
     * Получить данные для ярлыков
     * GET /campaigns/{campaignId}/orders/{orderId}/delivery/labels/data
     */
    public function getOrderLabelsData(MarketplaceAccount $account, string $orderId): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        return $this->http->get(
            $account,
            "/campaigns/{$campaignId}/orders/{$orderId}/delivery/labels/data"
        );
    }

    /**
     * Подготовка заказа - указать грузоместа (коробки)
     * PUT /campaigns/{campaignId}/orders/{orderId}/boxes
     * 
     * @param array $boxes - массив грузомест, каждое содержит:
     *   - fulfilmentId: string (формат: orderId-boxNumber, например "52195310272-1")
     *   - weight: int (вес в граммах)
     *   - width, height, depth: int (размеры в сантиметрах)
     *   - items: array (товары в этом грузоместе)
     *       - id: int (ID товара из заказа)
     *       - count: int (количество)
     *       - cis: array (коды маркировки, опционально)
     */
    public function setOrderBoxes(MarketplaceAccount $account, string $orderId, array $boxes): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        return $this->http->put(
            $account,
            "/campaigns/{$campaignId}/orders/{$orderId}/boxes",
            ['boxes' => $boxes]
        );
    }

    /**
     * Добавить код маркировки (КИЗ/CIS) для товара
     * Используется в PUT boxes для товаров с обязательной маркировкой
     * 
     * @param array $items - товары из заказа
     * @param array $markingCodes - коды маркировки [itemId => [codes]]
     */
    public function prepareBoxesWithMarking(array $orderItems, array $markingCodes): array
    {
        $boxItems = [];
        foreach ($orderItems as $item) {
            $itemId = $item['id'] ?? null;
            if (!$itemId) continue;

            $boxItem = [
                'id' => $itemId,
                'count' => $item['count'] ?? 1,
            ];

            // Add marking codes if available
            if (isset($markingCodes[$itemId])) {
                $boxItem['cis'] = $markingCodes[$itemId];
            }

            $boxItems[] = $boxItem;
        }

        return $boxItems;
    }

    /**
     * Получить информацию о заказе
     * GET /campaigns/{campaignId}/orders/{orderId}
     */
    public function getOrder(MarketplaceAccount $account, string $orderId): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        $response = $this->http->get(
            $account,
            "/campaigns/{$campaignId}/orders/{$orderId}"
        );

        return $response['order'] ?? [];
    }

    /**
     * Отменить заказ
     * PUT /campaigns/{campaignId}/orders/{orderId}/status
     */
    public function cancelOrder(MarketplaceAccount $account, string $orderId, string $reason = 'SHOP_FAILED'): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        // Get order before cancellation to restore stock
        $order = \App\Models\YandexMarketOrder::where('marketplace_account_id', $account->id)
            ->where('order_id', $orderId)
            ->first();

        $response = $this->http->put(
            $account,
            "/campaigns/{$campaignId}/orders/{$orderId}/status",
            [
                'order' => [
                    'status' => 'CANCELLED',
                    'substatus' => $reason,
                ]
            ]
        );

        // Restore stock if order was not already cancelled
        if ($order && $order->status !== 'CANCELLED') {
            $items = $order->order_data['items'] ?? [];
            $this->processOrderStock($account, $items, 'increment');
        }

        // Update local order status
        \App\Models\YandexMarketOrder::where('marketplace_account_id', $account->id)
            ->where('order_id', $orderId)
            ->update([
                'status' => 'CANCELLED',
                'substatus' => $reason,
                'status_normalized' => 'cancelled',
            ]);

        return $response;
    }

    // ================== STOCK SYNC ==================

    /**
     * Обновить остаток товара на Yandex Market
     * PUT /campaigns/{campaignId}/offers/stocks
     */
    public function updateStock(MarketplaceAccount $account, string $offerId, int $stock, ?int $warehouseId = null): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        // Получить ID склада если не указан
        if (!$warehouseId) {
            $warehouseId = $this->getDefaultWarehouseId($account);
        }

        if (!$warehouseId) {
            throw new \RuntimeException('Не найден склад для обновления остатков');
        }

        $request = [
            'skus' => [
                [
                    'sku' => $offerId,
                    'warehouseId' => $warehouseId,
                    'items' => [
                        [
                            'count' => max(0, $stock),
                            'type' => 'FIT',
                            'updatedAt' => now()->toIso8601String(),
                        ]
                    ]
                ]
            ]
        ];

        $response = $this->http->put(
            $account,
            "/campaigns/{$campaignId}/offers/stocks",
            $request
        );

        return [
            'success' => true,
            'request' => $request,
            'response' => $response,
        ];
    }

    /**
     * Обновить остатки нескольких товаров
     */
    public function updateStockBatch(MarketplaceAccount $account, array $stocks, ?int $warehouseId = null): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        if (!$warehouseId) {
            $warehouseId = $this->getDefaultWarehouseId($account);
        }

        if (!$warehouseId) {
            throw new \RuntimeException('Не найден склад для обновления остатков');
        }

        $skus = [];
        foreach ($stocks as $offerId => $stock) {
            $skus[] = [
                'sku' => $offerId,
                'warehouseId' => $warehouseId,
                'items' => [
                    [
                        'count' => max(0, (int) $stock),
                        'type' => 'FIT',
                        'updatedAt' => now()->toIso8601String(),
                    ]
                ]
            ];
        }

        $request = ['skus' => $skus];

        $response = $this->http->put(
            $account,
            "/campaigns/{$campaignId}/offers/stocks",
            $request
        );

        return [
            'success' => true,
            'count' => count($skus),
            'request' => $request,
            'response' => $response,
        ];
    }

    /**
     * Получить ID склада по умолчанию
     */
    public function getDefaultWarehouseId(MarketplaceAccount $account): ?int
    {
        $warehouses = $this->getWarehouses($account);
        
        // Вернуть первый активный склад
        foreach ($warehouses as $warehouse) {
            if (!empty($warehouse['id'])) {
                return (int) $warehouse['id'];
            }
        }

        return null;
    }

    /**
     * Получить список складов
     * GET /businesses/{businessId}/warehouses
     */
    public function getWarehouses(MarketplaceAccount $account): array
    {
        $businessId = $this->http->getBusinessId($account);
        
        if (!$businessId) {
            // Try to get from campaign
            $campaignId = $this->http->getCampaignId($account);
            if ($campaignId) {
                try {
                    $response = $this->http->get($account, "/campaigns/{$campaignId}/warehouses");
                    return $response['warehouses'] ?? [];
                } catch (\Exception $e) {
                    return [];
                }
            }
            return [];
        }

        try {
            $response = $this->http->get($account, "/businesses/{$businessId}/warehouses");
            return $response['result']['warehouses'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Получить текущие остатки товаров с маркетплейса
     * POST /campaigns/{campaignId}/offers/stocks
     */
    public function getStocks(MarketplaceAccount $account, array $offerIds = []): array
    {
        $campaignId = $this->http->getCampaignId($account);
        
        if (!$campaignId) {
            throw new \RuntimeException('Campaign ID не настроен');
        }

        $body = [];
        if (!empty($offerIds)) {
            $body['offerIds'] = $offerIds;
        }

        $response = $this->http->post(
            $account,
            "/campaigns/{$campaignId}/offers/stocks",
            $body
        );

        return $response['result']['warehouses'] ?? $response['warehouses'] ?? [];
    }
}
