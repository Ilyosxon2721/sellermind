<?php

// file: app/Services/Marketplaces/Sync/OrdersSyncService.php

namespace App\Services\Marketplaces\Sync;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use App\Models\UzumOrder;
use App\Models\UzumOrderItem;
use App\Models\WbOrder;
use App\Models\WbOrderItem;
use App\Services\Marketplaces\MarketplaceClientFactory;
use App\Services\Stock\OrderStockService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrdersSyncService
{
    protected OrderStockService $orderStockService;

    public function __construct(
        protected MarketplaceClientFactory $clientFactory,
        ?OrderStockService $orderStockService = null
    ) {
        $this->orderStockService = $orderStockService ?? new OrderStockService;
    }

    /**
     * Sync orders for all active accounts (or filtered by marketplace)
     */
    public function syncAll(string $marketplace = 'all', int $daysBack = 7): array
    {
        $query = MarketplaceAccount::query()->where('is_active', true);

        if ($marketplace !== 'all') {
            $query->where('marketplace', $marketplace);
        }

        $accounts = $query->get();
        $results = [];

        foreach ($accounts as $account) {
            $results[$account->id] = $this->syncAccountOrders($account, $daysBack);
        }

        return $results;
    }

    /**
     * Sync orders for a specific account
     */
    public function syncAccountOrders(MarketplaceAccount $account, int $daysBack = 7): array
    {
        $from = now()->subDays($daysBack)->startOfDay();
        $to = now()->endOfDay();

        // Create sync log
        $syncLog = MarketplaceSyncLog::start(
            $account->id,
            MarketplaceSyncLog::TYPE_ORDERS,
            [
                'from' => $from->toIso8601String(),
                'to' => $to->toIso8601String(),
                'days_back' => $daysBack,
            ]
        );

        try {
            Log::channel('daily')->info('Starting orders sync', [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace,
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
            ]);

            $client = $this->clientFactory->forAccount($account);
            $ordersData = $client->fetchOrders($account, $from, $to);

            $stats = $this->persistOrders($account, $ordersData);

            $syncLog->markAsSuccess(
                "Синхронизировано заказов: {$stats['created']} новых, {$stats['updated']} обновлено",
                $stats
            );

            Log::channel('daily')->info('Orders sync completed', [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace,
                'stats' => $stats,
            ]);

            return [
                'success' => true,
                'stats' => $stats,
            ];
        } catch (\Throwable $e) {
            $errorMessage = mb_substr($e->getMessage(), 0, 500);

            $syncLog->markAsError($errorMessage);

            Log::channel('daily')->error('Orders sync failed', [
                'account_id' => $account->id,
                'marketplace' => $account->marketplace,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 1000),
            ]);

            return [
                'success' => false,
                'error' => $errorMessage,
            ];
        }
    }

    /**
     * Persist orders data to database
     */
    protected function persistOrders(MarketplaceAccount $account, array $ordersData): array
    {
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($ordersData as $orderData) {
            try {
                $result = $this->persistOrder($account, $orderData);

                if ($result === 'created') {
                    $created++;
                } elseif ($result === 'updated') {
                    $updated++;
                } else {
                    $skipped++;
                }
            } catch (\Throwable $e) {
                $errors++;
                Log::channel('daily')->warning('Failed to persist order', [
                    'account_id' => $account->id,
                    'external_order_id' => $orderData['external_order_id'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'total' => count($ordersData),
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Persist single order
     */
    protected function persistOrder(MarketplaceAccount $account, array $orderData): string
    {
        if ($account->marketplace === 'wb') {
            return $this->persistWbOrder($account, $orderData);
        }

        if ($account->marketplace === 'uzum') {
            return $this->persistUzumOrder($account, $orderData);
        }

        throw new \RuntimeException("Unsupported marketplace: {$account->marketplace}");
    }

    /**
     * Обработать изменение статуса заказа для остатков
     */
    protected function processOrderStockChange(
        MarketplaceAccount $account,
        $order,
        ?string $oldStatus,
        string $newStatus,
        string $marketplace
    ): void {
        // Обрабатываем только если статус изменился или это новый заказ
        if ($oldStatus === $newStatus) {
            return;
        }

        try {
            $items = $this->orderStockService->getOrderItems($order, $marketplace);

            $stockResult = $this->orderStockService->processOrderStatusChange(
                $account,
                $order,
                $oldStatus,
                $newStatus,
                $items
            );

            Log::info('Order stock processed', [
                'marketplace' => $marketplace,
                'order_id' => $order->id,
                'external_order_id' => $order->external_order_id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'stock_result' => $stockResult,
            ]);
        } catch (\Throwable $e) {
            // Не прерываем синхронизацию из-за ошибки остатков
            Log::error('Order stock processing failed', [
                'marketplace' => $marketplace,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Persist WB order
     */
    protected function persistWbOrder(MarketplaceAccount $account, array $orderData): string
    {
        $externalOrderId = $orderData['external_order_id'] ?? null;

        if (! $externalOrderId) {
            throw new \RuntimeException('Missing external_order_id');
        }

        $existingOrder = WbOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $externalOrderId)
            ->first();

        $oldStatus = $existingOrder?->status;

        // Parse ordered_at date
        $orderedAt = null;
        if (! empty($orderData['ordered_at'])) {
            try {
                $orderedAt = Carbon::parse($orderData['ordered_at']);
            } catch (\Exception $e) {
                $orderedAt = now();
            }
        }

        // Получаем SKU (берём первый из массива skus если есть)
        $sku = null;
        if (! empty($orderData['wb_skus']) && is_array($orderData['wb_skus'])) {
            $sku = (string) $orderData['wb_skus'][0];
        }

        // Получаем office (берём первый из массива offices если есть)
        $office = null;
        if (! empty($orderData['wb_offices']) && is_array($orderData['wb_offices'])) {
            $office = (string) $orderData['wb_offices'][0];
        } elseif (! empty($orderData['wb_office_id'])) {
            $office = (string) $orderData['wb_office_id'];
        }

        $orderPayload = [
            'marketplace_account_id' => $account->id,
            'external_order_id' => $externalOrderId,
            'rid' => $orderData['wb_rid'] ?? null,
            'order_uid' => $orderData['wb_order_uid'] ?? null,
            'nm_id' => $orderData['wb_nm_id'] ?? null,
            'chrt_id' => $orderData['wb_chrt_id'] ?? null,
            'article' => $orderData['wb_article'] ?? null,
            'sku' => $sku,
            'status' => $orderData['status'] ?? 'new',
            'status_normalized' => $orderData['status_normalized'] ?? ($orderData['status'] ?? 'new'),
            'wb_status' => $orderData['wb_status'] ?? null,
            'wb_status_group' => $orderData['wb_status_group'] ?? null,
            'wb_supplier_status' => $orderData['wb_supplier_status'] ?? null,
            'wb_delivery_type' => $orderData['wb_delivery_type'] ?? null,
            'cargo_type' => $orderData['wb_cargo_type'] ?? null,
            'warehouse_id' => $orderData['wb_warehouse_id'] ?? ($orderData['warehouse_id'] ?? null),
            'supply_id' => $orderData['supply_id'] ?? null,
            'office' => $office,
            'customer_name' => $orderData['customer_name'] ?? null,
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'total_amount' => $orderData['total_amount'] ?? 0,
            'price' => $orderData['wb_price'] ?? null,
            'scan_price' => $orderData['wb_scan_price'] ?? null,
            'converted_price' => $orderData['wb_converted_price'] ?? null,
            'currency' => $orderData['currency'] ?? 'RUB',
            'currency_code' => $orderData['wb_currency_code'] ?? null,
            'converted_currency_code' => $orderData['wb_converted_currency_code'] ?? null,
            'is_b2b' => $orderData['wb_is_b2b'] ?? false,
            'is_zero_order' => $orderData['wb_is_zero_order'] ?? false,
            'ordered_at' => $orderedAt,
            'delivered_at' => ! empty($orderData['delivered_at']) ? Carbon::parse($orderData['delivered_at']) : null,
            'raw_payload' => $orderData['raw_payload'] ?? $orderData,
        ];

        $result = DB::transaction(function () use ($existingOrder, $orderPayload, $orderData) {
            if ($existingOrder) {
                // Проверяем были ли реальные изменения
                $hasChanges = false;
                foreach ($orderPayload as $key => $value) {
                    if ($value != $existingOrder->$key) {
                        $hasChanges = true;
                        break;
                    }
                }

                $existingOrder->update($orderPayload);

                // Даже если данные не изменились, обновляем updated_at чтобы показать что синхронизация прошла
                if (! $hasChanges) {
                    $existingOrder->touch();
                }

                $order = $existingOrder;
                $resultType = 'updated';
            } else {
                $order = WbOrder::create($orderPayload);
                $resultType = 'created';
            }

            // Sync order items
            $this->syncWbOrderItems($order, $orderData['items'] ?? []);

            return ['result' => $resultType, 'order' => $order];
        });

        // Обрабатываем изменение статуса для остатков
        $order = $result['order'];
        $newStatus = $order->status;
        $isCreated = $result['result'] === 'created';

        if ($isCreated || $oldStatus !== $newStatus) {
            $this->processOrderStockChange($account, $order, $oldStatus, $newStatus, 'wb');
        }

        // Дополнительная проверка: если заказ отменён, но резерв всё ещё активен - освободить
        $this->ensureCancelledOrderStockReleased($account, $order, 'wb');

        return $result['result'];
    }

    /**
     * Persist Uzum order
     */
    protected function persistUzumOrder(MarketplaceAccount $account, array $orderData): string
    {
        $externalOrderId = $orderData['external_order_id'] ?? null;

        if (! $externalOrderId) {
            throw new \RuntimeException('Missing external_order_id');
        }

        $existingOrder = UzumOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $externalOrderId)
            ->first();

        $oldStatus = $existingOrder?->status;

        // Parse ordered_at date with millisecond support
        $orderedAt = null;
        if (! empty($orderData['ordered_at'])) {
            try {
                if (is_numeric($orderData['ordered_at'])) {
                    $ts = (string) $orderData['ordered_at'];
                    if (strlen($ts) > 13) {
                        $ts = substr($ts, 0, 13);
                    }
                    $num = (int) $ts;
                    $orderedAt = $num > 1e12
                        ? Carbon::createFromTimestampMs($num)
                        : Carbon::createFromTimestamp($num);
                } else {
                    $orderedAt = Carbon::parse($orderData['ordered_at']);
                }
            } catch (\Exception $e) {
                $orderedAt = now();
            }
        }

        // Extract delivery_type/scheme from raw_payload or wb_delivery_type (uzum mapOrderData sets it there)
        $rawPayload = $orderData['raw_payload'] ?? $orderData;
        $deliveryType = $orderData['wb_delivery_type']
            ?? $rawPayload['scheme']
            ?? $rawPayload['deliveryScheme']
            ?? 'FBS';

        $orderPayload = [
            'marketplace_account_id' => $account->id,
            'external_order_id' => $externalOrderId,
            'status' => $orderData['status'] ?? 'new',
            'status_normalized' => $orderData['status_normalized'] ?? ($orderData['status'] ?? 'new'),
            'uzum_status' => $orderData['uzum_status'] ?? null,
            'delivery_type' => strtoupper($deliveryType), // FBS, DBS, EDBS
            'customer_name' => $orderData['customer_name'] ?? null,
            'customer_phone' => $orderData['customer_phone'] ?? null,
            'total_amount' => $orderData['total_amount'] ?? 0,
            'currency' => $orderData['currency'] ?? 'UZS',
            'ordered_at' => $orderedAt,
            'raw_payload' => $rawPayload,
        ];

        $result = DB::transaction(function () use ($existingOrder, $orderPayload, $orderData) {
            if ($existingOrder) {
                // Проверяем были ли реальные изменения
                $hasChanges = false;
                foreach ($orderPayload as $key => $value) {
                    if ($value != $existingOrder->$key) {
                        $hasChanges = true;
                        break;
                    }
                }

                $existingOrder->update($orderPayload);

                // Даже если данные не изменились, обновляем updated_at чтобы показать что синхронизация прошла
                if (! $hasChanges) {
                    $existingOrder->touch();
                }

                $order = $existingOrder;
                $result = 'updated';
            } else {
                $order = UzumOrder::create($orderPayload);
                $result = 'created';
            }

            // Sync order items
            $this->syncUzumOrderItems($order, $orderData['items'] ?? []);

            return ['result' => $result, 'order' => $order];
        });

        // Обрабатываем изменение статуса для остатков
        $order = $result['order'];
        $newStatus = $order->status;
        $isCreated = $result['result'] === 'created';

        if ($isCreated || $oldStatus !== $newStatus) {
            $this->processOrderStockChange($account, $order, $oldStatus, $newStatus, 'uzum');
        }

        // Дополнительная проверка: если заказ отменён, но резерв всё ещё активен - освободить
        // Это нужно для случаев, когда отмена была пропущена при предыдущих синхронизациях
        $this->ensureCancelledOrderStockReleased($account, $order, 'uzum');

        return $result['result'];
    }

    /**
     * Sync WB order items
     */
    protected function syncWbOrderItems(WbOrder $order, array $items): void
    {
        // Delete existing items for this order (simple approach)
        $order->items()->delete();

        foreach ($items as $itemData) {
            WbOrderItem::create([
                'wb_order_id' => $order->id,
                'external_offer_id' => $itemData['external_offer_id'] ?? null,
                'name' => $itemData['name'] ?? null,
                'quantity' => $itemData['quantity'] ?? 1,
                'price' => $itemData['price'] ?? 0,
                'total_price' => $itemData['total_price'] ?? ($itemData['price'] ?? 0) * ($itemData['quantity'] ?? 1),
                'raw_payload' => $itemData['raw_payload'] ?? $itemData,
            ]);
        }
    }

    /**
     * Sync Uzum order items
     */
    protected function syncUzumOrderItems(UzumOrder $order, array $items): void
    {
        // Delete existing items for this order (simple approach)
        $order->items()->delete();

        foreach ($items as $itemData) {
            UzumOrderItem::create([
                'uzum_order_id' => $order->id,
                'external_offer_id' => $itemData['external_offer_id'] ?? null,
                'name' => $itemData['name'] ?? null,
                'quantity' => $itemData['quantity'] ?? 1,
                'price' => $itemData['price'] ?? 0,
                'total_price' => $itemData['total_price'] ?? ($itemData['price'] ?? 0) * ($itemData['quantity'] ?? 1),
                'raw_payload' => $itemData['raw_payload'] ?? $itemData,
            ]);
        }
    }

    /**
     * Убедиться, что резерв обработан для заказов в финальном статусе.
     *
     * Обрабатывает случаи, когда заказ отменён или продан, но stock_status
     * всё ещё 'reserved' (из-за ошибки или пропуска при предыдущих синхронизациях).
     */
    protected function ensureCancelledOrderStockReleased(
        MarketplaceAccount $account,
        $order,
        string $marketplace
    ): void {
        // Проверяем, есть ли активный резерв
        $currentStockStatus = $order->stock_status ?? 'none';

        if ($currentStockStatus !== 'reserved') {
            return; // Уже освобождён, продан, или не было резерва
        }

        // Проверяем, является ли статус финальным (отмена или продажа)
        $cancelledStatuses = OrderStockService::CANCELLED_STATUSES[$marketplace] ?? [];
        $soldStatuses = OrderStockService::SOLD_STATUSES[$marketplace] ?? [];

        $isCancelled = in_array($order->status, $cancelledStatuses, true);
        $isSold = in_array($order->status, $soldStatuses, true);

        if (! $isCancelled && ! $isSold) {
            return;
        }

        Log::info('OrdersSyncService: Found order with stuck reservation, processing', [
            'marketplace' => $marketplace,
            'order_id' => $order->id,
            'external_order_id' => $order->external_order_id,
            'order_status' => $order->status,
            'stock_status' => $currentStockStatus,
            'action' => $isCancelled ? 'release' : 'sold',
        ]);

        try {
            $items = $this->orderStockService->getOrderItems($order, $marketplace);

            $result = $this->orderStockService->processOrderStatusChange(
                $account,
                $order,
                null, // Передаём null как oldStatus, чтобы логика сработала
                $order->status,
                $items
            );

            Log::info('OrdersSyncService: Stuck reservation processed', [
                'order_id' => $order->id,
                'result' => $result,
            ]);
        } catch (\Throwable $e) {
            Log::error('OrdersSyncService: Failed to process stuck reservation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
