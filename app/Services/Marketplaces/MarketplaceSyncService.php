<?php

// file: app/Services/Marketplaces/MarketplaceSyncService.php

namespace App\Services\Marketplaces;

use App\Events\MarketplaceSyncProgress;
use App\Helpers\BroadcastHelper;
use App\Jobs\SyncWildberriesSupplies;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSyncLog;
use App\Models\VariantMarketplaceLink;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use App\Services\Marketplaces\Wildberries\WildberriesPriceService;
use App\Services\Marketplaces\Wildberries\WildberriesProductService;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use App\Services\Stock\StockSyncService;
use App\Services\Uzum\UzumOrderSyncService;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class MarketplaceSyncService
{
    protected MarketplaceCustomerService $customerService;

    public function __construct(
        protected MarketplaceRegistry $registry,
        ?MarketplaceCustomerService $customerService = null,
    ) {
        $this->customerService = $customerService ?? new MarketplaceCustomerService;
    }

    /**
     * Извлечь данные клиента из DBS заказа в клиентскую базу.
     * Ошибки извлечения не прерывают синхронизацию заказов.
     */
    protected function extractCustomerFromOrder(MarketplaceAccount $account, $order): void
    {
        try {
            $this->customerService->extractFromOrder($account, $order);
        } catch (Throwable $e) {
            Log::warning('Ошибка извлечения клиента из заказа', [
                'order_id' => $order->id ?? null,
                'marketplace' => $account->marketplace,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Sync products catalog
     */
    public function syncProducts(MarketplaceAccount $account, ?array $productIds = null): void
    {
        $log = MarketplaceSyncLog::start($account->id, MarketplaceSyncLog::TYPE_PRODUCTS);
        $code = strtolower((string) $account->marketplace);

        try {
            // Wildberries: тянем карточки из Content API и сохраняем в local WB tables
            if ($code === 'wb' || $account->isWildberries()) {
                $httpClient = new WildberriesHttpClient($account);
                $productService = new WildberriesProductService($httpClient);

                $result = $productService->syncProducts($account);

                $log->markAsSuccess(
                    "WB products synced: {$result['synced']} (created: {$result['created']}, updated: {$result['updated']})"
                );

                return;
            }

            // Uzum: тянем каталог по всем магазинам и сохраняем marketplace_products
            if ($code === 'uzum' || $account->isUzum()) {
                $client = $this->registry->getClientForAccount($account);
                $result = $client->syncCatalog($account);
                $log->markAsSuccess("Uzum products synced: {$result['synced']} across shops: ".implode(',', $result['shops']));

                return;
            }

            // Yandex Market: используем специальный клиент с syncCatalog

            // Ozon: тянем каталог с API и сохраняем в ozon_products
            if ($code === 'ozon' || $account->marketplace === 'ozon') {
                $client = $this->registry->getClientForAccount($account);
                $result = $client->syncCatalog($account);
                $log->markAsSuccess("Ozon products synced: {$result['synced']} (created: {$result['created']}, updated: {$result['updated']})");

                return;
            }

            if ($code === 'ym') {
                $ymHttpClient = app(\App\Services\Marketplaces\YandexMarket\YandexMarketHttpClient::class);
                $ymClient = new \App\Services\Marketplaces\YandexMarket\YandexMarketClient($ymHttpClient);

                $result = $ymClient->syncCatalog($account);
                $log->markAsSuccess("YM products synced: {$result['synced']}");

                return;
            }

            $query = MarketplaceProduct::where('marketplace_account_id', $account->id);

            if ($productIds) {
                $query->whereIn('product_id', $productIds);
            }

            $products = $query->get()->all();

            if (empty($products)) {
                $log->markAsSuccess('No products to sync');

                return;
            }

            $client = $this->registry->getClientForAccount($account);
            $client->syncProducts($account, $products);

            $log->markAsSuccess('Synced '.count($products).' products');
        } catch (Throwable $e) {
            $log->markAsError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync prices
     */
    public function syncPrices(MarketplaceAccount $account, ?array $productIds = null): void
    {
        $log = MarketplaceSyncLog::start($account->id, MarketplaceSyncLog::TYPE_PRICES);

        try {
            // Wildberries: обновляем локальные цены из WB Prices API
            if ($account->isWildberries()) {
                $httpClient = new WildberriesHttpClient($account);
                $priceService = new WildberriesPriceService($httpClient);

                $result = $priceService->syncPrices($account);

                $log->markAsSuccess(
                    "WB prices synced: {$result['synced']} updated, errors: ".count($result['errors'] ?? [])
                );

                return;
            }

            $query = MarketplaceProduct::where('marketplace_account_id', $account->id)
                ->where('status', MarketplaceProduct::STATUS_ACTIVE);

            if ($productIds) {
                $query->whereIn('product_id', $productIds);
            }

            $products = $query->with('product')->get()->all();

            if (empty($products)) {
                $log->markAsSuccess('No products to update prices');

                return;
            }

            $client = $this->registry->getClientForAccount($account);
            $client->syncPrices($account, $products);

            // Update last_synced_price for each product
            foreach ($products as $mp) {
                if ($mp->product) {
                    $mp->update([
                        'last_synced_price' => $mp->product->price ?? null,
                        'last_synced_at' => now(),
                    ]);
                }
            }

            $log->markAsSuccess('Updated prices for '.count($products).' products');
        } catch (Throwable $e) {
            $log->markAsError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync stocks
     */
    public function syncStocks(MarketplaceAccount $account, ?array $productIds = null): void
    {
        $log = MarketplaceSyncLog::start($account->id, MarketplaceSyncLog::TYPE_STOCKS);

        try {
            // Wildberries: подтягиваем остатки из Statistics API в локальные таблицы
            if ($account->isWildberries()) {
                $httpClient = new WildberriesHttpClient($account);
                $stockService = new WildberriesStockService($httpClient);

                $result = $stockService->syncStocks($account);

                $log->markAsSuccess(
                    "WB stocks synced: {$result['synced']} items, warehouses created: {$result['warehouses_created']}, product updates: {$result['products_updated']}"
                );

                return;
            }

            // Uzum: используем StockSyncService (корректные fbsLinked/dbsLinked + все 7 полей)
            if ($account->isUzum()) {
                $syncService = app(StockSyncService::class);

                // Не фильтруем по external_sku_id — StockSyncService::findUzumSkuId()
                // может зарезолвить skuId по баркоду или из raw_payload
                $links = VariantMarketplaceLink::where('marketplace_account_id', $account->id)
                    ->where('marketplace_code', 'uzum')
                    ->where('is_active', true)
                    ->where('sync_stock_enabled', true)
                    ->with(['variant', 'marketplaceProduct', 'account'])
                    ->get();

                $synced = 0;
                $skipped = 0;
                $errors = 0;

                foreach ($links as $link) {
                    try {
                        $syncService->syncLinkStock($link);
                        $synced++;
                    } catch (\RuntimeException $e) {
                        if (str_contains($e->getMessage(), 'не подключён к FBS/DBS')) {
                            $skipped++;
                        } else {
                            $errors++;
                            Log::warning('Uzum stock sync error', ['sku_id' => $link->external_sku_id, 'error' => $e->getMessage()]);
                        }
                    } catch (Throwable $e) {
                        $errors++;
                        Log::error('Uzum stock sync failed', ['sku_id' => $link->external_sku_id, 'error' => $e->getMessage()]);
                    }

                    usleep(300000); // 300ms между запросами
                }

                $log->markAsSuccess("Uzum stocks: синхронизировано {$synced}, пропущено {$skipped}, ошибок {$errors}");

                return;
            }

            $query = MarketplaceProduct::where('marketplace_account_id', $account->id)
                ->where('status', MarketplaceProduct::STATUS_ACTIVE);

            if ($productIds) {
                $query->whereIn('product_id', $productIds);
            }

            $products = $query->with('product')->get()->all();

            if (empty($products)) {
                $log->markAsSuccess('No products to update stocks');

                return;
            }

            $client = $this->registry->getClientForAccount($account);
            $client->syncStocks($account, $products);

            // Update last_synced_stock for each product
            foreach ($products as $mp) {
                if ($mp->product) {
                    $mp->update([
                        'last_synced_stock' => $mp->product->stock_quantity ?? null,
                        'last_synced_at' => now(),
                    ]);
                }
            }

            $log->markAsSuccess('Updated stocks for '.count($products).' products');
        } catch (Throwable $e) {
            $log->markAsError($e->getMessage());
            throw $e;
        }
    }

    /**
     * Sync orders from marketplace
     */
    public function syncOrders(
        MarketplaceAccount $account,
        ?DateTimeInterface $from = null,
        ?DateTimeInterface $to = null,
        ?array $statuses = null
    ): void {
        // По умолчанию тянем последние 30 дней
        $from = $from ?? Carbon::now()->subDays(30);
        $to = $to ?? Carbon::now();

        $log = MarketplaceSyncLog::start($account->id, MarketplaceSyncLog::TYPE_ORDERS, [
            'from' => $from->format('Y-m-d H:i:s'),
            'to' => $to->format('Y-m-d H:i:s'),
        ]);

        try {
            // Отправляем событие о начале синхронизации
            BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                $account->company_id,
                $account->id,
                'started',
                'Начало синхронизации заказов...',
                0
            ));

            // Для Wildberries используем новый FBS API
            if ($account->marketplace === 'wb') {
                $httpClient = new WildberriesHttpClient($account);
                $orderService = new WildberriesOrderService($httpClient);

                // 1. Загружаем ВСЕ FBS заказы (не только новые)
                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'progress',
                    'Загрузка всех FBS заказов...',
                    25
                ));

                $allOrdersResult = $orderService->fetchAllOrders($account);

                // 2. Затем синхронизируем детальные данные из Statistics API (финансовые данные)
                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'progress',
                    'Синхронизация детальных данных...',
                    50
                ));

                $syncResult = $orderService->syncOrders($account, $from);

                // 3. Синхронизируем поставки и пересчитываем статусы заказов, чтобы вкладки «В доставке»/«Архив» не пустовали
                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'progress',
                    'Синхронизация поставок и статусов заказов...',
                    75
                ));

                // Выполняем sync поставок синхронно, чтобы сразу обновить статусы заказов
                (new SyncWildberriesSupplies($account))->handle();

                // 4. Архивируем старые заказы (старше 30 дней)
                $archived = $this->archiveOldOrders($account, 30);
                if ($archived > 0) {
                    Log::info("Archived {$archived} old orders for account {$account->id}");
                }

                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'completed',
                    "Синхронизация завершена: {$allOrdersResult['created']} новых, {$allOrdersResult['updated']} обновлено",
                    100
                ));

                $log->markAsSuccess("WB orders synced: {$allOrdersResult['created']} new, {$allOrdersResult['synced']} total, {$syncResult['updated']} updated with details, {$archived} archived");

                return;
            }

            // Для Yandex Market используем YandexMarketClient с собственной моделью YandexMarketOrder
            if ($account->marketplace === 'ym') {
                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'progress',
                    'Загрузка заказов Yandex Market...',
                    25
                ));

                $ymHttpClient = app(\App\Services\Marketplaces\YandexMarket\YandexMarketHttpClient::class);
                $ymClient = new \App\Services\Marketplaces\YandexMarket\YandexMarketClient($ymHttpClient);
                $stats = $ymClient->syncOrders($account, $from, $to);

                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'completed',
                    "Синхронизация Yandex Market завершена: {$stats['synced']} из {$stats['total']} заказов",
                    100,
                    ['synced' => $stats['synced'], 'total' => $stats['total']]
                ));

                $log->markAsSuccess("YM orders synced: {$stats['synced']} of {$stats['total']}, errors: " . count($stats['errors']));

                return;
            }

            // Для Uzum используем новый UzumOrderSyncService
            if ($account->marketplace === 'uzum' || $account->isUzum()) {
                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'progress',
                    'Загрузка FBS заказов Uzum...',
                    25
                ));

                $uzumSync = new UzumOrderSyncService;
                $daysBack = (int) ceil($from->diffInDays($to, true)) ?: 30;
                $stats = $uzumSync->sync($account, $daysBack);

                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'completed',
                    "Синхронизация Uzum завершена: {$stats['created']} новых, {$stats['updated']} обновлено",
                    100
                ));

                $log->markAsSuccess("Uzum orders synced: {$stats['created']} new, {$stats['updated']} updated, {$stats['total']} total");

                return;
            }

            $client = $this->registry->getClientForAccount($account);

            BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                $account->company_id,
                $account->id,
                'progress',
                'Загрузка заказов из маркетплейса...',
                25
            ));

            if ($statuses && method_exists($client, 'fetchOrdersByStatuses')) {
                $ordersData = $client->fetchOrdersByStatuses($account, $from, $to, $statuses);
            } else {
                $ordersData = $client->fetchOrders($account, $from, $to);
            }

            BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                $account->company_id,
                $account->id,
                'progress',
                'Сохранение '.count($ordersData).' заказов...',
                50
            ));

            $created = 0;
            $updated = 0;

            foreach ($ordersData as $orderData) {
                if ($account->marketplace === 'wb') {
                    $result = $this->upsertWbOrder($account, $orderData);
                } elseif ($account->marketplace === 'uzum') {
                    $result = $this->upsertUzumOrder($account, $orderData);
                } elseif ($account->marketplace === 'ozon') {
                    $result = $this->upsertOzonOrder($account, $orderData);
                } else {
                    $result = $this->upsertOrder($account, $orderData);
                }

                if ($result === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
            }

            // Для WB: дополнительно запрашиваем статусы заказов
            if ($account->marketplace === 'wb' && count($ordersData) > 0 && method_exists($client, 'fetchOrderStatuses')) {
                BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                    $account->company_id,
                    $account->id,
                    'progress',
                    'Обновление статусов заказов...',
                    75
                ));

                $orderIds = array_column($ordersData, 'external_order_id');
                $statuses = $client->fetchOrderStatuses($account, $orderIds);

                // Обновляем статусы в БД
                foreach ($statuses as $orderId => $statusData) {
                    $supplierStatus = $statusData['supplier_status'] ?? null;
                    $wbStatus = $statusData['wb_status'] ?? null;
                    // Определяем статус группу
                    $statusGroup = $this->mapWbStatusGroup($supplierStatus, $wbStatus);

                    \App\Models\WbOrder::where('marketplace_account_id', $account->id)
                        ->where('external_order_id', $orderId)
                        ->update([
                            'wb_supplier_status' => $supplierStatus,
                            'wb_status' => $wbStatus,
                            'wb_status_group' => $statusGroup,
                        ]);
                }
            }

            $log->markAsSuccess('Fetched '.count($ordersData)." orders (created: {$created}, updated: {$updated})");

            // Отправляем событие о завершении синхронизации
            BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                $account->company_id,
                $account->id,
                'completed',
                "Синхронизация завершена. Обработано: {$created} новых, {$updated} обновлено",
                100,
                ['created' => $created, 'updated' => $updated]
            ));
        } catch (Throwable $e) {
            $log->markAsError($e->getMessage());

            // Отправляем событие об ошибке
            BroadcastHelper::safeAll(new MarketplaceSyncProgress(
                $account->company_id,
                $account->id,
                'error',
                'Ошибка синхронизации: '.$e->getMessage(),
                null
            ));

            throw $e;
        }
    }


    /**
     * Upsert WB order into dedicated table
     */
    protected function upsertWbOrder(MarketplaceAccount $account, array $orderData): string
    {
        $externalId = $orderData['external_order_id'] ?? null;
        if (! $externalId) {
            return 'skipped';
        }

        $order = \App\Models\WbOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $externalId)
            ->first();

        $isNew = ! $order;

        $orderedAt = null;
        if (isset($orderData['ordered_at'])) {
            try {
                $orderedAt = \Carbon\Carbon::parse($orderData['ordered_at']);
            } catch (\Throwable $e) {
                Log::warning('Ошибка парсинга даты заказа WB', ['value' => $orderData['ordered_at'] ?? null, 'error' => $e->getMessage()]);
                $orderedAt = null;
            }
        }

        $updateData = [
            'status' => $orderData['status'] ?? null,
            'status_normalized' => $orderData['status_normalized'] ?? $orderData['status'] ?? null,
            'wb_status' => $orderData['wb_status'] ?? null,
            'wb_status_group' => $orderData['wb_status_group'] ?? null,
            'wb_supplier_status' => $orderData['wb_supplier_status'] ?? null,
            'wb_delivery_type' => $orderData['wb_delivery_type'] ?? null,
            'warehouse_id' => $orderData['wb_warehouse_id'] ?? $orderData['warehouse_id'] ?? null,
            'customer_name' => $orderData['customer_name'] ?? null,
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'total_amount' => $orderData['total_amount'] ?? 0,
            'currency' => $orderData['currency'] ?? 'RUB',
            'ordered_at' => $orderedAt,
            'delivered_at' => $orderData['delivered_at'] ?? null,
            'raw_payload' => $orderData['raw_payload'] ?? $orderData,
        ];

        $order = \App\Models\WbOrder::updateOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'external_order_id' => $externalId,
            ],
            $updateData
        );

        // Items
        if (! empty($orderData['items'])) {
            foreach ($orderData['items'] as $itemData) {
                \App\Models\WbOrderItem::updateOrCreate(
                    [
                        'wb_order_id' => $order->id,
                        'external_offer_id' => $itemData['external_offer_id'] ?? null,
                    ],
                    [
                        'name' => $itemData['name'] ?? null,
                        'quantity' => $itemData['quantity'] ?? 1,
                        'price' => $itemData['price'] ?? null,
                        'total_price' => $itemData['total_price'] ?? null,
                        'raw_payload' => $itemData['raw_payload'] ?? null,
                    ]
                );
            }
        }

        $this->extractCustomerFromOrder($account, $order);

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Upsert Uzum order into dedicated table
     */
    protected function upsertUzumOrder(MarketplaceAccount $account, array $orderData): string
    {
        $externalId = $orderData['external_order_id'] ?? null;
        if (! $externalId) {
            return 'skipped';
        }

        $order = \App\Models\UzumOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $externalId)
            ->first();

        $isNew = ! $order;

        $orderedAt = null;
        if (isset($orderData['ordered_at'])) {
            try {
                // Uzum API возвращает timestamp в миллисекундах
                if (is_numeric($orderData['ordered_at'])) {
                    $ts = (string) $orderData['ordered_at'];
                    // Обрезаем лишние символы если timestamp длиннее 13 символов
                    if (strlen($ts) > 13) {
                        $ts = substr($ts, 0, 13);
                    }
                    $num = (int) $ts;
                    // Если больше 1e12, это timestamp в миллисекундах
                    $appTz = config('app.timezone');
                    $orderedAt = $num > 1e12
                        ? \Carbon\Carbon::createFromTimestampMs($num, $appTz)
                        : \Carbon\Carbon::createFromTimestamp($num, $appTz);
                } else {
                    $orderedAt = \Carbon\Carbon::parse($orderData['ordered_at']);
                }
            } catch (\Throwable $e) {
                Log::warning('Ошибка парсинга даты заказа Uzum', ['value' => $orderData['ordered_at'] ?? null, 'error' => $e->getMessage()]);
                $orderedAt = null;
            }
        }

        $updateData = [
            'status' => $orderData['status'] ?? null,
            'status_normalized' => $orderData['status_normalized'] ?? $orderData['status'] ?? null,
            'delivery_type' => $orderData['delivery_type'] ?? $orderData['wb_delivery_type'] ?? null,
            'shop_id' => $orderData['shop_id'] ?? $orderData['raw_payload']['shopId'] ?? null,
            'customer_name' => $orderData['customer_name'] ?? null,
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'total_amount' => $orderData['total_amount'] ?? 0,
            'currency' => $orderData['currency'] ?? 'UZS',
            'ordered_at' => $orderedAt,
            'delivered_at' => $orderData['delivered_at'] ?? null,
            'delivery_address_full' => $orderData['delivery_address_full'] ?? null,
            'delivery_city' => $orderData['delivery_city'] ?? null,
            'delivery_street' => $orderData['delivery_street'] ?? null,
            'delivery_home' => $orderData['delivery_home'] ?? null,
            'delivery_flat' => $orderData['delivery_flat'] ?? null,
            'delivery_longitude' => $orderData['delivery_longitude'] ?? null,
            'delivery_latitude' => $orderData['delivery_latitude'] ?? null,
            'raw_payload' => $orderData['raw_payload'] ?? $orderData,
        ];

        $order = \App\Models\UzumOrder::updateOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'external_order_id' => $externalId,
            ],
            $updateData
        );

        if (! empty($orderData['items'])) {
            foreach ($orderData['items'] as $itemData) {
                \App\Models\UzumOrderItem::updateOrCreate(
                    [
                        'uzum_order_id' => $order->id,
                        'external_offer_id' => $itemData['external_offer_id'] ?? null,
                    ],
                    [
                        'name' => $itemData['name'] ?? null,
                        'quantity' => $itemData['quantity'] ?? 1,
                        'price' => $itemData['price'] ?? null,
                        'total_price' => $itemData['total_price'] ?? null,
                        'raw_payload' => $itemData['raw_payload'] ?? null,
                    ]
                );
            }
        }

        $this->extractCustomerFromOrder($account, $order);

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Upsert Ozon order into dedicated table
     */
    protected function upsertOzonOrder(MarketplaceAccount $account, array $orderData): string
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('ozon_orders')) {
            return 'skipped';
        }

        $orderId = $orderData['external_order_id'] ?? $orderData['order_id'] ?? null;
        if (! $orderId) {
            return 'skipped';
        }

        $order = \App\Models\OzonOrder::where('marketplace_account_id', $account->id)
            ->where('order_id', $orderId)
            ->first();

        $isNew = ! $order;

        $createdAtOzon = null;
        if (isset($orderData['created_at_ozon']) || isset($orderData['ordered_at'])) {
            try {
                $dateStr = $orderData['created_at_ozon'] ?? $orderData['ordered_at'];
                $createdAtOzon = \Carbon\Carbon::parse($dateStr);
            } catch (\Throwable $e) {
                Log::warning('Ошибка парсинга даты создания заказа Ozon', ['value' => $dateStr ?? null, 'error' => $e->getMessage()]);
                $createdAtOzon = null;
            }
        }

        $shipmentDate = null;
        if (isset($orderData['shipment_date'])) {
            try {
                $shipmentDate = \Carbon\Carbon::parse($orderData['shipment_date']);
            } catch (\Throwable $e) {
                Log::warning('Ошибка парсинга даты отгрузки Ozon', ['value' => $orderData['shipment_date'], 'error' => $e->getMessage()]);
                $shipmentDate = null;
            }
        }

        $inProcessAt = null;
        if (isset($orderData['in_process_at'])) {
            try {
                $inProcessAt = \Carbon\Carbon::parse($orderData['in_process_at']);
            } catch (\Throwable $e) {
                Log::warning('Ошибка парсинга даты обработки Ozon', ['value' => $orderData['in_process_at'], 'error' => $e->getMessage()]);
                $inProcessAt = null;
            }
        }

        $updateData = [
            'posting_number' => $orderData['posting_number'] ?? null,
            'status' => $orderData['status'] ?? null,
            'substatus' => $orderData['substatus'] ?? null,
            'total_price' => $orderData['total_price'] ?? $orderData['total_amount'] ?? 0,
            'currency' => $orderData['currency'] ?? 'RUB',
            'delivery_method' => $orderData['delivery_method'] ?? null,
            'warehouse_id' => $orderData['warehouse_id'] ?? null,
            'in_process_at' => $inProcessAt,
            'shipment_date' => $shipmentDate,
            'created_at_ozon' => $createdAtOzon,
            'order_data' => $orderData['raw_payload'] ?? $orderData,
        ];

        $order = \App\Models\OzonOrder::updateOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'order_id' => $orderId,
            ],
            $updateData
        );

        return $isNew ? 'created' : 'updated';
    }

    /**
     * Test connection to marketplace
     */
    public function testConnection(MarketplaceAccount $account): array
    {
        try {
            $client = $this->registry->getClientForAccount($account);

            return $client->testConnection($account);
        } catch (Throwable $e) {
            Log::warning('Проверка подключения к маркетплейсу failed', [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace ?? 'unknown',
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Ошибка: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Map WB status to status group
     */
    protected function mapWbStatusGroup(?string $supplierStatus, ?string $wbStatus): string
    {
        // Отменённые
        if (in_array($supplierStatus, ['cancel', 'reject']) ||
            in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'canceled';
        }

        // Архив (доставлено)
        if (in_array($wbStatus, ['delivered', 'sold_from_store']) ||
            $wbStatus === 'sold' ||
            $supplierStatus === 'receive') {
            return 'archive';
        }

        // В доставке
        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup'])) {
            return 'shipping';
        }

        // На сборке
        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted', 'sold'])) {
            return 'assembling';
        }

        // Новые
        if ($supplierStatus === 'new' || $wbStatus === 'waiting') {
            return 'new';
        }

        return 'new';
    }

    /**
     * Archive old orders (older than specified days)
     * Удаляет заказы старше указанного количества дней
     */
    protected function archiveOldOrders(MarketplaceAccount $account, int $days = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($days);

        try {
            if ($account->marketplace === 'wb') {
                // Удаляем старые заказы WB
                $deleted = \App\Models\WbOrder::where('marketplace_account_id', $account->id)
                    ->where('created_at', '<', $cutoffDate)
                    ->where('wb_status_group', 'archive') // Удаляем только уже завершённые
                    ->delete();

                return $deleted;
            }

            if ($account->marketplace === 'uzum') {
                // Удаляем старые заказы Uzum
                $deleted = \App\Models\UzumOrder::where('marketplace_account_id', $account->id)
                    ->where('created_at', '<', $cutoffDate)
                    ->whereIn('status', ['completed', 'cancelled', 'delivered'])
                    ->delete();

                return $deleted;
            }

            return 0;
        } catch (\Exception $e) {
            Log::error('Failed to archive old orders', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
