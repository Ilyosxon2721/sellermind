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
     * @param  Model  $order  Модель заказа (WbOrder, UzumOrder, OzonOrder)
     * @param  string|null  $oldStatus  Предыдущий статус
     * @param  string  $newStatus  Новый статус
     * @param  array  $items  Позиции заказа
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

        // 2.5. Historical order already sold but never processed - just mark as sold
        // This handles orders that were delivered before stock tracking was enabled
        // Also handles orders with 'skipped' status (items not linked at reservation time)
        // IMPORTANT: Also consume any active reservations that might exist
        if ($isSoldStatus && in_array($currentStockStatus, ['none', 'skipped'])) {
            // Check if there are active reservations for this order and consume them
            $this->consumeStockReservations($order);

            $order->update([
                'stock_status' => 'sold',
                'stock_sold_at' => now(),
            ]);
            Log::info('OrderStockService: Order marked as sold (reservations consumed if any)', [
                'order_id' => $order->id,
                'previous_stock_status' => $currentStockStatus,
            ]);

            return ['success' => true, 'action' => 'sold_historical', 'message' => 'Order marked as sold'];
        }

        // 3. Отмена заказа
        if ($isCancelledStatus) {
            // Если был резерв - отменяем резерв и возвращаем остаток
            if ($currentStockStatus === 'reserved') {
                return $this->releaseReserve($account, $order, $items, $marketplace);
            }
            // Если резерв НЕ был создан (none/skipped) — просто помечаем как released
            // БЕЗ движения остатков, т.к. списания не было
            if (in_array($currentStockStatus, ['none', 'skipped'])) {
                $order->update([
                    'stock_status' => 'released',
                    'stock_released_at' => now(),
                ]);
                Log::info('OrderStockService: Cancelled order without reservation, no stock adjustment needed', [
                    'order_id' => $order->id,
                    'previous_stock_status' => $currentStockStatus,
                ]);

                return ['success' => true, 'action' => 'released_no_stock', 'message' => 'Order cancelled, no stock was reserved'];
            }
            // Если уже продан - ничего не делаем (возврат вручную)
            if ($currentStockStatus === 'sold') {
                Log::info('OrderStockService: Order cancelled after sold, manual return needed', [
                    'order_id' => $order->id,
                ]);

                return ['success' => true, 'action' => 'none', 'message' => 'Order was already sold, manual return needed'];
            }
            // Если уже освобождён - ничего не делаем
            if ($currentStockStatus === 'released') {
                Log::info('OrderStockService: Order already released', [
                    'order_id' => $order->id,
                ]);

                return ['success' => true, 'action' => 'none', 'message' => 'Stock already released'];
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
     * ВАЖНО: При резервировании СРАЗУ списываем со склада (ledger entry) чтобы:
     * 1. Остатки на маркетплейсах обновились и не было overselling
     * 2. Создаём stock_reservation для отслеживания (чтобы при отмене вернуть товар)
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
            // Перечитываем заказ с блокировкой строки для защиты от гонки
            // (несколько cron-задач могут обрабатывать один заказ одновременно)
            $freshOrder = $order->newQuery()->lockForUpdate()->find($order->id);
            if (! $freshOrder || ($freshOrder->stock_status ?? 'none') !== 'none') {
                DB::rollBack();
                Log::info('OrderStockService: Order already processed by another process, skipping', [
                    'order_id' => $order->id,
                    'stock_status' => $freshOrder->stock_status ?? 'unknown',
                ]);

                return ['success' => true, 'action' => 'none', 'message' => 'Already processed by another process'];
            }
            // Используем свежую модель далее
            $order = $freshOrder;

            foreach ($items as $item) {
                $quantity = $this->getItemQuantity($item);
                if ($quantity <= 0) {
                    continue;
                }

                $variant = $this->findVariantByOrderItem($account, $item, $marketplace);

                if (! $variant) {
                    Log::warning('OrderStockService: Variant not found for order item', [
                        'account_id' => $account->id,
                        'order_id' => $order->id,
                        'item' => $item,
                    ]);
                    $results['items_failed']++;
                    $results['errors'][] = 'Variant not found: '.json_encode($item);

                    continue;
                }

                $stockBefore = $variant->stock_default;

                // Decrease stock in ProductVariant (quietly to avoid Observer creating duplicate ledger entry)
                // Observer would create stock_adjustment ledger entry, but we create our own below
                $variant->decrementStockQuietly($quantity);
                $stockAfter = $variant->stock_default;

                // Create warehouse stock ledger entry (actual deduction)
                $warehouseSku = $this->createWarehouseStockLedger(
                    $account,
                    $order,
                    $variant,
                    -$quantity, // Negative for stock out
                    'marketplace_order_reserve',
                    $marketplace
                );

                // Create stock reservation for tracking (to return stock if cancelled)
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

                Log::info('OrderStockService: Stock reserved and deducted', [
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);

                // ВАЖНО: Синхронизируем остатки с ДРУГИМИ маркетплейсами
                // чтобы избежать overselling
                $this->syncVariantToOtherMarketplaces($variant, $account->id);
            }

            // Обновляем статус заказа ТОЛЬКО если хотя бы один item был успешно обработан
            // Если все items не найдены (не привязаны) - НЕ помечаем заказ как reserved
            if ($results['items_processed'] > 0) {
                $order->update([
                    'stock_status' => 'reserved',
                    'stock_reserved_at' => now(),
                ]);
            } else {
                // Все items не привязаны - помечаем заказ как skipped
                $order->update([
                    'stock_status' => 'skipped',
                ]);
                Log::info('OrderStockService: No items could be reserved (all items not linked)', [
                    'order_id' => $order->id,
                ]);
            }

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
     * ВАЖНО: Остаток уже был списан при резервировании!
     * Здесь только обновляем статус резерва в CONSUMED.
     */
    protected function convertReserveToSold(Model $order): array
    {
        // Stock was already deducted on reserve, just update reservation status
        $this->consumeStockReservations($order);

        $order->update([
            'stock_status' => 'sold',
            'stock_sold_at' => now(),
        ]);

        Log::info('OrderStockService: Reserve converted to sold (stock was already deducted)', [
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
     * ВАЖНО: Поскольку при резервировании мы СОЗДАЛИ ledger entry (списание),
     * при отмене нужно ВЕРНУТЬ товар обратно (положительный ledger entry).
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
            'reservations_released' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            // Перечитываем заказ с блокировкой для защиты от гонки
            $freshOrder = $order->newQuery()->lockForUpdate()->find($order->id);
            if (! $freshOrder || $freshOrder->stock_status === 'released') {
                DB::rollBack();
                Log::info('OrderStockService: Order already released by another process, skipping', [
                    'order_id' => $order->id,
                    'stock_status' => $freshOrder->stock_status ?? 'unknown',
                ]);

                return ['success' => true, 'action' => 'none', 'message' => 'Already released by another process'];
            }
            $order = $freshOrder;

            // Сначала проверяем есть ли активные резервы в базе
            $activeReservations = StockReservation::where('source_type', 'marketplace_order')
                ->where('source_id', $order->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->with('sku.productVariant')
                ->get();

            // Если есть активные резервы - освобождаем их напрямую
            if ($activeReservations->isNotEmpty()) {
                Log::info('OrderStockService: Found active reservations, releasing directly', [
                    'order_id' => $order->id,
                    'reservations_count' => $activeReservations->count(),
                ]);

                foreach ($activeReservations as $reservation) {
                    $variant = $reservation->sku?->productVariant;
                    $qty = $reservation->qty;

                    // Отменяем резерв
                    $reservation->update(['status' => StockReservation::STATUS_CANCELLED]);
                    $results['reservations_released']++;

                    if ($variant) {
                        $stockBefore = $variant->stock_default;

                        // Возвращаем остатки (quietly to avoid Observer creating duplicate ledger entry)
                        $variant->incrementStockQuietly($qty);
                        $stockAfter = $variant->stock_default;

                        // Создаём запись в журнале
                        StockLedger::create([
                            'company_id' => $account->company_id,
                            'occurred_at' => now(),
                            'warehouse_id' => $reservation->warehouse_id,
                            'sku_id' => $reservation->sku_id,
                            'qty_delta' => $qty,
                            'cost_delta' => 0,
                            'currency_code' => 'UZS',
                            'source_type' => 'marketplace_order_cancel',
                            'source_id' => $order->id,
                        ]);

                        Log::info('OrderStockService: Stock returned from reservation', [
                            'variant_id' => $variant->id,
                            'quantity' => $qty,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                        ]);

                        // Синхронизируем с другими маркетплейсами
                        $this->syncVariantToOtherMarketplaces($variant, $account->id);

                        $results['items_processed']++;
                    }
                }
            } else {
                // Если резервов нет - пробуем через items заказа
                foreach ($items as $item) {
                    $quantity = $this->getItemQuantity($item);
                    if ($quantity <= 0) {
                        continue;
                    }

                    $variant = $this->findVariantByOrderItem($account, $item, $marketplace);

                    if (! $variant) {
                        $results['items_failed']++;

                        continue;
                    }

                    $stockBefore = $variant->stock_default;

                    // Return stock to ProductVariant (quietly to avoid Observer creating duplicate ledger entry)
                    $variant->incrementStockQuietly($quantity);
                    $stockAfter = $variant->stock_default;

                    // Create warehouse stock ledger entry (positive to return stock)
                    $this->createWarehouseStockLedger(
                        $account,
                        $order,
                        $variant,
                        $quantity, // Positive for stock return
                        'marketplace_order_cancel',
                        $marketplace
                    );

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

                    Log::info('OrderStockService: Stock returned on cancel', [
                        'variant_id' => $variant->id,
                        'quantity' => $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);

                    // ВАЖНО: Синхронизируем остатки с ДРУГИМИ маркетплейсами
                    // чтобы они знали что товар снова доступен
                    $this->syncVariantToOtherMarketplaces($variant, $account->id);
                }

                // Cancel any remaining stock reservations
                $this->cancelStockReservations($order);
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
        $chrtId = $item['chrt_id'] ?? $item['chrtId'] ?? null; // WB characteristic ID (size/color)

        Log::debug('OrderStockService: Looking for variant', [
            'account_id' => $account->id,
            'marketplace' => $marketplace,
            'sku_id' => $skuId,
            'offer_id' => $offerId,
            'barcode' => $barcode,
            'nm_id' => $nmId,
            'chrt_id' => $chrtId,
        ]);

        // =====================================================
        // UZUM SPECIFIC: Используем ТОЛЬКО поиск по barcode через skuList
        // Это критически важно, т.к. Uzum API не возвращает skuId в FBS заказах
        // и другие методы поиска могут найти НЕПРАВИЛЬНЫЙ товар
        // =====================================================
        if ($marketplace === 'uzum') {
            // 1. Сначала пробуем точное совпадение по marketplace_barcode
            if ($barcode) {
                $link = VariantMarketplaceLink::query()
                    ->where('marketplace_account_id', $account->id)
                    ->where('marketplace_barcode', $barcode)
                    ->where('is_active', true)
                    ->first();

                if ($link && $link->variant) {
                    Log::debug('OrderStockService: [Uzum] Found variant via marketplace_barcode', [
                        'barcode' => $barcode,
                        'link_id' => $link->id,
                        'variant_id' => $link->variant->id,
                    ]);

                    return $link->variant;
                }
            }

            // 2. Поиск по barcode через skuList в MarketplaceProduct.raw_payload
            if ($barcode) {
                $variant = $this->findVariantByBarcodeInSkuList($account, $barcode);
                if ($variant) {
                    Log::debug('OrderStockService: [Uzum] Found variant via skuList barcode', [
                        'barcode' => $barcode,
                        'variant_id' => $variant->id,
                        'variant_sku' => $variant->sku,
                    ]);

                    return $variant;
                }
            }

            // Для Uzum НЕ используем другие методы поиска - они могут найти неправильный товар
            // Если barcode не найден в skuList, значит товар не привязан к нашей системе
            Log::info('OrderStockService: [Uzum] Variant not found, item not linked', [
                'barcode' => $barcode,
                'sku_id' => $skuId,
                'account_id' => $account->id,
            ]);

            return null;
        }

        // =====================================================
        // WB SPECIFIC: Приоритетный поиск по chrtId (идентификатор размера/цвета)
        // chrtId уникально идентифицирует конкретный вариант товара на WB
        // =====================================================
        if ($marketplace === 'wb' && $chrtId) {
            // 1. Ищем по chrtId в external_sku_id
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $account->id)
                ->where('external_sku_id', (string) $chrtId)
                ->where('is_active', true)
                ->first();

            if ($link && $link->variant) {
                Log::debug('OrderStockService: [WB] Found variant via chrtId', [
                    'chrt_id' => $chrtId,
                    'link_id' => $link->id,
                    'variant_id' => $link->variant->id,
                ]);

                return $link->variant;
            }
        }

        // =====================================================
        // Для других маркетплейсов (WB, Ozon, YM) - стандартная логика
        // =====================================================

        // 1. Ищем по marketplace_barcode (приоритетный поиск по баркоду маркетплейса)
        if ($barcode) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $account->id)
                ->where('marketplace_barcode', $barcode)
                ->where('is_active', true)
                ->first();

            if ($link && $link->variant) {
                Log::debug('OrderStockService: Found variant via marketplace_barcode', [
                    'barcode' => $barcode,
                    'link_id' => $link->id,
                    'variant_id' => $link->variant->id,
                ]);

                return $link->variant;
            }
        }

        // 2. Ищем по ТОЧНОМУ external_sku_id (приоритет для маркетплейсов с несколькими SKU на продукт)
        if ($skuId) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $account->id)
                ->where('external_sku_id', $skuId)
                ->where('is_active', true)
                ->first();

            if ($link && $link->variant) {
                Log::debug('OrderStockService: Found variant via exact external_sku_id', [
                    'sku_id' => $skuId,
                    'link_id' => $link->id,
                    'variant_id' => $link->variant->id,
                ]);

                return $link->variant;
            }
        }

        // 3. Ищем по external_offer_id или nm_id (для WB)
        $link = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('is_active', true)
            ->where(function ($query) use ($skuId, $offerId, $nmId) {
                // По external_offer_id (может совпадать со skuId для некоторых МП)
                if ($skuId) {
                    $query->orWhere('external_offer_id', $skuId);
                }
                if ($offerId) {
                    $query->orWhere('external_offer_id', $offerId);
                }
                // По nm_id для WB (может храниться в external_offer_id)
                if ($nmId) {
                    $query->orWhere('external_offer_id', (string) $nmId);
                    $query->orWhere('external_sku_id', (string) $nmId);
                }
            })
            ->first();

        if ($link && $link->variant) {
            Log::debug('OrderStockService: Found variant via external_offer_id/nm_id', [
                'link_id' => $link->id,
                'variant_id' => $link->variant->id,
            ]);

            return $link->variant;
        }

        // 4. Ищем через MarketplaceProduct
        if ($skuId || $nmId) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $account->id)
                ->where('is_active', true)
                ->whereHas('marketplaceProduct', function ($query) use ($skuId, $nmId, $offerId) {
                    if ($skuId) {
                        $query->orWhere('external_product_id', $skuId);
                    }
                    if ($nmId) {
                        $query->orWhere('external_product_id', (string) $nmId);
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

        // 5. Fallback: ищем по barcode в ProductVariant (если баркод совпадает с внутренним)
        if ($barcode) {
            $variant = ProductVariant::where('barcode', $barcode)
                ->where('company_id', $account->company_id)
                ->first();

            if ($variant) {
                Log::debug('OrderStockService: Found variant via internal barcode', [
                    'barcode' => $barcode,
                    'variant_id' => $variant->id,
                ]);

                return $variant;
            }
        }

        // 6. Fallback: ищем по артикулу/sku
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
     * Найти вариант по barcode через skuList в MarketplaceProduct.raw_payload
     * Критично для Uzum, где API не возвращает skuId в FBS заказах
     */
    protected function findVariantByBarcodeInSkuList(
        MarketplaceAccount $account,
        string $barcode
    ): ?ProductVariant {
        // Ищем MarketplaceProduct где в skuList есть этот barcode
        $marketplaceProduct = \App\Models\MarketplaceProduct::query()
            ->where('marketplace_account_id', $account->id)
            ->whereNotNull('raw_payload')
            ->get()
            ->first(function ($product) use ($barcode) {
                $skuList = $product->raw_payload['skuList'] ?? [];
                foreach ($skuList as $sku) {
                    if (isset($sku['barcode']) && (string) $sku['barcode'] === (string) $barcode) {
                        return true;
                    }
                }

                return false;
            });

        if (! $marketplaceProduct) {
            Log::debug('OrderStockService: No MarketplaceProduct found with barcode in skuList', [
                'barcode' => $barcode,
                'account_id' => $account->id,
            ]);

            return null;
        }

        // Найти skuId для этого barcode
        $skuList = $marketplaceProduct->raw_payload['skuList'] ?? [];
        $matchedSkuId = null;
        foreach ($skuList as $sku) {
            if (isset($sku['barcode']) && (string) $sku['barcode'] === (string) $barcode) {
                $matchedSkuId = $sku['skuId'] ?? null;
                break;
            }
        }

        if (! $matchedSkuId) {
            Log::warning('OrderStockService: Found barcode but no skuId in skuList', [
                'barcode' => $barcode,
                'product_id' => $marketplaceProduct->id,
            ]);

            return null;
        }

        // Теперь ищем VariantMarketplaceLink по этому skuId
        $link = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $account->id)
            ->where('external_sku_id', (string) $matchedSkuId)
            ->where('is_active', true)
            ->first();

        if ($link && $link->variant) {
            Log::info('OrderStockService: Found variant via barcode->skuId lookup', [
                'barcode' => $barcode,
                'sku_id' => $matchedSkuId,
                'link_id' => $link->id,
                'variant_id' => $link->variant->id,
                'variant_sku' => $link->variant->sku,
            ]);

            return $link->variant;
        }

        // НЕ используем fallback - только точное совпадение по skuId
        // Если связь не найдена, значит товар не привязан к нашей системе
        Log::warning('OrderStockService: No VariantMarketplaceLink found for skuId', [
            'barcode' => $barcode,
            'sku_id' => $matchedSkuId,
            'product_id' => $marketplaceProduct->id,
            'account_id' => $account->id,
        ]);

        return null;
    }

    /**
     * Create warehouse stock ledger entry for marketplace order
     *
     * @param  int  $qtyDelta  Quantity change (negative for outgoing, positive for incoming)
     * @param  string  $sourceType  Type of operation (e.g., 'marketplace_order_reserve')
     * @param  string  $marketplace  Marketplace code
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

            if (! $warehouseId) {
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
     * Get or create warehouse SKU without creating ledger entry
     */
    protected function getOrCreateWarehouseSku(
        MarketplaceAccount $account,
        ProductVariant $variant
    ): ?WarehouseSku {
        try {
            return WarehouseSku::firstOrCreate(
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
        } catch (\Throwable $e) {
            Log::error('OrderStockService: Failed to get/create warehouse SKU', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Determine which warehouse to use for marketplace orders
     *
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

            if (! $warehouseId) {
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
        return (int) ($item['quantity'] ?? $item['amount'] ?? $item['qty'] ?? 1);
    }

    /**
     * Получить внешний ID заказа
     */
    protected function getExternalOrderId(Model $order): string
    {
        return (string) ($order->external_order_id ?? $order->order_id ?? $order->posting_number ?? $order->id);
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
                return $items->map(function ($item) use ($marketplace) {
                    $rawPayload = $item->raw_payload ?? [];

                    // WB specific: extract data from WB API structure
                    if ($marketplace === 'wb') {
                        // WB stores barcode in skus array, not in barcode field
                        $skus = $rawPayload['skus'] ?? [];
                        $barcode = ! empty($skus) ? (string) $skus[0] : null;

                        // chrtId is the characteristic ID (identifies size/color)
                        // This is the key identifier for matching variants
                        $chrtId = $rawPayload['chrtId'] ?? null;
                        $nmId = $rawPayload['nmId'] ?? null;
                        $article = $rawPayload['article'] ?? $rawPayload['supplierArticle'] ?? null;

                        return [
                            'sku_id' => $chrtId, // Use chrtId for variant matching
                            'nm_id' => $nmId,    // nmId identifies the product card
                            'chrt_id' => $chrtId, // Explicit chrtId field
                            'offer_id' => $article,
                            'barcode' => $barcode,
                            'quantity' => $item->quantity ?? 1,
                            'name' => $item->name ?? null,
                            'raw_payload' => $rawPayload,
                        ];
                    }

                    // Uzum specific: use sku_id accessor which gets skuId from raw_payload
                    // This is critical for matching correct variant by size/color
                    $skuId = $item->sku_id ?? $item->external_offer_id ?? null;

                    return [
                        'sku_id' => $skuId,
                        'barcode' => $rawPayload['barcode'] ?? null,
                        'quantity' => $item->quantity ?? 1,
                        'name' => $item->name ?? null,
                        'raw_payload' => $rawPayload,
                    ];
                })->toArray();
            }
        }

        // Для Ozon - данные в order_data JSON
        if ($marketplace === 'ozon' && ! empty($order->order_data)) {
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
                // WB stores barcode in skus array
                $skus = $rawPayload['skus'] ?? [];
                $barcode = ! empty($skus) ? (string) $skus[0] : ($rawPayload['barcode'] ?? null);

                return [[
                    'nm_id' => $rawPayload['nmId'],
                    'chrt_id' => $rawPayload['chrtId'] ?? null,
                    'sku_id' => $rawPayload['chrtId'] ?? null, // chrtId for variant matching
                    'barcode' => $barcode,
                    'offer_id' => $rawPayload['article'] ?? $rawPayload['supplierArticle'] ?? null,
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

    /**
     * Синхронизировать остатки варианта с ДРУГИМИ маркетплейсами
     * Исключаем маркетплейс, откуда пришёл заказ (sourceLinkId)
     *
     * @param  int|null  $sourceAccountId  ID аккаунта маркетплейса, откуда пришёл заказ (исключаем)
     */
    protected function syncVariantToOtherMarketplaces(ProductVariant $variant, ?int $sourceAccountId = null): void
    {
        try {
            // Получаем все активные связи с маркетплейсами для этого варианта
            $links = $variant->activeMarketplaceLinks()
                ->where('sync_stock_enabled', true)
                ->with('account')
                ->get();

            if ($links->isEmpty()) {
                Log::debug('OrderStockService: No active marketplace links for variant', [
                    'variant_id' => $variant->id,
                ]);

                return;
            }

            // Получаем StockSyncService
            $stockSyncService = app(StockSyncService::class);
            $currentStock = $variant->getCurrentStock();

            foreach ($links as $link) {
                // Пропускаем маркетплейс, откуда пришёл заказ
                if ($sourceAccountId && $link->marketplace_account_id === $sourceAccountId) {
                    Log::debug('OrderStockService: Skipping source marketplace for sync', [
                        'variant_id' => $variant->id,
                        'account_id' => $link->marketplace_account_id,
                    ]);

                    continue;
                }

                try {
                    $stockSyncService->syncLinkStock($link, $currentStock);

                    Log::info('OrderStockService: Stock synced to other marketplace', [
                        'variant_id' => $variant->id,
                        'link_id' => $link->id,
                        'marketplace' => $link->account->marketplace,
                        'stock' => $currentStock,
                    ]);
                } catch (\Exception $e) {
                    Log::warning('OrderStockService: Failed to sync stock to marketplace', [
                        'variant_id' => $variant->id,
                        'link_id' => $link->id,
                        'marketplace' => $link->account->marketplace ?? 'unknown',
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('OrderStockService: Error syncing variant to other marketplaces', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
