<?php

namespace App\Services\Stock;

use App\Models\MarketplaceAccount;
use App\Models\OrderStockReturn;
use App\Models\ProductVariant;
use App\Models\VariantMarketplaceLink;
use App\Models\Warehouse\Sku as WarehouseSku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use App\Models\Warehouse\Warehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки остатков при заказах с маркетплейсов
 *
 * Логика:
 * - NEW/IN_ASSEMBLY → резервирование (decrementStock)
 * - IN_DELIVERY → перевод резерва в продажу (только статус)
 * - CANCELLED (до отправки) → отмена резерва (incrementStock)
 * - RETURNED → создание записи для ручной обработки
 */
class OrderStockService
{
    /**
     * Статусы для резервирования товара
     */
    public const RESERVE_STATUSES = [
        'wb' => ['new', 'confirm', 'assembly', 'in_assembly'],
        'uzum' => ['new', 'in_assembly', 'CREATED', 'PACKING'],
        'ozon' => ['awaiting_packaging', 'awaiting_deliver', 'acceptance_in_progress'],
        'ym' => ['PROCESSING', 'RESERVED'],
    ];

    /**
     * Статусы продажи (товар отправлен)
     */
    public const SOLD_STATUSES = [
        'wb' => ['in_delivery', 'delivered', 'completed', 'complete'],
        'uzum' => ['in_supply', 'accepted_uzum', 'waiting_pickup', 'issued', 'PENDING_DELIVERY', 'DELIVERING', 'ACCEPTED_AT_DP', 'DELIVERED_TO_CUSTOMER_DELIVERY_POINT', 'DELIVERED', 'COMPLETED'],
        'ozon' => ['delivering', 'delivered', 'driver_pickup'],
        'ym' => ['DELIVERY', 'PICKUP', 'DELIVERED'],
    ];

    /**
     * Статусы отмены
     */
    public const CANCELLED_STATUSES = [
        'wb' => ['cancelled', 'canceled', 'cancel'],
        'uzum' => ['cancelled', 'CANCELLED', 'CANCELED', 'PENDING_CANCELLATION'],
        'ozon' => ['cancelled', 'canceled'],
        'ym' => ['CANCELLED'],
    ];

    /**
     * Статусы возврата
     */
    public const RETURNED_STATUSES = [
        'wb' => ['returned', 'return'],
        'uzum' => ['returns', 'RETURNED'],
        'ozon' => ['returned'],
        'ym' => ['RETURNED'],
    ];

    /**
     * Обработать изменение статуса заказа
     *
     * @param MarketplaceAccount $account
     * @param Model $order Модель заказа (WbOrder, UzumOrder, OzonOrder)
     * @param string|null $oldStatus Предыдущий статус
     * @param string $newStatus Новый статус
     * @param array $items Позиции заказа
     * @return array Результат обработки
     */
    public function processOrderStatusChange(
        MarketplaceAccount $account,
        Model $order,
        ?string $oldStatus,
        string $newStatus,
        array $items
    ): array {
        $marketplace = strtolower($account->marketplace);
        $currentStockStatus = $order->stock_status ?? 'none';

        Log::info('OrderStockService: Processing order status change', [
            'account_id' => $account->id,
            'marketplace' => $marketplace,
            'order_id' => $order->id,
            'external_order_id' => $this->getExternalOrderId($order),
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'current_stock_status' => $currentStockStatus,
        ]);

        // Определяем какое действие нужно выполнить
        $isReserveStatus = $this->isReserveStatus($marketplace, $newStatus);
        $isSoldStatus = $this->isSoldStatus($marketplace, $newStatus);
        $isCancelledStatus = $this->isCancelledStatus($marketplace, $newStatus);
        $isReturnedStatus = $this->isReturnedStatus($marketplace, $newStatus);

        // 1. Новый заказ или переход в статус резерва
        if ($isReserveStatus && $currentStockStatus === 'none') {
            return $this->reserveStock($account, $order, $items, $marketplace);
        }

        // 2. Переход в статус продажи (отправка)
        if ($isSoldStatus && $currentStockStatus === 'reserved') {
            return $this->convertReserveToSold($order);
        }

        // 3. Отмена заказа
        if ($isCancelledStatus) {
            // Если был резерв - отменяем его
            if ($currentStockStatus === 'reserved') {
                return $this->releaseReserve($account, $order, $items, $marketplace);
            }
            // Если уже продан - ничего не делаем (возврат вручную)
            if ($currentStockStatus === 'sold') {
                Log::info('OrderStockService: Order cancelled after sold, manual return needed', [
                    'order_id' => $order->id,
                ]);
                return ['success' => true, 'action' => 'none', 'message' => 'Order was already sold, manual return needed'];
            }
        }

        // 4. Возврат товара
        if ($isReturnedStatus && $currentStockStatus === 'sold') {
            return $this->createReturnRecord($account, $order, $marketplace);
        }

        return ['success' => true, 'action' => 'none', 'message' => 'No stock action needed'];
    }

    /**
     * Зарезервировать товар (новый заказ)
     */
    protected function reserveStock(
        MarketplaceAccount $account,
        Model $order,
        array $items,
        string $marketplace
    ): array {
        $results = [
            'success' => true,
            'action' => 'reserve',
            'items_processed' => 0,
            'items_failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                $quantity = $this->getItemQuantity($item);
                if ($quantity <= 0) {
                    continue;
                }

                $variant = $this->findVariantByOrderItem($account, $item, $marketplace);

                if (!$variant) {
                    Log::warning('OrderStockService: Variant not found for order item', [
                        'account_id' => $account->id,
                        'order_id' => $order->id,
                        'item' => $item,
                    ]);
                    $results['items_failed']++;
                    $results['errors'][] = 'Variant not found: ' . json_encode($item);
                    continue;
                }

                $stockBefore = $variant->stock_default;
                $variant->decrementStock($quantity);
                $stockAfter = $variant->fresh()->stock_default;

                // Create warehouse stock ledger entry
                $warehouseSku = $this->createWarehouseStockLedger(
                    $account,
                    $order,
                    $variant,
                    -$quantity, // Negative for outgoing
                    'marketplace_order_reserve',
                    $marketplace
                );

                // Create stock reservation
                if ($warehouseSku) {
                    $this->createStockReservation(
                        $account,
                        $order,
                        $warehouseSku,
                        $quantity,
                        $marketplace
                    );
                }

                // Логируем операцию
                $this->logStockOperation(
                    $account,
                    $order,
                    $marketplace,
                    $variant,
                    'reserve',
                    $quantity,
                    $stockBefore,
                    $stockAfter,
                    $item
                );

                $results['items_processed']++;

                Log::info('OrderStockService: Stock reserved', [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);
            }

            // Обновляем статус заказа
            $order->update([
                'stock_status' => 'reserved',
                'stock_reserved_at' => now(),
            ]);

            DB::commit();

            return $results;

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('OrderStockService: Failed to reserve stock', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'action' => 'reserve',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Перевести резерв в продажу
     */
    protected function convertReserveToSold(Model $order): array
    {
        // Update stock reservations to CONSUMED
        $this->consumeStockReservations($order);

        $order->update([
            'stock_status' => 'sold',
            'stock_sold_at' => now(),
        ]);

        Log::info('OrderStockService: Reserve converted to sold', [
            'order_id' => $order->id,
        ]);

        return [
            'success' => true,
            'action' => 'sold',
            'message' => 'Reserve converted to sold',
        ];
    }

    /**
     * Отменить резерв (при отмене заказа до отправки)
     */
    protected function releaseReserve(
        MarketplaceAccount $account,
        Model $order,
        array $items,
        string $marketplace
    ): array {
        $results = [
            'success' => true,
            'action' => 'release',
            'items_processed' => 0,
            'items_failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            foreach ($items as $item) {
                $quantity = $this->getItemQuantity($item);
                if ($quantity <= 0) {
                    continue;
                }

                $variant = $this->findVariantByOrderItem($account, $item, $marketplace);

                if (!$variant) {
                    $results['items_failed']++;
                    continue;
                }

                $stockBefore = $variant->stock_default;
                $variant->incrementStock($quantity);
                $stockAfter = $variant->fresh()->stock_default;

                // Create warehouse stock ledger entry (positive to return stock)
                $this->createWarehouseStockLedger(
                    $account,
                    $order,
                    $variant,
                    $quantity, // Positive for incoming
                    'marketplace_order_cancel',
                    $marketplace
                );

                // Cancel stock reservations
                $this->cancelStockReservations($order);

                // Логируем операцию
                $this->logStockOperation(
                    $account,
                    $order,
                    $marketplace,
                    $variant,
                    'release',
                    $quantity,
                    $stockBefore,
                    $stockAfter,
                    $item
                );

                $results['items_processed']++;

                Log::info('OrderStockService: Stock released', [
                    'variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);
            }

            // Обновляем статус заказа
            $order->update([
                'stock_status' => 'released',
                'stock_released_at' => now(),
            ]);

            DB::commit();

            return $results;

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('OrderStockService: Failed to release reserve', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'action' => 'release',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Создать запись для ручной обработки возврата
     */
    protected function createReturnRecord(
        MarketplaceAccount $account,
        Model $order,
        string $marketplace
    ): array {
        try {
            $return = OrderStockReturn::updateOrCreate(
                [
                    'order_type' => $marketplace,
                    'order_id' => $order->id,
                ],
                [
                    'company_id' => $account->company_id,
                    'marketplace_account_id' => $account->id,
                    'external_order_id' => $this->getExternalOrderId($order),
                    'status' => OrderStockReturn::STATUS_PENDING,
                    'returned_at' => now(),
                ]
            );

            $order->update([
                'stock_status' => 'returned',
            ]);

            Log::info('OrderStockService: Return record created for manual processing', [
                'order_id' => $order->id,
                'return_id' => $return->id,
                'marketplace' => $marketplace,
            ]);

            return [
                'success' => true,
                'action' => 'return_created',
                'return_id' => $return->id,
                'message' => 'Return record created for manual processing',
            ];

        } catch (\Throwable $e) {
            Log::error('OrderStockService: Failed to create return record', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'action' => 'return_created',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Найти внутренний товар по данным из заказа
     */
    protected function findVariantByOrderItem(
        MarketplaceAccount $account,
        array $item,
        string $marketplace
    ): ?ProductVariant {
        // Получаем идентификаторы из позиции заказа
        $skuId = $item['sku_id'] ?? $item['skuId'] ?? $item['external_offer_id'] ?? null;
        $offerId = $item['offer_id'] ?? $item['offerId'] ?? $item['article'] ?? $item['supplierArticle'] ?? null;
        $barcode = $item['barcode'] ?? null;
        $nmId = $item['nm_id'] ?? $item['nmId'] ?? null;

        Log::debug('OrderStockService: Looking for variant', [
            'account_id' => $account->id,
            'sku_id' => $skuId,
            'offer_id' => $offerId,
            'barcode' => $barcode,
            'nm_id' => $nmId,
        ]);

        // 1. Ищем по связи VariantMarketplaceLink
        $link = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('is_active', true)
            ->where(function ($query) use ($skuId, $offerId, $nmId) {
                // По external_sku_id
                if ($skuId) {
                    $query->orWhere('external_sku_id', $skuId);
                }
                // По external_offer_id
                if ($offerId) {
                    $query->orWhere('external_offer_id', $offerId);
                }
                // По nm_id для WB (может храниться в external_offer_id)
                if ($nmId) {
                    $query->orWhere('external_offer_id', (string)$nmId);
                    $query->orWhere('external_sku_id', (string)$nmId);
                }
            })
            ->first();

        if ($link && $link->variant) {
            Log::debug('OrderStockService: Found variant via VariantMarketplaceLink', [
                'link_id' => $link->id,
                'variant_id' => $link->variant->id,
            ]);
            return $link->variant;
        }

        // 2. Ищем через MarketplaceProduct
        if ($skuId || $nmId) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $account->id)
                ->where('is_active', true)
                ->whereHas('marketplaceProduct', function ($query) use ($skuId, $nmId, $offerId) {
                    if ($skuId) {
                        $query->orWhere('external_product_id', $skuId);
                    }
                    if ($nmId) {
                        $query->orWhere('external_product_id', (string)$nmId);
                    }
                    if ($offerId) {
                        $query->orWhere('external_offer_id', $offerId);
                    }
                })
                ->first();

            if ($link && $link->variant) {
                Log::debug('OrderStockService: Found variant via MarketplaceProduct', [
                    'link_id' => $link->id,
                    'variant_id' => $link->variant->id,
                ]);
                return $link->variant;
            }
        }

        // 3. Fallback: ищем по barcode в ProductVariant
        if ($barcode) {
            $variant = ProductVariant::where('barcode', $barcode)
                ->where('company_id', $account->company_id)
                ->first();

            if ($variant) {
                Log::debug('OrderStockService: Found variant via barcode', [
                    'barcode' => $barcode,
                    'variant_id' => $variant->id,
                ]);
                return $variant;
            }
        }

        // 4. Fallback: ищем по артикулу/sku
        if ($offerId) {
            $variant = ProductVariant::where('sku', $offerId)
                ->where('company_id', $account->company_id)
                ->first();

            if ($variant) {
                Log::debug('OrderStockService: Found variant via SKU', [
                    'sku' => $offerId,
                    'variant_id' => $variant->id,
                ]);
                return $variant;
            }
        }

        Log::warning('OrderStockService: Variant not found', [
            'account_id' => $account->id,
            'sku_id' => $skuId,
            'offer_id' => $offerId,
            'barcode' => $barcode,
        ]);

        return null;
    }

    /**
     * Create warehouse stock ledger entry for marketplace order
     * 
     * @param MarketplaceAccount $account
     * @param Model $order
     * @param ProductVariant $variant
     * @param int $qtyDelta Quantity change (negative for outgoing, positive for incoming)
     * @param string $sourceType Type of operation (e.g., 'marketplace_order_reserve')
     * @param string $marketplace Marketplace code
     * @return WarehouseSku|null Returns warehouse SKU if successful
     */
    protected function createWarehouseStockLedger(
        MarketplaceAccount $account,
        Model $order,
        ProductVariant $variant,
        int $qtyDelta,
        string $sourceType,
        string $marketplace
    ): ?WarehouseSku {
        try {
            // Find or create warehouse SKU for this variant
            $warehouseSku = WarehouseSku::firstOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'company_id' => $account->company_id,
                ],
                [
                    'product_id' => $variant->product_id,
                    'sku_code' => $variant->sku,
                    'barcode_ean13' => $variant->barcode,
                    'is_active' => true,
                ]
            );

            // Determine warehouse to use
            $warehouseId = $this->determineWarehouse($account);

            if (!$warehouseId) {
                Log::warning('OrderStockService: No warehouse found for stock ledger entry', [
                    'account_id' => $account->id,
                    'variant_id' => $variant->id,
                ]);
                return null;
            }

            // Create stock ledger entry
            StockLedger::create([
                'company_id' => $account->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouseId,
                'location_id' => null,
                'sku_id' => $warehouseSku->id,
                'qty_delta' => $qtyDelta,
                'cost_delta' => 0, // We don't track cost here
                'currency_code' => 'UZS',
                'document_id' => null,
                'document_line_id' => null,
                'source_type' => $sourceType,
                'source_id' => $order->id,
                'created_by' => null,
            ]);

            Log::info('OrderStockService: Warehouse stock ledger entry created', [
                'variant_id' => $variant->id,
                'warehouse_sku_id' => $warehouseSku->id,
                'warehouse_id' => $warehouseId,
                'qty_delta' => $qtyDelta,
                'source_type' => $sourceType,
            ]);

            return $warehouseSku;

        } catch (\Throwable $e) {
            // Log error but don't fail the entire operation
            Log::error('OrderStockService: Failed to create warehouse stock ledger', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    /**
     * Determine which warehouse to use for marketplace orders
     * 
     * @param MarketplaceAccount $account
     * @return int|null Warehouse ID or null if not found
     */
    protected function determineWarehouse(MarketplaceAccount $account): ?int
    {
        // Priority 1: Check if account has default warehouse configured
        // (This would require adding warehouse_id to marketplace_accounts table)
        // if ($account->warehouse_id) {
        //     return $account->warehouse_id;
        // }

        // Priority 2: Get company's first active warehouse
        $warehouse = Warehouse::where('company_id', $account->company_id)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($warehouse) {
            return $warehouse->id;
        }

        // Priority 3: Create default warehouse if none exists
        try {
            $warehouse = Warehouse::create([
                'company_id' => $account->company_id,
                'name' => 'Склад по умолчанию',
                'code' => 'DEFAULT',
                'is_active' => true,
            ]);

            Log::info('OrderStockService: Created default warehouse', [
                'company_id' => $account->company_id,
                'warehouse_id' => $warehouse->id,
            ]);

            return $warehouse->id;
        } catch (\Throwable $e) {
            Log::error('OrderStockService: Failed to create default warehouse', [
                'company_id' => $account->company_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Create stock reservation for marketplace order
     * 
     * @param MarketplaceAccount $account
     * @param Model $order
     * @param WarehouseSku $warehouseSku
     * @param int $quantity
     * @param string $marketplace
     * @return void
     */
    protected function createStockReservation(
        MarketplaceAccount $account,
        Model $order,
        WarehouseSku $warehouseSku,
        int $quantity,
        string $marketplace
    ): void {
        try {
            $warehouseId = $this->determineWarehouse($account);

            if (!$warehouseId) {
                return;
            }

            StockReservation::create([
                'company_id' => $account->company_id,
                'warehouse_id' => $warehouseId,
                'sku_id' => $warehouseSku->id,
                'qty' => $quantity,
                'status' => StockReservation::STATUS_ACTIVE,
                'reason' => "Marketplace order: {$marketplace}",
                'source_type' => 'marketplace_order',
                'source_id' => $order->id,
                'expires_at' => now()->addDays(7), // 7 days expiration
                'created_by' => null,
            ]);

            Log::info('OrderStockService: Stock reservation created', [
                'order_id' => $order->id,
                'sku_id' => $warehouseSku->id,
                'quantity' => $quantity,
            ]);

        } catch (\Throwable $e) {
            Log::error('OrderStockService: Failed to create stock reservation', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Consume (mark as used) stock reservations for an order
     * 
     * @param Model $order
     * @return void
     */
    protected function consumeStockReservations(Model $order): void
    {
        try {
            $updated = StockReservation::where('source_type', 'marketplace_order')
                ->where('source_id', $order->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->update(['status' => StockReservation::STATUS_CONSUMED]);

            Log::info('OrderStockService: Stock reservations consumed', [
                'order_id' => $order->id,
                'count' => $updated,
            ]);

        } catch (\Throwable $e) {
            Log::error('OrderStockService: Failed to consume stock reservations', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Cancel stock reservations for an order
     * 
     * @param Model $order
     * @return void
     */
    protected function cancelStockReservations(Model $order): void
    {
        try {
            $updated = StockReservation::where('source_type', 'marketplace_order')
                ->where('source_id', $order->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->update(['status' => StockReservation::STATUS_CANCELLED]);

            Log::info('OrderStockService: Stock reservations cancelled', [
                'order_id' => $order->id,
                'count' => $updated,
            ]);

        } catch (\Throwable $e) {
            Log::error('OrderStockService: Failed to cancel stock reservations', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Логировать операцию с остатками
     */
    protected function logStockOperation(
        MarketplaceAccount $account,
        Model $order,
        string $marketplace,
        ProductVariant $variant,
        string $action,
        int $quantity,
        ?int $stockBefore,
        ?int $stockAfter,
        array $item
    ): void {
        try {
            DB::table('order_stock_logs')->insert([
                'company_id' => $account->company_id,
                'marketplace_account_id' => $account->id,
                'order_type' => $marketplace,
                'order_id' => $order->id,
                'external_order_id' => $this->getExternalOrderId($order),
                'product_variant_id' => $variant->id,
                'external_sku' => $item['sku_id'] ?? $item['skuId'] ?? $item['external_offer_id'] ?? null,
                'barcode' => $item['barcode'] ?? $variant->barcode,
                'action' => $action,
                'quantity' => $quantity,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'success' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Throwable $e) {
            Log::warning('OrderStockService: Failed to log stock operation', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Получить количество из позиции заказа
     */
    protected function getItemQuantity(array $item): int
    {
        return (int)($item['quantity'] ?? $item['amount'] ?? $item['qty'] ?? 1);
    }

    /**
     * Получить внешний ID заказа
     */
    protected function getExternalOrderId(Model $order): string
    {
        return (string)($order->external_order_id ?? $order->order_id ?? $order->posting_number ?? $order->id);
    }

    /**
     * Проверить, является ли статус статусом резервирования
     */
    public function isReserveStatus(string $marketplace, string $status): bool
    {
        $statuses = self::RESERVE_STATUSES[$marketplace] ?? [];
        return in_array(strtolower($status), array_map('strtolower', $statuses))
            || in_array($status, $statuses);
    }

    /**
     * Проверить, является ли статус статусом продажи
     */
    public function isSoldStatus(string $marketplace, string $status): bool
    {
        $statuses = self::SOLD_STATUSES[$marketplace] ?? [];
        return in_array(strtolower($status), array_map('strtolower', $statuses))
            || in_array($status, $statuses);
    }

    /**
     * Проверить, является ли статус статусом отмены
     */
    public function isCancelledStatus(string $marketplace, string $status): bool
    {
        $statuses = self::CANCELLED_STATUSES[$marketplace] ?? [];
        return in_array(strtolower($status), array_map('strtolower', $statuses))
            || in_array($status, $statuses);
    }

    /**
     * Проверить, является ли статус статусом возврата
     */
    public function isReturnedStatus(string $marketplace, string $status): bool
    {
        $statuses = self::RETURNED_STATUSES[$marketplace] ?? [];
        return in_array(strtolower($status), array_map('strtolower', $statuses))
            || in_array($status, $statuses);
    }

    /**
     * Получить позиции заказа в унифицированном формате
     */
    public function getOrderItems(Model $order, string $marketplace): array
    {
        // Для WB, Uzum - есть связь items()
        if (method_exists($order, 'items')) {
            $items = $order->items;
            if ($items && $items->isNotEmpty()) {
                return $items->map(function ($item) {
                    return [
                        'sku_id' => $item->external_offer_id ?? null,
                        'barcode' => $item->raw_payload['barcode'] ?? null,
                        'quantity' => $item->quantity ?? 1,
                        'name' => $item->name ?? null,
                        'raw_payload' => $item->raw_payload ?? [],
                    ];
                })->toArray();
            }
        }

        // Для Ozon - данные в order_data JSON
        if ($marketplace === 'ozon' && !empty($order->order_data)) {
            $orderData = is_array($order->order_data) ? $order->order_data : json_decode($order->order_data, true);
            return $orderData['products'] ?? [];
        }

        // Fallback: пробуем достать из raw_payload
        $rawPayload = $order->raw_payload ?? [];
        if (is_string($rawPayload)) {
            $rawPayload = json_decode($rawPayload, true) ?? [];
        }

        // WB specific
        if ($marketplace === 'wb') {
            if (isset($rawPayload['nmId'])) {
                return [[
                    'nm_id' => $rawPayload['nmId'],
                    'sku_id' => $rawPayload['chrtId'] ?? null,
                    'barcode' => $rawPayload['barcode'] ?? null,
                    'offer_id' => $rawPayload['supplierArticle'] ?? null,
                    'quantity' => 1,
                ]];
            }
        }

        // Uzum specific
        if ($marketplace === 'uzum') {
            $items = $rawPayload['orderItems'] ?? [];
            return array_map(function ($item) {
                return [
                    'sku_id' => $item['skuId'] ?? null,
                    'barcode' => $item['barcode'] ?? null,
                    'quantity' => $item['amount'] ?? 1,
                    'name' => $item['skuTitle'] ?? $item['title'] ?? null,
                ];
            }, $items);
        }

        // YandexMarket specific - items are in order_data
        if ($marketplace === 'ym') {
            $orderData = $order->order_data ?? [];
            if (is_string($orderData)) {
                $orderData = json_decode($orderData, true) ?? [];
            }
            $items = $orderData['items'] ?? [];
            return array_map(function ($item) {
                return [
                    'sku_id' => $item['shopSku'] ?? $item['offerId'] ?? null,
                    'offer_id' => $item['offerId'] ?? $item['shopSku'] ?? null,
                    'barcode' => $item['barcode'] ?? null,
                    'quantity' => $item['count'] ?? 1,
                    'name' => $item['offerName'] ?? null,
                ];
            }, $items);
        }

        return [];
    }
}
