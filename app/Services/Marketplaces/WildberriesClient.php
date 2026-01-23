<?php
// file: app/Services/Marketplaces/WildberriesClient.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use DateTimeInterface;

class WildberriesClient implements MarketplaceClientInterface
{
    protected MarketplaceHttpClient $http;

    public function __construct(MarketplaceHttpClient $http)
    {
        $this->http = $http;
    }

    public function getMarketplaceCode(): string
    {
        return 'wb';
    }

    /**
     * Ping API to check connectivity (health-check)
     * Uses a lightweight endpoint to verify credentials are valid
     */
    public function ping(MarketplaceAccount $account): array
    {
        try {
            // Use warehouses endpoint - lightweight and validates API key
            // WB API v3: GET /api/v3/warehouses
            $response = $this->http->get($account, '/api/v3/warehouses');

            return [
                'success' => true,
                'message' => 'Wildberries API доступен',
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
            // Проверяем подключение через запрос складов
            // WB API v3: GET /api/v3/warehouses
            $response = $this->http->get($account, '/api/v3/warehouses');

            return [
                'success' => true,
                'message' => 'Соединение с Wildberries успешно установлено',
                'data' => $response,
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
        // TODO: Implement WB product sync
        //
        // WB Content API endpoints:
        // - POST /content/v2/cards/upload - создание карточки
        // - POST /content/v2/cards/update - обновление карточки
        // - GET /content/v2/get/cards/list - получение списка карточек
        //
        // Необходимые данные для создания карточки:
        // - vendorCode (артикул)
        // - title
        // - description
        // - brand
        // - dimensions (width, height, length)
        // - characteristics
        // - photos

        foreach ($products as $marketplaceProduct) {
            try {
                // TODO: Map internal product to WB card format
                // TODO: Create or update card via API
                // TODO: Update MarketplaceProduct with external_product_id (nmId)

                $marketplaceProduct->markAsSynced();
            } catch (\Exception $e) {
                $marketplaceProduct->markAsFailed($e->getMessage());
            }
        }
    }

    public function syncPrices(MarketplaceAccount $account, array $products): void
    {
        // WB Prices API v3: POST /api/v1/prices
        $priceUpdates = [];

        foreach ($products as $marketplaceProduct) {
            // nmId - это ID товара на WB
            $nmId = $marketplaceProduct->external_product_id ?? $marketplaceProduct->external_offer_id;
            if (!$nmId) {
                continue;
            }

            $product = $marketplaceProduct->product;
            if (!$product || !$product->price) {
                continue;
            }

            $priceUpdates[] = [
                'nmId' => (int) $nmId,
                'price' => (int) $product->price,
                'discount' => 0, // Можно добавить поле скидки в Product
            ];
        }

        if (empty($priceUpdates)) {
            return;
        }

        // Отправляем обновление цен в WB API
        $this->http->post($account, '/api/v1/prices', $priceUpdates);
    }

    public function syncStocks(MarketplaceAccount $account, array $products): void
    {
        // WB Stocks API: PUT /api/v3/stocks/{warehouseId}
        $warehouseId = $account->credentials_json['warehouse_id'] ?? null;

        if (!$warehouseId) {
            throw new \RuntimeException('Warehouse ID не настроен для этого аккаунта WB');
        }

        $stockUpdates = [];

        foreach ($products as $marketplaceProduct) {
            $sku = $marketplaceProduct->external_sku;
            if (!$sku) {
                continue;
            }

            $product = $marketplaceProduct->product;
            if (!$product) {
                continue;
            }

            $stockUpdates[] = [
                'sku' => $sku,
                'amount' => (int) ($product->stock_quantity ?? 0),
            ];
        }

        if (empty($stockUpdates)) {
            return;
        }

        // Отправляем обновление остатков в WB API
        $this->http->put($account, "/api/v3/stocks/{$warehouseId}", ['stocks' => $stockUpdates]);
    }

    public function fetchOrders(MarketplaceAccount $account, DateTimeInterface $from, DateTimeInterface $to, int $suppliesLimit = 20): array
    {
        $allOrders = [];

        // 1. Always fetch new orders (most important - these need to be processed)
        // Endpoint /api/v3/orders/new возвращает заказы СО статусами
        $newOrders = $this->fetchNewOrders($account);
        foreach ($newOrders as $order) {
            $allOrders[$order['external_order_id']] = $order;
        }

        // 2. Fetch orders from supplies to ensure linkage with supply_id and closed supplies
        // Endpoint /api/v3/supplies/{id}/orders возвращает заказы СО статусами
        try {
            $supplyOrders = $this->fetchOrdersFromSupplies($account, $suppliesLimit);
            foreach ($supplyOrders as $order) {
                $allOrders[$order['external_order_id']] = $order;
            }
        } catch (\Exception $e) {
            \Log::warning('WB fetchOrdersFromSupplies failed, continuing without supply linkage', [
                'error' => $e->getMessage(),
            ]);
        }

        // ПРИМЕЧАНИЕ: Мы НЕ используем fetchOrdersByDate, так как /api/v3/orders
        // НЕ возвращает поля supplierStatus и wbStatus, что приводит к неправильному маппингу статусов.
        // Вместо этого полагаемся на fetchNewOrders и fetchOrdersFromSupplies, которые возвращают полные данные.

        // 3. DBW (доставка курьером WB) — вызываем дополнительные endpoints, если доступны
        try {
            foreach ($this->fetchDbwNewOrders($account) as $order) {
                $allOrders[$order['external_order_id']] = $order;
            }
            foreach ($this->fetchDbwOrders($account, $from) as $order) {
                $allOrders[$order['external_order_id']] = $order;
            }
        } catch (\Exception $e) {
            \Log::warning('WB DBW fetch failed, continuing without DBW orders', ['error' => $e->getMessage()]);
        }

        $orders = array_values($allOrders);

        // 4. Попытаться дополнить клиентскими данными
        try {
            $this->enrichWithClientInfo($account, $orders);
        } catch (\Exception $e) {
            \Log::warning('WB enrichWithClientInfo failed, continuing without client info', ['error' => $e->getMessage()]);
        }

        return $orders;
    }

    /**
     * Получить FBS заказы за период через /api/v3/orders с пагинацией next
     */
    protected function fetchOrdersByDate(MarketplaceAccount $account, DateTimeInterface $from): array
    {
        $orders = [];
        $next = 0;
        $dateFrom = $from->getTimestamp();

        do {
            $params = [
                'dateFrom' => $dateFrom,
                'limit' => 1000,
                'next' => $next,
            ];

            $response = $this->http->get($account, '/api/v3/orders', $params);

            foreach ($response['orders'] ?? [] as $orderData) {
                $orders[] = $this->mapOrderData($orderData);
            }

            $next = $response['next'] ?? 0;
        } while ($next > 0 && !empty($response['orders']));

        return $orders;
    }

    /**
     * Получить новые заказы (статус: new)
     * GET /api/v3/orders/new
     */
    public function fetchNewOrders(MarketplaceAccount $account): array
    {
        $orders = [];
        $next = 0;

        try {
            do {
                $params = ['limit' => 1000];
                if ($next > 0) {
                    $params['next'] = $next;
                }

                $response = $this->http->get($account, '/api/v3/orders/new', $params);

                foreach ($response['orders'] ?? [] as $orderData) {
                    $mapped = $this->mapOrderData($orderData);
                    // Эти заказы всегда "новые" (не подтверждены)
                    $mapped['status'] = 'new';
                    $mapped['status_normalized'] = 'new';
                    $mapped['wb_supplier_status'] = 'new';
                    $mapped['wb_status_group'] = 'new';
                    $orders[] = $mapped;
                }

                $next = $response['next'] ?? 0;
            } while ($next > 0 && count($response['orders'] ?? []) > 0);

        } catch (\Exception $e) {
            \Log::error('WB fetchNewOrders error', ['error' => $e->getMessage()]);
        }

        return $orders;
    }

    /**
     * Получить заказы из всех поставок с пагинацией
     * Синхронизирует заказы из активных и недавно закрытых поставок с правильными статусами
     */
    public function fetchOrdersFromSupplies(MarketplaceAccount $account, int $maxSuppliesPerRun = 50): array
    {
        $orders = [];

        try {
            // Get all supplies
            $allSupplies = $this->fetchSupplies($account);

            // Разделяем поставки на категории
            $activeSupplies = []; // Не закрыты - заказы "на сборке"
            $inDeliverySupplies = []; // Закрыты но не доставлены - заказы "в доставке"
            $recentCompletedSupplies = []; // Закрыты и доставлены недавно - для архива

            foreach ($allSupplies as $supply) {
                $isClosed = !empty($supply['closedAt']);
                $isScanned = !empty($supply['scanDt']); // Принято на складе WB

                if (!$isClosed) {
                    // Поставка не закрыта - заказы на сборке
                    $activeSupplies[] = $supply;
                } elseif ($isClosed && !$isScanned) {
                    // Закрыта, но ещё не принята на склад WB - в доставке до WB
                    $inDeliverySupplies[] = $supply;
                } else {
                    // Принята на склад WB - заказы в доставке клиентам или уже доставлены
                    $closedDate = \Carbon\Carbon::parse($supply['closedAt']);
                    $daysAgo = $closedDate->diffInDays(now());

                    // Берем закрытые поставки за последние 30 дней для архива
                    if ($daysAgo <= 30) {
                        $recentCompletedSupplies[] = $supply;
                    }
                }
            }

            \Log::info('WB fetchOrdersFromSupplies', [
                'total_supplies' => count($allSupplies),
                'active' => count($activeSupplies),
                'in_delivery' => count($inDeliverySupplies),
                'recent_completed' => count($recentCompletedSupplies),
            ]);

            // Обрабатываем поставки по приоритету: активные > в доставке > завершённые
            // Приоритизируем активные поставки - они самые важные (заказы на сборке)
            $suppliesToProcess = [];

            // Сначала ВСЕГДА берём все активные поставки (их обычно мало)
            $suppliesToProcess = array_merge($suppliesToProcess, $activeSupplies);

            // Затем добавляем из доставки и завершённых до лимита
            $remainingSlots = $maxSuppliesPerRun - count($activeSupplies);
            if ($remainingSlots > 0) {
                $otherSupplies = array_merge($inDeliverySupplies, $recentCompletedSupplies);
                $suppliesToProcess = array_merge(
                    $suppliesToProcess,
                    array_slice($otherSupplies, 0, $remainingSlots)
                );
            }

            \Log::info('WB supplies to process', [
                'total_selected' => count($suppliesToProcess),
                'active' => count($activeSupplies),
                'other' => count($suppliesToProcess) - count($activeSupplies),
            ]);

            // Обрабатываем каждую поставку
            foreach ($suppliesToProcess as $supply) {
                $supplyId = $supply['id'] ?? null;
                if (!$supplyId) continue;

                $isClosed = !empty($supply['closedAt']);
                $isScanned = !empty($supply['scanDt']);

                try {
                    // Step 1: Get order IDs from supply (new API endpoint)
                    $orderIdsResponse = $this->http->get($account, "/api/marketplace/v3/supplies/{$supplyId}/order-ids");
                    $orderIds = $orderIdsResponse['orderIds'] ?? [];

                    if (empty($orderIds)) {
                        continue;
                    }

                    // Step 2: Get order details via POST /api/v3/orders/status
                    $chunks = array_chunk($orderIds, 1000);
                    foreach ($chunks as $chunk) {
                        $statusResponse = $this->http->post($account, '/api/v3/orders/status', ['orders' => $chunk]);

                        foreach ($statusResponse['orders'] ?? [] as $orderData) {
                            $mapped = $this->mapOrderData($orderData);
                            $mapped['supply_id'] = $supplyId;

                            // Определяем статус на основе состояния поставки
                            if (!$isClosed) {
                                // Активная поставка - на сборке
                                $mapped['status'] = 'in_assembly';
                                $mapped['status_normalized'] = 'in_assembly';
                                $mapped['wb_supplier_status'] = 'confirm';
                                $mapped['wb_status_group'] = 'assembling';
                            } elseif ($isClosed && !$isScanned) {
                                // Поставка закрыта, но не принята WB - в пути до WB
                                $mapped['status'] = 'in_delivery';
                                $mapped['status_normalized'] = 'in_delivery';
                                $mapped['wb_supplier_status'] = 'complete';
                                $mapped['wb_status_group'] = 'shipping';
                            } else {
                                // Поставка принята WB - в доставке клиентам или завершено
                                $mapped['status'] = 'in_delivery';
                                $mapped['status_normalized'] = 'in_delivery';
                                $mapped['wb_supplier_status'] = 'complete';
                                $mapped['wb_status_group'] = 'shipping';
                            }

                            $orders[] = $mapped;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning("WB fetch orders from supply {$supplyId} error", ['error' => $e->getMessage()]);
                    continue;
                }
            }

        } catch (\Exception $e) {
            \Log::error('WB fetchOrdersFromSupplies error', ['error' => $e->getMessage()]);
        }

        return $orders;
    }

    /**
     * Получить статусы заказов
     * POST /api/v3/orders/status
     */
    public function fetchOrderStatuses(MarketplaceAccount $account, array $orderIds): array
    {
        try {
            // Преобразуем все ID в целые числа
            $orderIds = array_map('intval', $orderIds);

            // WB принимает до 1000 ID за раз
            $chunks = array_chunk($orderIds, 1000);
            $statuses = [];

            foreach ($chunks as $chunk) {
                $response = $this->http->post($account, '/api/v3/orders/status', ['orders' => $chunk]);

                foreach ($response['orders'] ?? [] as $orderStatus) {
                    $statuses[$orderStatus['id']] = [
                        'supplier_status' => $orderStatus['supplierStatus'] ?? null,
                        'wb_status' => $orderStatus['wbStatus'] ?? null,
                    ];
                }
            }

            return $statuses;
        } catch (\Exception $e) {
            \Log::error('WB fetchOrderStatuses error', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Получить заказы DBW (новые)
     * GET /api/v3/dbw/orders/new
     */
    protected function fetchDbwNewOrders(MarketplaceAccount $account): array
    {
        $orders = [];
        $next = 0;

        do {
            $params = [
                'limit' => 1000,
                'next' => $next,
            ];

            $response = $this->http->get($account, '/api/v3/dbw/orders/new', $params);

            foreach ($response['orders'] ?? [] as $orderData) {
                $orders[] = $this->mapOrderData($orderData);
            }

            $next = $response['next'] ?? 0;
        } while ($next > 0 && !empty($response['orders']));

        return $orders;
    }

    /**
     * Получить заказы DBW (завершённые/исторические)
     * GET /api/v3/dbw/orders
     */
    protected function fetchDbwOrders(MarketplaceAccount $account, DateTimeInterface $from): array
    {
        $orders = [];
        $next = 0;
        $dateFrom = $from->getTimestamp();

        do {
            $params = [
                'dateFrom' => $dateFrom,
                'limit' => 1000,
                'next' => $next,
            ];

            $response = $this->http->get($account, '/api/v3/dbw/orders', $params);

            foreach ($response['orders'] ?? [] as $orderData) {
                $orders[] = $this->mapOrderData($orderData);
            }

            $next = $response['next'] ?? 0;
        } while ($next > 0 && !empty($response['orders']));

        return $orders;
    }

    /**
     * Дополнить заказы клиентскими данными (если метод доступен)
     * POST /api/v3/orders/client
     */
    protected function enrichWithClientInfo(MarketplaceAccount $account, array &$orders): void
    {
        if (empty($orders)) {
            return;
        }

        $byId = [];
        foreach ($orders as $idx => $order) {
            $orderId = $order['wb_order_id'] ?? $order['external_order_id'] ?? null;
            if ($orderId) {
                $byId[$orderId] = $idx;
            }
        }

        $orderIds = array_keys($byId);
        if (empty($orderIds)) {
            return;
        }

        // WB обычно до 1000 ID за раз — безопасно режем на 500
        $chunks = array_chunk($orderIds, 500);
        $clientData = [];

        foreach ($chunks as $chunk) {
            $response = $this->http->post($account, '/api/v3/orders/client', ['orders' => $chunk]);
            foreach ($response['orders'] ?? [] as $row) {
                $id = $row['id'] ?? null;
                if ($id) {
                    $clientData[$id] = $row;
                }
            }
        }

        if (empty($clientData)) {
            return;
        }

        foreach ($clientData as $id => $data) {
            if (!isset($byId[$id])) {
                continue;
            }
            $idx = $byId[$id];
            $orders[$idx]['wb_client'] = $data;

            // Попробуем заполнить базовые поля, если есть
            $client = $data['client'] ?? [];
            if (!empty($client['fio'])) {
                $orders[$idx]['customer_name'] = $client['fio'];
            }
            if (!empty($client['phone'])) {
                $orders[$idx]['customer_phone'] = $client['phone'];
            }

            $address = $data['address'] ?? $orders[$idx]['wb_address_full'] ?? null;
            if (is_array($address)) {
                $orders[$idx]['wb_address_full'] = $address['fullAddress'] ?? ($address['address'] ?? null);
                $orders[$idx]['wb_address_lat'] = $address['latitude'] ?? $orders[$idx]['wb_address_lat'] ?? null;
                $orders[$idx]['wb_address_lng'] = $address['longitude'] ?? $orders[$idx]['wb_address_lng'] ?? null;
            }
        }
    }

    /**
     * Получить список поставок
     * GET /api/v3/supplies
     */
    public function fetchSupplies(MarketplaceAccount $account): array
    {
        $supplies = [];
        $next = 0;

        try {
            do {
                $params = [
                    'limit' => 1000,
                    'next' => $next,
                ];

                $response = $this->http->get($account, '/api/v3/supplies', $params);

                foreach ($response['supplies'] ?? [] as $supply) {
                    $supplies[] = $supply;
                }

                $next = $response['next'] ?? 0;
            } while ($next > 0 && !empty($response['supplies']));

        } catch (\Exception $e) {
            \Log::error('WB fetchSupplies error', ['error' => $e->getMessage()]);
            // Return empty array on error - supplies may not be available
            return [];
        }

        return $supplies;
    }

    /**
     * Создать новую поставку
     * POST /api/v3/supplies
     */
    public function createSupply(MarketplaceAccount $account, string $name): ?string
    {
        try {
            $response = $this->http->post($account, '/api/v3/supplies', ['name' => $name]);
            return $response['id'] ?? null; // Возвращает supply ID (WB-GI-1234567)
        } catch (\Exception $e) {
            \Log::error('WB createSupply error', ['error' => $e->getMessage()]);
            return null;
        }
    }

    /**
     * Добавить заказы в поставку
     * PATCH /api/marketplace/v3/supplies/{supplyId}/orders
     */
    public function addOrdersToSupply(MarketplaceAccount $account, string $supplyId, array $orderIds): bool
    {
        try {
            // WB принимает до 100 заказов за раз
            $chunks = array_chunk($orderIds, 100);

            foreach ($chunks as $chunk) {
                $this->http->patch($account, "/api/marketplace/v3/supplies/{$supplyId}/orders", [
                    'orders' => $chunk
                ]);
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('WB addOrdersToSupply error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Отправить поставку в доставку
     * PATCH /api/v3/supplies/{supplyId}/deliver
     */
    public function deliverSupply(MarketplaceAccount $account, string $supplyId): bool
    {
        try {
            $this->http->patch($account, "/api/v3/supplies/{$supplyId}/deliver");
            return true;
        } catch (\Exception $e) {
            \Log::error('WB deliverSupply error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Отменить заказ
     * PATCH /api/v3/orders/{orderId}/cancel
     */
    public function cancelOrder(MarketplaceAccount $account, string $orderId): bool
    {
        try {
            $this->http->patch($account, "/api/v3/orders/{$orderId}/cancel");
            return true;
        } catch (\Exception $e) {
            \Log::error('WB cancelOrder error', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function getProductInfo(MarketplaceAccount $account, string $externalId): ?array
    {
        // TODO: Implement WB product info fetch
        //
        // GET /content/v2/get/cards/list with filter by nmId

        try {
            // $response = $this->http->post($account, '/content/v2/get/cards/list', [
            //     'settings' => [
            //         'filter' => [
            //             'withPhoto' => -1,
            //             'nmID' => [(int) $externalId],
            //         ],
            //     ],
            // ]);
            //
            // return $response['cards'][0] ?? null;

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Map WB order data to standard format
     */
    public function mapOrderData(array $orderData): array
    {
        // Маппинг заказа WB в стандартный формат
        $items = [];

        // WB возвращает информацию о товаре в самом заказе
        if (!empty($orderData['nmId'])) {
            // Цена: используем totalPrice, если есть, иначе price, иначе convertedPrice
            $priceValue = $orderData['totalPrice'] ?? $orderData['price'] ?? $orderData['convertedPrice'] ?? 0;

            $items[] = [
                'external_offer_id' => (string) $orderData['nmId'],
                'name' => $orderData['supplierArticle'] ?? $orderData['article'] ?? null,
                'quantity' => 1,
                'price' => $priceValue / 100, // WB хранит цены в копейках
                'total_price' => $priceValue / 100,
                'raw_payload' => $orderData,
            ];
        }

        // Определяем статус на основе supplierStatus и wbStatus
        $wbSupplierStatus = $orderData['supplierStatus'] ?? null;
        $wbStatus = $orderData['wbStatus'] ?? null;
        $internalStatus = $this->mapWbStatusToInternal($wbSupplierStatus, $wbStatus);

        // Цена заказа: используем totalPrice, если есть, иначе price, иначе convertedPrice
        $orderTotalAmount = ($orderData['totalPrice'] ?? $orderData['price'] ?? $orderData['convertedPrice'] ?? 0) / 100;

        return [
            'external_order_id' => (string) ($orderData['id'] ?? $orderData['rid'] ?? ''),
            'status' => $internalStatus,
            'status_normalized' => $internalStatus,
            'customer_name' => null, // WB не передаёт данные покупателя
            'customer_phone' => null,
            'total_amount' => $orderTotalAmount,
            'currency' => 'RUB',
            'ordered_at' => $orderData['createdAt'] ?? $orderData['dateCreated'] ?? null,
            'items' => $items,
            'raw_payload' => $orderData,

            // WB-специфичные поля (базовые)
            'supply_id' => $orderData['supplyId'] ?? null,
            'wb_supplier_status' => $wbSupplierStatus,
            'wb_status' => $wbStatus,
            'wb_status_group' => null, // Будет установлено в fetchOrdersFromSupplies на основе статуса поставки

            // WB-специфичные поля (расширенные - идентификаторы)
            'wb_order_id' => $orderData['id'] ?? null,
            'wb_order_uid' => $orderData['orderUid'] ?? null,
            'wb_rid' => $orderData['rid'] ?? null,

            // Товар
            'wb_nm_id' => $orderData['nmId'] ?? null,
            'wb_chrt_id' => $orderData['chrtId'] ?? null,
            'wb_article' => $orderData['article'] ?? $orderData['supplierArticle'] ?? null,
            'wb_skus' => $orderData['skus'] ?? null,

            // Логистика
            'wb_warehouse_id' => $orderData['warehouseId'] ?? null,
            'wb_office_id' => $orderData['officeId'] ?? null,
            'wb_offices' => $orderData['offices'] ?? null,
            'wb_delivery_type' => $orderData['deliveryType'] ?? null,
            'wb_cargo_type' => $orderData['cargoType'] ?? null,
            'wb_is_zero_order' => $orderData['isZeroOrder'] ?? false,
            'wb_is_b2b' => $orderData['options']['isB2b'] ?? false,

            // Адрес
            'wb_address_full' => $orderData['address']['fullAddress'] ?? $orderData['address'] ?? null,
            'wb_address_lat' => $orderData['address']['latitude'] ?? null,
            'wb_address_lng' => $orderData['address']['longitude'] ?? null,

            // Финансы (в копейках)
            'wb_price' => $orderData['price'] ?? null,
            'wb_final_price' => $orderData['finalPrice'] ?? null,
            'wb_converted_price' => $orderData['convertedPrice'] ?? null,
            'wb_converted_final_price' => $orderData['convertedFinalPrice'] ?? null,
            'wb_sale_price' => $orderData['salePrice'] ?? null,
            'wb_scan_price' => $orderData['scanPrice'] ?? null,
            'wb_currency_code' => $orderData['currencyCode'] ?? null,
            'wb_converted_currency_code' => $orderData['convertedCurrencyCode'] ?? null,

            // Даты
            'wb_ddate' => $orderData['ddate'] ?? null,
            'wb_created_at_utc' => $orderData['createdAt'] ?? null,

            // Метаданные
            'wb_required_meta' => $orderData['requiredMeta'] ?? null,
            'wb_optional_meta' => $orderData['optionalMeta'] ?? null,
            'wb_comment' => $orderData['comment'] ?? null,
        ];
    }

    /**
     * Маппинг WB статусов в внутренние статусы системы
     * Приоритет: supplierStatus > wbStatus
     *
     * Статусы WB API (supplierStatus):
     * - new: новый заказ
     * - confirm: подтверждён продавцом
     * - complete: собран и передан в доставку
     * - cancel: отменён
     * - reject: отклонён
     * - receive: получен клиентом
     *
     * Статусы WB API (wbStatus):
     * - waiting: ожидает обработки
     * - sorted: отсортирован
     * - sold: продан
     * - canceled: отменён
     * - canceled_by_client: отменён клиентом
     * - declined_by_client: отклонён клиентом
     * - defect: дефект
     * - delivered: доставлен
     * - sold_from_store: продан из магазина
     * - on_way_to_client: в пути к клиенту
     * - on_way_from_client: в пути от клиента
     * - ready_for_pickup: готов к выдаче
     */
    protected function mapWbStatusToInternal(?string $supplierStatus, ?string $wbStatus): string
    {
        // Normalize to lowercase
        $supplierStatus = $supplierStatus ? strtolower($supplierStatus) : null;
        $wbStatus = $wbStatus ? strtolower($wbStatus) : null;

        // Приоритет: отменённые → завершённые → в доставке → на сборке → новые

        // 1. Отменённые
        if (in_array($supplierStatus, ['cancel', 'reject', 'cancelled']) ||
            in_array($wbStatus, ['canceled', 'cancelled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'cancelled';
        }

        // 2. Завершённые (доставлено клиенту)
        if (in_array($wbStatus, ['delivered', 'sold_from_store', 'received']) ||
            $wbStatus === 'sold' ||
            $supplierStatus === 'receive') {
            return 'completed';
        }

        // 3. В доставке (продавец завершил, WB доставляет)
        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'at_pickup_point', 'in_transit', 'delivering'])) {
            return 'in_delivery';
        }

        // 4. На сборке (продавец подтвердил и собирает)
        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted', 'accepted', 'in_assembly', 'confirm', 'confirmed'])) {
            return 'in_assembly';
        }

        // 5. Новые (ожидают подтверждения) - ТОЛЬКО если явно указано
        if ($supplierStatus === 'new' || $wbStatus === 'waiting' || $wbStatus === 'new') {
            return 'new';
        }

        // 6. Если статус не определён, но есть wbStatus - скорее всего это archived заказы
        // Archived заказы без supplierStatus обычно завершены или отменены
        if ($wbStatus && !$supplierStatus) {
            // Если wbStatus есть но не попадает в известные - скорее всего completed
            return 'completed';
        }

        // По умолчанию - если есть supplierStatus 'complete', то in_delivery
        // Иначе in_assembly (заказ в процессе)
        if ($supplierStatus) {
            return 'in_assembly';
        }

        // Если совсем нет данных - новый
        return 'new';
    }
}
