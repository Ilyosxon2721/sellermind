<?php

// file: app/Services/Marketplaces/Wildberries/WildberriesOrderService.php

namespace App\Services\Marketplaces\Wildberries;

use App\Events\MarketplaceDataChanged;
use App\Events\MarketplaceSyncProgress;
use App\Models\MarketplaceAccount;
use App\Models\Supply;
use App\Models\WbOrder;
use App\Models\WildberriesOrder;
use App\Services\Marketplaces\Sync\OrdersSyncService;
use App\Services\Marketplaces\WildberriesClient;
use App\Services\Stock\OrderStockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for syncing Wildberries orders.
 *
 * WB Marketplace API:
 * - GET /api/v3/orders/new - get new FBS orders
 * - GET /api/v3/orders - get all orders with status
 *
 * WB Statistics API:
 * - GET /api/v1/supplier/orders - detailed orders with financial data
 */
class WildberriesOrderService
{
    protected ?WildberriesHttpClient $httpClient = null;

    protected ?MarketplaceAccount $currentAccount = null;

    protected OrderStockService $orderStockService;

    public function __construct(?WildberriesHttpClient $httpClient = null, ?OrderStockService $orderStockService = null)
    {
        $this->httpClient = $httpClient;
        $this->orderStockService = $orderStockService ?? new OrderStockService;
    }

    /**
     * Get the OrderStockService instance
     */
    public function getOrderStockService(): OrderStockService
    {
        return $this->orderStockService;
    }

    /**
     * Get HTTP client for the specified account
     */
    protected function getHttpClient(MarketplaceAccount $account): WildberriesHttpClient
    {
        // If account changed or no client, create new one
        if (! $this->httpClient || $this->currentAccount?->id !== $account->id) {
            $this->httpClient = new WildberriesHttpClient($account);
            $this->currentAccount = $account;
        }

        return $this->httpClient;
    }

    /**
     * Fetch new FBS orders (Marketplace API)
     *
     * @return array Sync results
     */
    public function fetchNewOrders(MarketplaceAccount $account): array
    {
        $synced = 0;
        $created = 0;
        $errors = [];

        Log::info('Fetching new WB FBS orders', ['account_id' => $account->id]);

        // Broadcast start of fetching
        broadcast(new MarketplaceSyncProgress(
            $account->company_id,
            $account->id,
            'started',
            'Получение новых заказов',
            0
        ));

        try {
            // GET /api/v3/orders/new
            $response = $this->getHttpClient($account)->get('marketplace', '/api/v3/orders/new');

            $orders = $response['orders'] ?? [];

            foreach ($orders as $orderData) {
                try {
                    $result = $this->processOrderFromMarketplace($account, $orderData);

                    if ($result['created']) {
                        $created++;
                    }

                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'order_id' => $orderData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('WB new orders fetch completed', [
                'account_id' => $account->id,
                'synced' => $synced,
                'created' => $created,
            ]);

        } catch (\Exception $e) {
            Log::error('WB new orders fetch failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $errors[] = ['fetch_error' => $e->getMessage()];
        }

        // Broadcast completion/progress update
        broadcast(new MarketplaceSyncProgress(
            $account->company_id,
            $account->id,
            'completed',
            'Получение новых заказов завершено',
            100,
            ['created' => $created, 'synced' => $synced]
        ));

        // Broadcast data change if any orders affected
        if ($synced > 0 || $created > 0) {
            broadcast(new MarketplaceDataChanged(
                $account->company_id,
                $account->id,
                'orders',
                $created > 0 ? 'created' : 'updated',
                $synced,
                null,
                ['new_orders_count' => $created]
            ));
        }

        return [
            'synced' => $synced,
            'created' => $created,
            'errors' => $errors,
        ];
    }

    /**
     * Fetch orders with all statuses from Marketplace API
     *
     * @param  int  $limit  Limit of orders to fetch (default 1000)
     * @param  int  $next  Pagination offset
     * @return array Sync results
     */
    public function fetchAllOrders(MarketplaceAccount $account, int $limit = 1000, int $next = 0): array
    {
        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];
        $syncedOrderIds = []; // Собираем ID всех синхронизированных заказов со ВСЕХ страниц

        Log::info('Fetching all WB FBS orders', [
            'account_id' => $account->id,
            'limit' => $limit,
            'next' => $next,
        ]);

        // Broadcast start of fetching
        broadcast(new MarketplaceSyncProgress(
            $account->company_id,
            $account->id,
            'started',
            'Получение всех заказов',
            0
        ));

        try {
            $currentNext = $next;
            $pageCount = 0;
            $maxPages = 50; // Защита от бесконечного цикла

            // Пагинация: получаем ВСЕ заказы через все страницы
            do {
                $pageCount++;

                // GET /api/v3/orders - получаем ВСЕ заказы (не только новые)
                $response = $this->getHttpClient($account)->get('marketplace', '/api/v3/orders', [
                    'limit' => $limit,
                    'next' => $currentNext,
                ]);

                $orders = $response['orders'] ?? [];
                $nextCursor = $response['next'] ?? 0;

                Log::info('WB fetchAllOrders page', [
                    'account_id' => $account->id,
                    'page' => $pageCount,
                    'orders_count' => count($orders),
                    'next_cursor' => $nextCursor,
                ]);

                foreach ($orders as $orderData) {
                    try {
                        $result = $this->processOrderFromMarketplace($account, $orderData);

                        // Сохраняем ID синхронизированного заказа
                        $syncedOrderIds[] = $orderData['id'];

                        if ($result['created']) {
                            $created++;
                        } else {
                            $updated++;
                        }

                        $synced++;
                    } catch (\Exception $e) {
                        $errors[] = [
                            'order_id' => $orderData['id'] ?? 'unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }

                // Переходим к следующей странице
                // WB API: next=0 означает последняя страница
                $currentNext = $nextCursor;

                // Небольшая пауза между страницами
                if ($currentNext > 0 && count($orders) >= $limit) {
                    usleep(300000); // 0.3 секунды
                }

            } while ($currentNext > 0 && count($orders) >= $limit && $pageCount < $maxPages);

            // Помечаем заказы, которых нет в ответе API, как отменённые
            // Но только те, которые ещё не в статусе archive или canceled
            // Важно: только после полной пагинации всех страниц
            if (! empty($syncedOrderIds)) {
                // Также добавляем 'cancelled' в excluded statuses (двойное написание)
                $ordersToCancel = WbOrder::where('marketplace_account_id', $account->id)
                    ->whereNotIn('external_order_id', $syncedOrderIds)
                    ->whereNotIn('status', ['archive', 'canceled', 'cancelled', 'completed'])
                    ->get();

                foreach ($ordersToCancel as $orderToCancel) {
                    $oldStatus = $orderToCancel->status;

                    // Обновляем статус
                    $orderToCancel->update([
                        'status' => 'cancelled',
                        'status_normalized' => 'cancelled',
                        'wb_status_group' => 'canceled',
                    ]);

                    // Обрабатываем остатки (отменяем резерв если был)
                    try {
                        $items = $this->orderStockService->getOrderItems($orderToCancel, 'wb');
                        $this->orderStockService->processOrderStatusChange(
                            $account,
                            $orderToCancel,
                            $oldStatus,
                            'cancelled',
                            $items
                        );
                    } catch (\Throwable $e) {
                        Log::warning('Failed to process stock for cancelled order', [
                            'order_id' => $orderToCancel->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if ($ordersToCancel->count() > 0) {
                    Log::info("Marked {$ordersToCancel->count()} orders as canceled (not in API response)", [
                        'account_id' => $account->id,
                        'total_synced_ids' => count($syncedOrderIds),
                        'pages_fetched' => $pageCount,
                    ]);
                }
            }

            Log::info('WB all orders fetch completed', [
                'account_id' => $account->id,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'pages' => $pageCount,
            ]);

        } catch (\Exception $e) {
            Log::error('WB all orders fetch failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $errors[] = ['fetch_error' => $e->getMessage()];
        }

        // Broadcast completion
        broadcast(new MarketplaceSyncProgress(
            $account->company_id,
            $account->id,
            'completed',
            'Получение всех заказов завершено',
            100,
            ['created' => $created, 'updated' => $updated, 'synced' => $synced]
        ));

        // Broadcast data change if any orders affected
        if ($synced > 0 || $created > 0 || $updated > 0) {
            broadcast(new MarketplaceDataChanged(
                $account->company_id,
                $account->id,
                'orders',
                $created > 0 ? 'created' : 'updated',
                $synced,
                null,
                ['new_orders_count' => $created, 'updated_orders_count' => $updated]
            ));
        }

        return [
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Sync orders from Statistics API (includes financial data)
     *
     * @param  int  $flag  0=all, 1=income only
     * @return array Sync results
     */
    public function syncOrders(MarketplaceAccount $account, ?\DateTimeInterface $from = null, int $flag = 0): array
    {
        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];

        $dateFrom = $from ?? now()->subDays(7);

        Log::info('Syncing WB orders from Statistics API', [
            'account_id' => $account->id,
            'date_from' => $dateFrom->format('Y-m-d'),
        ]);

        // Broadcast start of sync
        broadcast(new MarketplaceSyncProgress(
            $account->company_id,
            $account->id,
            'started',
            'Синхронизация заказов',
            0
        ));

        try {
            // GET /api/v1/supplier/orders
            $response = $this->getHttpClient($account)->get('statistics', '/api/v1/supplier/orders', [
                'dateFrom' => $dateFrom->format('Y-m-d'),
                'flag' => $flag,
            ]);

            $orders = is_array($response) ? $response : [];

            foreach ($orders as $orderData) {
                try {
                    $result = $this->processOrderFromStatistics($account, $orderData);

                    if ($result['created']) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'srid' => $orderData['srid'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            Log::info('WB orders sync completed', [
                'account_id' => $account->id,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('WB orders sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            $errors[] = ['sync_error' => $e->getMessage()];
        }

        // Broadcast completion with summary
        broadcast(new MarketplaceSyncProgress(
            $account->company_id,
            $account->id,
            'completed',
            'Синхронизация заказов завершена',
            100,
            ['created' => $created, 'updated' => $updated, 'synced' => $synced]
        ));

        // Broadcast data change
        if ($synced > 0 || $created > 0 || $updated > 0) {
            broadcast(new MarketplaceDataChanged(
                $account->company_id,
                $account->id,
                'orders',
                $created > 0 ? 'created' : 'updated',
                $synced,
                null,
                ['created' => $created, 'updated' => $updated]
            ));
        }

        return [
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Process order from Marketplace API (FBS orders)
     */
    protected function processOrderFromMarketplace(MarketplaceAccount $account, array $orderData): array
    {
        $orderId = $orderData['id'] ?? null;

        if (! $orderId) {
            throw new \RuntimeException('Order data missing id');
        }

        // Получаем существующий заказ для определения oldStatus
        $existingOrder = WbOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $orderId)
            ->first();
        $oldStatus = $existingOrder?->status;

        // Терминальные статусы которые НЕ должны перезаписываться при синхронизации "новых" заказов
        $terminalStatuses = ['cancelled', 'canceled', 'completed'];

        // Если заказ уже существует с терминальным статусом - пропускаем обновление статуса
        // чтобы не перезаписать cancelled на new
        $preserveTerminalStatus = $existingOrder && in_array($oldStatus, $terminalStatuses, true);

        // Используем WildberriesClient для мапинга данных
        $marketplaceHttpClient = new \App\Services\Marketplaces\MarketplaceHttpClient($account, 'wb');
        $client = new WildberriesClient($marketplaceHttpClient);
        $mapped = $client->mapOrderData($orderData);

        // Добавляем supply_id если есть
        if (! empty($orderData['supplyId'])) {
            $mapped['supply_id'] = $orderData['supplyId'];
        }

        // Устанавливаем wb_status_group на основе статуса поставки или API статуса
        if (! empty($orderData['supply_status_group'])) {
            $mapped['wb_status_group'] = $orderData['supply_status_group'];
        } elseif (! empty($mapped['supply_id'])) {
            // Если есть поставка, но нет статуса - ищем поставку в БД
            $supply = Supply::where('marketplace_account_id', $account->id)
                ->where('external_supply_id', $mapped['supply_id'])
                ->first();

            if ($supply) {
                // Определяем статус на основе статуса поставки
                $mapped['wb_status_group'] = match ($supply->status) {
                    'draft', 'in_assembly' => 'assembling',
                    'ready' => 'assembling',
                    'sent', 'in_delivery' => 'shipping',
                    'delivered' => 'archive',
                    'cancelled' => 'canceled',
                    default => 'new'
                };
            } else {
                // Поставка не найдена - определяем по API статусу
                $mapped['wb_status_group'] = $this->mapWbStatusToGroup(
                    $mapped['wb_supplier_status'] ?? null,
                    $mapped['wb_status'] ?? null
                );
            }
        } else {
            // Если нет поставки - определяем по API статусу (supplierStatus + wbStatus)
            $mapped['wb_status_group'] = $this->mapWbStatusToGroup(
                $mapped['wb_supplier_status'] ?? null,
                $mapped['wb_status'] ?? null
            );
        }

        // Если существующий заказ имеет терминальный статус - сохраняем его
        if ($preserveTerminalStatus) {
            $mapped['status'] = $oldStatus;
            $mapped['status_normalized'] = $oldStatus;
            // Также сохраняем существующий wb_status_group
            $mapped['wb_status_group'] = $existingOrder->wb_status_group;

            Log::info('Preserving terminal status for existing order', [
                'order_id' => $orderId,
                'preserved_status' => $oldStatus,
            ]);
        } elseif (! empty($orderData['supply_status'])) {
            $mapped['status'] = $orderData['supply_status'];
            $mapped['status_normalized'] = $orderData['supply_status'];
        } elseif (! empty($mapped['wb_status_group'])) {
            // Устанавливаем status на основе wb_status_group
            $mapped['status'] = match ($mapped['wb_status_group']) {
                'new' => 'new',
                'assembling' => 'in_assembly',
                'shipping' => 'in_delivery',
                'archive' => 'completed',
                'canceled' => 'cancelled',
                default => 'new'
            };
            $mapped['status_normalized'] = $mapped['status'];
        } else {
            // Если wb_status_group не установлен, вычисляем статус напрямую
            $mapped['status'] = $this->mapWbStatusToInternal(
                $mapped['wb_supplier_status'] ?? null,
                $mapped['wb_status'] ?? null
            );
            $mapped['status_normalized'] = $mapped['status'];
        }

        // Используем OrdersSyncService для сохранения
        $clientFactory = app(\App\Services\Marketplaces\MarketplaceClientFactory::class);
        $syncService = new OrdersSyncService($clientFactory);

        // Вызываем через reflection, т.к. метод protected
        $reflection = new \ReflectionClass($syncService);
        $method = $reflection->getMethod('persistWbOrder');
        $method->setAccessible(true);
        $result = $method->invoke($syncService, $account, $mapped);

        $created = $result === 'created';

        // Получаем заказ из БД для возврата
        $order = WbOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $orderId)
            ->first();

        // Обрабатываем изменение остатков
        if ($order) {
            $newStatus = $order->status;

            // Обрабатываем только если статус изменился или это новый заказ
            if ($created || $oldStatus !== $newStatus) {
                try {
                    // Получаем позиции заказа
                    $items = $this->orderStockService->getOrderItems($order, 'wb');

                    // Обрабатываем изменение статуса
                    $stockResult = $this->orderStockService->processOrderStatusChange(
                        $account,
                        $order,
                        $oldStatus,
                        $newStatus,
                        $items
                    );

                    Log::info('WB order stock processed', [
                        'order_id' => $order->id,
                        'external_order_id' => $orderId,
                        'old_status' => $oldStatus,
                        'new_status' => $newStatus,
                        'stock_result' => $stockResult,
                    ]);
                } catch (\Throwable $e) {
                    // Не прерываем синхронизацию из-за ошибки остатков
                    Log::error('WB order stock processing failed', [
                        'order_id' => $order->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }

        return ['order' => $order, 'created' => $created];
    }

    /**
     * Process order from Statistics API (detailed with financial data)
     */
    protected function processOrderFromStatistics(MarketplaceAccount $account, array $orderData): array
    {
        $srid = $orderData['srid'] ?? null;

        if (! $srid) {
            throw new \RuntimeException('Order data missing srid');
        }

        $order = WildberriesOrder::where('marketplace_account_id', $account->id)
            ->where('srid', $srid)
            ->first();

        $created = ! $order;

        if (! $order) {
            $order = new WildberriesOrder;
            $order->marketplace_account_id = $account->id;
            $order->srid = $srid;
        }

        $order->fill([
            'order_id' => $orderData['odid'] ?? null,
            'odid' => $orderData['odid'] ?? null,
            'rid' => $orderData['rid'] ?? null,
            'nm_id' => $orderData['nmId'] ?? null,
            'supplier_article' => $orderData['supplierArticle'] ?? null,
            'barcode' => $orderData['barcode'] ?? null,
            'tech_size' => $orderData['techSize'] ?? null,
            'brand' => $orderData['brand'] ?? null,
            'subject' => $orderData['subject'] ?? null,
            'category' => $orderData['category'] ?? null,
            'warehouse_name' => $orderData['warehouseName'] ?? null,
            // warehouseType: "Склад продавца" = FBS, "Склад WB" = FBW/FBO
            'warehouse_type' => $orderData['warehouseType'] ?? null,
            'status' => $this->mapStatisticsStatus($orderData),
            'wb_status' => $orderData['orderType'] ?? null,
            'is_cancel' => (bool) ($orderData['isCancel'] ?? false),
            'is_return' => (bool) ($orderData['isReturn'] ?? false),
            // isRealization - boolean field from Statistics API indicating completed sale
            'is_realization' => (bool) ($orderData['isRealization'] ?? false),
            'price' => $orderData['priceWithDisc'] ?? null,
            'discount_percent' => $orderData['discountPercent'] ?? null,
            'total_price' => $orderData['totalPrice'] ?? null,
            'finished_price' => $orderData['finishedPrice'] ?? null,
            'for_pay' => $orderData['forPay'] ?? null,
            'spp' => $orderData['spp'] ?? null,
            'region_name' => $orderData['regionName'] ?? null,
            'oblast_okrug_name' => $orderData['oblastOkrugName'] ?? null,
            'country_name' => $orderData['countryName'] ?? null,
            // WB API возвращает время в московском часовом поясе (UTC+3)
            // Конвертируем в ташкентское время (UTC+5)
            'order_date' => isset($orderData['date']) ? Carbon::parse($orderData['date'], 'Europe/Moscow')->setTimezone('Asia/Tashkent') : null,
            'cancel_date' => isset($orderData['cancelDt']) ? Carbon::parse($orderData['cancelDt'], 'Europe/Moscow')->setTimezone('Asia/Tashkent') : null,
            'last_change_date' => isset($orderData['lastChangeDate']) ? Carbon::parse($orderData['lastChangeDate'], 'Europe/Moscow')->setTimezone('Asia/Tashkent') : now(),
            'income_id' => $orderData['incomeID'] ?? null,
            'raw_data' => $orderData,
        ]);

        $order->save();

        return ['order' => $order, 'created' => $created];
    }

    /**
     * Map Marketplace API status to internal status
     */
    protected function mapMarketplaceStatus(?string $status): string
    {
        if (! $status) {
            return 'unknown';
        }

        return match ($status) {
            'waiting' => 'new',
            'sorted' => 'processing',
            'sold' => 'delivered',
            'canceled', 'canceled_by_client' => 'cancelled',
            'defect' => 'defect',
            'ready_for_pickup' => 'shipped',
            default => $status,
        };
    }

    /**
     * Map WB status + supply presence to status group used on UI
     */
    protected function mapWbStatusGroup(?string $wbStatus, ?string $supplyId): string
    {
        // Если заказ прикреплён к поставке – считаем "На сборке"
        if (! empty($supplyId)) {
            return 'assembling';
        }

        $status = strtolower($wbStatus ?? '');

        return match ($status) {
            // Сортировка/обработка
            'sort', 'sorted', 'assembling', 'confirm' => 'assembling',
            // В пути/доставка
            'ready_for_pickup', 'on_way_to_client', 'delivering', 'shipped', 'shipping', 'complete' => 'shipping',
            // Возвраты (тоже в доставке)
            'on_way_from_client', 'return' => 'shipping',
            // Завершённые (архив)
            'sold', 'sold_from_store', 'delivered', 'receive' => 'archive',
            // Отмены/брак
            'canceled', 'canceled_by_client', 'cancel', 'reject', 'defect' => 'canceled',
            // Новые заказы
            'waiting', 'new' => 'new',
            default => 'new',
        };
    }

    /**
     * Map WB API statuses to internal status
     * Based on supplierStatus and wbStatus from WB API
     */
    protected function mapWbStatusToInternal(?string $supplierStatus, ?string $wbStatus): string
    {
        // 1. Отменённые (высший приоритет)
        if (in_array($supplierStatus, ['cancel', 'reject']) ||
            in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'cancelled';
        }

        // 2. Завершённые (доставлено клиенту)
        if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
            $supplierStatus === 'receive') {
            return 'completed';
        }

        // 3. В доставке
        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'accepted_by_carrier', 'sent_to_carrier'])) {
            return 'in_delivery';
        }

        // 4. На сборке
        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted'])) {
            return 'in_assembly';
        }

        // 5. Новые
        return 'new';
    }

    /**
     * Map WB API statuses to status group
     * Based on supplierStatus and wbStatus from WB API
     *
     * supplierStatus: new, confirm, complete, cancel
     * wbStatus: waiting, sorted, sold, canceled, canceled_by_client, declined_by_client,
     *           defect, ready_for_pickup, on_way_to_client, delivered, etc.
     */
    protected function mapWbStatusToGroup(?string $supplierStatus, ?string $wbStatus): string
    {
        // 1. Отменённые (высший приоритет)
        if (in_array($supplierStatus, ['cancel', 'reject']) ||
            in_array($wbStatus, ['canceled', 'canceled_by_client', 'declined_by_client', 'defect'])) {
            return 'canceled';
        }

        // 2. Завершённые/Архив (доставлено клиенту)
        if (in_array($wbStatus, ['delivered', 'sold_from_store', 'sold']) ||
            $supplierStatus === 'receive') {
            return 'archive';
        }

        // 3. В доставке (поставка передана в WB, доставляется клиенту)
        if ($supplierStatus === 'complete' ||
            in_array($wbStatus, ['on_way_to_client', 'on_way_from_client', 'ready_for_pickup', 'accepted_by_carrier', 'sent_to_carrier'])) {
            return 'shipping';
        }

        // 4. На сборке (заказ подтверждён, собирается)
        if ($supplierStatus === 'confirm' ||
            in_array($wbStatus, ['sorted'])) {
            return 'assembling';
        }

        // 5. Новые (ожидают подтверждения/добавления в поставку)
        if ($supplierStatus === 'new' ||
            $wbStatus === 'waiting') {
            return 'new';
        }

        // По умолчанию - новые
        return 'new';
    }

    /**
     * Map Statistics API order to internal status
     *
     * isRealization = true means item was sold and money received (completed sale)
     * isCancel = true means order was cancelled
     * isReturn = true means item was returned
     */
    protected function mapStatisticsStatus(array $orderData): string
    {
        if ($orderData['isCancel'] ?? false) {
            return 'cancelled';
        }

        if ($orderData['isReturn'] ?? false) {
            return 'returned';
        }

        // isRealization = true означает завершённую продажу (деньги получены)
        if ($orderData['isRealization'] ?? false) {
            return 'delivered';
        }

        return 'processing';
    }

    /**
     * Get orders summary for period
     */
    public function getOrdersSummary(MarketplaceAccount $account, Carbon $from, Carbon $to): array
    {
        return WildberriesOrder::where('marketplace_account_id', $account->id)
            ->whereBetween('order_date', [$from, $to])
            ->selectRaw('
                COUNT(*) as total_orders,
                SUM(CASE WHEN is_cancel = 0 AND is_return = 0 THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN is_cancel = 1 THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN is_return = 1 THEN 1 ELSE 0 END) as returned_orders,
                SUM(CASE WHEN is_cancel = 0 AND is_return = 0 THEN COALESCE(for_pay, finished_price, 0) ELSE 0 END) as total_revenue
            ')
            ->first()
            ->toArray();
    }

    /**
     * Get supply barcode/QR code
     *
     * @param  string  $supplyId  Supply ID (UUID)
     * @param  string  $type  Barcode type: 'svg', 'png', 'pdf' (default: 'png')
     * @return array ['file_content' => string, 'content_type' => string, 'format' => string]
     */
    public function getSupplyBarcode(MarketplaceAccount $account, string $supplyId, string $type = 'png'): array
    {
        try {
            // Получаем бинарные данные через GET запрос с параметром type
            $fileContent = $this->getHttpClient($account)->getBinary(
                'marketplace',
                "/api/v3/supplies/{$supplyId}/barcode",
                ['type' => $type]
            );

            $contentType = match ($type) {
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
                default => 'image/png',
            };

            Log::info('WB supply barcode fetched', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'type' => $type,
                'size' => strlen($fileContent),
            ]);

            return [
                'file_content' => $fileContent,
                'content_type' => $contentType,
                'format' => $type,
                'supply_id' => $supplyId,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get WB supply barcode', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get stickers for orders
     * POST /api/v3/orders/stickers
     *
     * @param  array  $orderIds  Array of order IDs (max 100)
     * @param  string  $type  Format: png|svg|zplv|zplh
     * @param  int  $width  Width in mm (default 58)
     * @param  int  $height  Height in mm (default 40)
     * @return string Binary content
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function getOrdersStickers(
        MarketplaceAccount $account,
        array $orderIds,
        string $type = 'png',
        int $width = 58,
        int $height = 40
    ): string {
        if (empty($orderIds)) {
            throw new \InvalidArgumentException('Order IDs array cannot be empty');
        }

        if (count($orderIds) > 100) {
            throw new \InvalidArgumentException('Maximum 100 order IDs allowed per request');
        }

        $allowedTypes = ['png', 'svg', 'zplv', 'zplh'];
        if (! in_array($type, $allowedTypes)) {
            throw new \InvalidArgumentException('Invalid sticker type. Allowed: '.implode(', ', $allowedTypes));
        }

        try {
            // Use postBinary for sticker generation
            $response = $this->getHttpClient($account)->postBinary(
                'marketplace',
                '/api/v3/orders/stickers',
                ['orders' => array_map('intval', $orderIds)],
                [
                    'type' => $type,
                    'width' => $width,
                    'height' => $height,
                ]
            );

            // WB API может вернуть либо бинарные данные, либо JSON с base64
            $fileContent = $response;

            // Проверяем, является ли ответ JSON
            if (substr($response, 0, 1) === '{' || substr($response, 0, 1) === '[') {
                $json = json_decode($response, true);

                if (json_last_error() === JSON_ERROR_NONE && isset($json['stickers'])) {
                    // Декодируем base64 из первого стикера
                    $fileContent = base64_decode($json['stickers'][0]['file']);

                    Log::info('WB API returned JSON response, decoded base64', [
                        'account_id' => $account->id,
                        'stickers_count' => count($json['stickers']),
                    ]);
                }
            }

            Log::info('WB order stickers generated', [
                'account_id' => $account->id,
                'orders_count' => count($orderIds),
                'type' => $type,
                'size' => strlen($fileContent),
            ]);

            return $fileContent;
        } catch (\Exception $e) {
            Log::error('Failed to generate WB order stickers', [
                'account_id' => $account->id,
                'orders_count' => count($orderIds),
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create tare (box) for supply via WB API
     * POST /api/v3/supplies/{supplyId}/trbx
     *
     * According to WB API documentation, boxes are created after orders are added to the supply.
     * The request body requires an 'amount' field specifying the quantity of boxes to create.
     *
     * @param  string  $supplyId  Supply ID (WB-GI-XXXXXXX format)
     * @param  int  $amount  Number of boxes to create (default: 1, max: 1000)
     * @return array Response with trbxIds array
     */
    public function createTare(MarketplaceAccount $account, string $supplyId, int $amount = 1): array
    {
        try {
            $response = $this->getHttpClient($account)->post('marketplace', "/api/v3/supplies/{$supplyId}/trbx", [
                'amount' => $amount,
            ]);

            Log::info('WB tare created', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'amount' => $amount,
                'trbx_ids' => $response['trbxIds'] ?? [],
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create WB tare', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Add orders to tare (box) via WB API
     * PATCH /api/v3/supplies/{supplyId}/trbx/{trbxId}
     *
     * @param  string  $supplyId  Supply ID
     * @param  string  $trbxId  Tare/box ID
     * @param  array  $orderIds  Array of order IDs to add to the box
     * @return array Response
     */
    public function addOrdersToTare(MarketplaceAccount $account, string $supplyId, string $trbxId, array $orderIds): array
    {
        try {
            $response = $this->getHttpClient($account)->patch('marketplace', "/api/v3/supplies/{$supplyId}/trbx/{$trbxId}", [
                'orderIds' => $orderIds,
            ]);

            Log::info('WB orders added to tare', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'trbx_id' => $trbxId,
                'orders_count' => count($orderIds),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to add orders to WB tare', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'trbx_id' => $trbxId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get list of tares (boxes) with orders for supply
     * GET /api/v3/supplies/{supplyId}/trbx
     *
     * @param  string  $supplyId  Supply ID
     * @return array List of tares/boxes
     */
    public function getTares(MarketplaceAccount $account, string $supplyId): array
    {
        try {
            $response = $this->getHttpClient($account)->get('marketplace', "/api/v3/supplies/{$supplyId}/trbx");

            Log::info('WB tares fetched', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'tares_count' => count($response['trbxs'] ?? []),
            ]);

            return $response['trbxs'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB tares', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get tare stickers/barcodes for supply
     * POST /api/v3/supplies/{supplyId}/trbx/stickers
     *
     * @param  string  $supplyId  Supply ID
     * @param  string  $type  Format: png, svg, pdf
     * @return array Binary content with metadata
     */
    public function getTareStickers(MarketplaceAccount $account, string $supplyId, string $type = 'png'): array
    {
        try {
            $response = $this->getHttpClient($account)->post('marketplace', "/api/v3/supplies/{$supplyId}/trbx/stickers", [
                'type' => $type,
            ]);

            Log::info('WB tare stickers fetched', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'type' => $type,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB tare stickers', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get tare (box) barcode from WB API
     *
     * @param  string  $supplyId  Supply ID
     * @param  string  $tareId  Tare ID (barcode)
     * @param  string  $type  Format: png, svg, pdf
     */
    public function getTareBarcode(MarketplaceAccount $account, string $supplyId, string $tareId, string $type = 'png'): array
    {
        try {
            // Получаем бинарные данные через GET запрос с параметром type
            $fileContent = $this->getHttpClient($account)->getBinary(
                'marketplace',
                "/api/v3/supplies/{$supplyId}/tares/{$tareId}/barcode",
                ['type' => $type]
            );

            $contentType = match ($type) {
                'svg' => 'image/svg+xml',
                'png' => 'image/png',
                'pdf' => 'application/pdf',
                default => 'image/png',
            };

            Log::info('WB tare barcode fetched', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'tare_id' => $tareId,
                'type' => $type,
                'size' => strlen($fileContent),
            ]);

            return [
                'file_content' => $fileContent,
                'content_type' => $contentType,
                'format' => $type,
                'supply_id' => $supplyId,
                'tare_id' => $tareId,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get WB tare barcode', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'tare_id' => $tareId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel supply
     *
     * @param  string  $supplyId  Supply ID (UUID)
     */
    public function cancelSupply(MarketplaceAccount $account, string $supplyId): bool
    {
        try {
            $httpClient = new WildberriesHttpClient($account);
            $httpClient->patch('marketplace', "/api/v3/supplies/{$supplyId}/cancel");

            Log::info('WB supply cancelled', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to cancel WB supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Remove order from supply
     *
     * @param  string  $supplyId  Supply ID (UUID)
     * @param  int  $orderId  Order ID
     */
    public function removeOrderFromSupply(MarketplaceAccount $account, string $supplyId, int $orderId): bool
    {
        try {
            $this->getHttpClient($account)->delete('marketplace', "/api/v3/supplies/{$supplyId}/orders/{$orderId}");

            Log::info('WB order removed from supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'order_id' => $orderId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to remove WB order from supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get orders that require reshipment
     *
     * @return array Orders requiring reshipment
     */
    public function getReshipmentOrders(MarketplaceAccount $account): array
    {
        try {
            $response = $this->getHttpClient($account)->get('marketplace', '/api/v3/supplies/orders/reshipment');

            Log::info('WB reshipment orders fetched', [
                'account_id' => $account->id,
                'count' => count($response['orders'] ?? []),
            ]);

            return $response['orders'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get WB reshipment orders', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get supply details
     *
     * @param  string  $supplyId  Supply ID (UUID)
     * @return array Supply details
     */
    public function getSupplyDetails(MarketplaceAccount $account, string $supplyId): array
    {
        try {
            $response = $this->getHttpClient($account)->get('marketplace', "/api/v3/supplies/{$supplyId}");

            Log::info('WB supply details fetched', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB supply details', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get order IDs in supply (new API method)
     *
     * @param  string  $supplyId  Supply ID (UUID)
     * @return array Order IDs in the supply
     */
    public function getSupplyOrderIds(MarketplaceAccount $account, string $supplyId): array
    {
        try {
            // New API endpoint (replaces deprecated GET /api/v3/supplies/{supplyId}/orders)
            $response = $this->getHttpClient($account)->get('marketplace', "/api/marketplace/v3/supplies/{$supplyId}/order-ids");

            $orderIds = $response['orderIds'] ?? [];

            Log::info('WB supply order IDs fetched', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'count' => count($orderIds),
            ]);

            return $orderIds;
        } catch (\Exception $e) {
            Log::error('Failed to get WB supply order IDs', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get orders in supply with full details
     *
     * @param  string  $supplyId  Supply ID (UUID)
     * @return array Orders in the supply with details
     */
    public function getSupplyOrders(MarketplaceAccount $account, string $supplyId): array
    {
        try {
            // Step 1: Get order IDs from the supply
            $orderIds = $this->getSupplyOrderIds($account, $supplyId);

            if (empty($orderIds)) {
                Log::info('WB supply has no orders', [
                    'account_id' => $account->id,
                    'supply_id' => $supplyId,
                ]);

                return [];
            }

            // Step 2: Get order details via POST /api/v3/orders/status
            $statusResponse = $this->getOrdersStatus($account, $orderIds);
            $orders = $statusResponse['orders'] ?? [];

            Log::info('WB supply orders fetched', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'order_ids_count' => count($orderIds),
                'orders_fetched' => count($orders),
            ]);

            return $orders;
        } catch (\Exception $e) {
            Log::error('Failed to get WB supply orders', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Sync orders from a specific supply
     *
     * @param  string  $supplyId  Supply ID (UUID)
     * @return array Sync results
     */
    public function syncSupplyOrders(MarketplaceAccount $account, string $supplyId): array
    {
        $synced = 0;
        $created = 0;
        $updated = 0;
        $errors = [];

        Log::info('Syncing orders from WB supply', [
            'account_id' => $account->id,
            'supply_id' => $supplyId,
        ]);

        try {
            // Получаем данные поставки для определения статуса
            $supply = Supply::where('marketplace_account_id', $account->id)
                ->where('external_supply_id', $supplyId)
                ->first();

            // Определяем wb_status_group на основе статуса поставки
            $wbStatusGroup = 'new';
            $status = 'new';
            if ($supply) {
                [$wbStatusGroup, $status] = match ($supply->status) {
                    'draft' => ['assembling', 'in_assembly'],
                    'sent' => ['shipping', 'in_delivery'],
                    'delivered' => ['archive', 'completed'],
                    'cancelled' => ['canceled', 'canceled'],
                    default => ['new', 'new']
                };
            }

            $orders = $this->getSupplyOrders($account, $supplyId);

            foreach ($orders as $orderData) {
                try {
                    // Add supply_id and status info to order data
                    $orderData['supplyId'] = $supplyId;
                    $orderData['supply_status_group'] = $wbStatusGroup;
                    $orderData['supply_status'] = $status;

                    $result = $this->processOrderFromMarketplace($account, $orderData);

                    if ($result['created']) {
                        $created++;
                    } else {
                        $updated++;
                    }

                    $synced++;
                } catch (\Exception $e) {
                    $errors[] = [
                        'order_id' => $orderData['id'] ?? 'unknown',
                        'error' => $e->getMessage(),
                    ];
                }
            }

            // Update supply counters
            $this->updateSupplyCounters($account, $supplyId);

            Log::info('WB supply orders sync completed', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ]);

        } catch (\Exception $e) {
            Log::error('WB supply orders sync failed', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            $errors[] = ['fetch_error' => $e->getMessage()];
        }

        return [
            'synced' => $synced,
            'created' => $created,
            'updated' => $updated,
            'errors' => $errors,
        ];
    }

    /**
     * Update supply counters (orders_count and total_amount)
     */
    protected function updateSupplyCounters(MarketplaceAccount $account, string $supplyId): void
    {
        $supply = Supply::where('marketplace_account_id', $account->id)
            ->where('external_supply_id', $supplyId)
            ->first();

        if ($supply) {
            // Используем новую таблицу wb_orders
            $orders = WbOrder::where('marketplace_account_id', $account->id)
                ->where('supply_id', $supplyId)
                ->get();

            $supply->orders_count = $orders->count();
            $supply->total_amount = $orders->sum('total_amount');
            $supply->save();

            Log::info('Supply counters updated', [
                'supply_id' => $supplyId,
                'orders_count' => $supply->orders_count,
                'total_amount' => $supply->total_amount,
            ]);
        }
    }

    /**
     * Пересчитать статусы заказов на основе статуса поставки
     */
    public function refreshOrdersStatusFromSupplies(MarketplaceAccount $account): array
    {
        $supplies = [];
        Supply::where('marketplace_account_id', $account->id)
            ->whereNotNull('external_supply_id')
            ->get()
            ->each(function ($supply) use (&$supplies) {
                $supplies[$supply->external_supply_id] = $supply;
                $supplies['SUPPLY-'.$supply->id] = $supply;
                $supplies[(string) $supply->id] = $supply;
            });

        $updated = 0;

        WbOrder::where('marketplace_account_id', $account->id)
            ->whereNotNull('supply_id')
            ->chunkById(200, function ($orders) use (&$updated, $supplies) {
                foreach ($orders as $order) {
                    $supply = $supplies[$order->supply_id] ?? null;
                    if (! $supply) {
                        continue;
                    }

                    $mapped = $this->mapOrderStatusBySupply($supply);
                    if (empty($mapped)) {
                        continue;
                    }

                    $payload = [];
                    if (! empty($mapped['status']) && $order->status !== $mapped['status']) {
                        $payload['status'] = $mapped['status'];
                    }
                    if (! empty($mapped['wb_status_group']) && $order->wb_status_group !== $mapped['wb_status_group']) {
                        $payload['wb_status_group'] = $mapped['wb_status_group'];
                    }
                    if (! empty($mapped['delivered_at']) && ! $order->delivered_at) {
                        $payload['delivered_at'] = $mapped['delivered_at'];
                    }

                    if (! empty($payload)) {
                        $order->update($payload);
                        $updated++;
                    }
                }
            });

        if ($updated > 0) {
            broadcast(new \App\Events\MarketplaceDataChanged(
                $account->company_id,
                $account->id,
                'orders',
                'updated',
                $updated,
                null,
                ['source' => 'supplies']
            ));
        }

        return ['updated' => $updated];
    }

    /**
     * Соотнесение статуса заказа со статусом поставки
     */
    protected function mapOrderStatusBySupply(Supply $supply): array
    {
        return match ($supply->status) {
            Supply::STATUS_DELIVERED => [
                'status' => 'completed',
                'wb_status_group' => 'archive',
                'delivered_at' => $supply->delivered_at ?? $supply->closed_at,
            ],
            Supply::STATUS_SENT => [
                'status' => 'in_delivery',
                'wb_status_group' => 'shipping',
            ],
            Supply::STATUS_READY,
            Supply::STATUS_IN_ASSEMBLY,
            Supply::STATUS_DRAFT => [
                'status' => 'in_assembly',
                'wb_status_group' => 'assembling',
            ],
            Supply::STATUS_CANCELLED => [
                'status' => 'cancelled',
                'wb_status_group' => 'canceled',
            ],
            default => [],
        };
    }

    /**
     * Add orders to supply
     *
     * @param  string  $supplyId  Supply ID (UUID)
     * @param  array  $orderIds  Array of order IDs
     * @return array Result with updated count
     */
    public function addOrdersToSupply(MarketplaceAccount $account, string $supplyId, array $orderIds): array
    {
        if (empty($orderIds)) {
            throw new \InvalidArgumentException('Order IDs array cannot be empty');
        }

        try {
            // 1. Отправляем запрос в WB API
            $httpClient = new WildberriesHttpClient($account);
            $httpClient->patch('marketplace', "/api/v3/supplies/{$supplyId}/orders", [
                'orders' => array_map('intval', $orderIds),
            ]);

            Log::info('WB orders added to supply via API', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'orders_count' => count($orderIds),
            ]);

            // 2. Находим локальную поставку
            $supply = Supply::where('marketplace_account_id', $account->id)
                ->where('external_supply_id', $supplyId)
                ->first();

            if (! $supply) {
                throw new \RuntimeException("Supply with external_supply_id {$supplyId} not found in database");
            }

            // 3. Обновляем локальные записи заказов
            $updated = 0;
            foreach ($orderIds as $externalOrderId) {
                $order = WbOrder::where('marketplace_account_id', $account->id)
                    ->where('external_order_id', $externalOrderId)
                    ->first();

                if ($order) {
                    $order->update([
                        'supply_id' => $supplyId,
                        'status' => 'in_assembly',
                    ]);

                    // Обновляем raw_payload
                    $payload = $order->raw_payload ?? [];
                    $payload['supplyId'] = $supplyId;
                    $order->update(['raw_payload' => $payload]);

                    $updated++;
                }
            }

            // 4. Обновляем счетчики поставки
            $this->updateSupplyCounters($account, $supplyId);

            Log::info('Local orders updated after WB sync', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'updated' => $updated,
            ]);

            return [
                'success' => true,
                'updated' => $updated,
                'supply_id' => $supplyId,
            ];

        } catch (\Exception $e) {
            Log::error('Failed to add WB orders to supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create new supply
     *
     * @param  string  $name  Supply name
     * @return array Created supply data with ID
     */
    public function createSupply(MarketplaceAccount $account, string $name): array
    {
        try {
            $httpClient = new WildberriesHttpClient($account);
            $response = $httpClient->post('marketplace', '/api/v3/supplies', [
                'name' => $name,
            ]);

            Log::info('WB supply created', [
                'account_id' => $account->id,
                'supply_id' => $response['id'] ?? null,
                'name' => $name,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to create WB supply', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Get list of supplies
     *
     * @param  int  $limit  Limit per page (default: 1000, max: 1000)
     * @param  int  $next  Cursor for pagination
     * @return array Supplies list with pagination info
     */
    public function getSupplies(MarketplaceAccount $account, int $limit = 1000, int $next = 0): array
    {
        try {
            $httpClient = new WildberriesHttpClient($account);
            $params = [
                'limit' => min($limit, 1000),
                'next' => $next,
            ];

            $response = $httpClient->get('marketplace', '/api/v3/supplies', $params);

            Log::info('WB supplies list fetched', [
                'account_id' => $account->id,
                'count' => count($response['supplies'] ?? []),
                'next' => $response['next'] ?? null,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB supplies list', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить статусы заказов
     * POST /api/v3/orders/status
     *
     * @param  array  $orderIds  Массив external_order_id (до 1000)
     */
    public function getOrdersStatus(MarketplaceAccount $account, array $orderIds): array
    {
        try {
            $response = $this->getHttpClient($account)->post('marketplace', '/api/v3/orders/status', [
                'orders' => array_map('intval', $orderIds),
            ]);

            Log::info('WB orders status fetched', [
                'account_id' => $account->id,
                'count' => count($response['orders'] ?? []),
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB orders status', [
                'account_id' => $account->id,
                'order_ids_count' => count($orderIds),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Получить стикеры заказов
     * POST /api/v3/orders/stickers
     *
     * @param  array  $orderIds  Массив external_order_id (до 100)
     * @param  string  $type  Формат: svg|zplv|zplh|png
     * @param  int  $width  Ширина: 58|40
     * @param  int  $height  Высота: 40|30
     */
    public function getOrderStickers(
        MarketplaceAccount $account,
        array $orderIds,
        string $type = 'png',
        int $width = 58,
        int $height = 40
    ): array {
        try {
            $response = $this->getHttpClient($account)->post('marketplace', '/api/v3/orders/stickers', [
                'orders' => array_map('intval', $orderIds),
            ], [
                'type' => $type,
                'width' => $width,
                'height' => $height,
            ]);

            Log::info('WB order stickers generated', [
                'account_id' => $account->id,
                'count' => count($orderIds),
                'type' => $type,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get WB order stickers', [
                'account_id' => $account->id,
                'order_ids_count' => count($orderIds),
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Отменить заказ
     * PATCH /api/v3/orders/{orderId}/cancel
     *
     * @param  string  $orderId  external_order_id
     */
    public function cancelOrder(MarketplaceAccount $account, string $orderId): array
    {
        try {
            $response = $this->getHttpClient($account)->patch('marketplace', "/api/v3/orders/{$orderId}/cancel");

            Log::info('WB order cancelled', [
                'account_id' => $account->id,
                'order_id' => $orderId,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to cancel WB order', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Передать поставку в доставку
     * PATCH /api/v3/supplies/{supplyId}/deliver
     *
     * @param  string  $supplyId  ID поставки (WB-GI-XXXXXXX)
     */
    public function deliverSupply(MarketplaceAccount $account, string $supplyId): array
    {
        try {
            $response = $this->getHttpClient($account)->patch('marketplace', "/api/v3/supplies/{$supplyId}/deliver");

            Log::info('WB supply delivered', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
            ]);

            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to deliver WB supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
