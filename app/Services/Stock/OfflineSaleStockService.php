<?php

declare(strict_types=1);

namespace App\Services\Stock;

use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\ProductVariant;
use App\Models\Warehouse\Sku as WarehouseSku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис обработки остатков при ручных продажах (OfflineSale)
 *
 * Логика:
 * - CONFIRMED → резервирование (ProductVariant decrement + StockReservation, БЕЗ ledger)
 * - DELIVERED → продажа: ledger -qty + reservation→CONSUMED
 * - CANCELLED → отмена: reservation→CANCELLED + ProductVariant increment
 */
final class OfflineSaleStockService
{
    /**
     * Обработать изменение статуса ручной продажи
     */
    public function processSaleStatusChange(
        OfflineSale $sale,
        ?string $oldStatus,
        string $newStatus
    ): array {
        $currentStockStatus = $sale->stock_status ?? 'none';

        Log::info('OfflineSaleStockService: Processing sale status change', [
            'sale_id' => $sale->id,
            'sale_number' => $sale->sale_number,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'current_stock_status' => $currentStockStatus,
        ]);

        // 1. Подтверждение продажи → резервирование
        if ($newStatus === OfflineSale::STATUS_CONFIRMED && $currentStockStatus === 'none') {
            return $this->reserveStock($sale);
        }

        // 2. Доставка → фактическая продажа
        if ($newStatus === OfflineSale::STATUS_DELIVERED && $currentStockStatus === 'reserved') {
            return $this->convertReserveToSold($sale);
        }

        // 2.5. Доставка без резерва (старые продажи или прямая доставка)
        if ($newStatus === OfflineSale::STATUS_DELIVERED && in_array($currentStockStatus, ['none', 'skipped'])) {
            // Просто списываем товар напрямую
            return $this->sellDirectly($sale);
        }

        // 3. Отмена продажи
        if ($newStatus === OfflineSale::STATUS_CANCELLED) {
            if ($currentStockStatus === 'reserved') {
                return $this->releaseReserve($sale);
            }
            // Если резерв НЕ был создан — ничего не делаем
            if (in_array($currentStockStatus, ['none', 'skipped'])) {
                $sale->update([
                    'stock_status' => 'released',
                    'stock_released_at' => now(),
                ]);

                return ['success' => true, 'action' => 'released_no_stock', 'message' => 'Sale cancelled, no stock was reserved'];
            }
            // Если уже продан — нужен ручной возврат
            if ($currentStockStatus === 'sold') {
                return ['success' => true, 'action' => 'none', 'message' => 'Sale was already delivered, manual return needed'];
            }
        }

        return ['success' => true, 'action' => 'none', 'message' => 'No stock action needed'];
    }

    /**
     * Зарезервировать товар при подтверждении продажи
     */
    protected function reserveStock(OfflineSale $sale): array
    {
        $results = [
            'success' => true,
            'action' => 'reserve',
            'items_processed' => 0,
            'items_failed' => 0,
            'errors' => [],
        ];

        DB::beginTransaction();

        try {
            // Блокировка строки для защиты от гонки
            $freshSale = OfflineSale::lockForUpdate()->find($sale->id);
            if (! $freshSale || ($freshSale->stock_status ?? 'none') !== 'none') {
                DB::rollBack();
                Log::info('OfflineSaleStockService: Sale already processed', [
                    'sale_id' => $sale->id,
                    'stock_status' => $freshSale->stock_status ?? 'unknown',
                ]);

                return ['success' => true, 'action' => 'none', 'message' => 'Already processed'];
            }
            $sale = $freshSale;

            $items = $sale->items()->with('sku')->get();

            foreach ($items as $item) {
                /** @var OfflineSaleItem $item */
                $quantity = $item->quantity;
                if ($quantity <= 0) {
                    continue;
                }

                // Получаем вариант товара
                $variant = $this->findVariantForItem($sale->company_id, $item);

                if (! $variant) {
                    Log::warning('OfflineSaleStockService: Variant not found for item', [
                        'sale_id' => $sale->id,
                        'item_id' => $item->id,
                        'sku_id' => $item->sku_id,
                    ]);
                    $results['items_failed']++;
                    $results['errors'][] = "Variant not found for item {$item->id}";

                    continue;
                }

                $stockBefore = $variant->stock_default;

                // Decrementируем остатки (quietly to avoid Observer creating duplicate ledger)
                $variant->decrementStockQuietly($quantity);
                $stockAfter = $variant->stock_default;

                // Получаем warehouse SKU для резерва
                $warehouseSku = $this->getOrCreateWarehouseSku($sale->company_id, $variant);

                // Создаём резерв
                if ($warehouseSku) {
                    $this->createStockReservation($sale, $warehouseSku, $quantity);
                }

                Log::info('OfflineSaleStockService: Stock reserved', [
                    'sale_id' => $sale->id,
                    'variant_id' => $variant->id,
                    'variant_sku' => $variant->sku,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);

                $results['items_processed']++;
            }

            // Обновляем статус продажи
            if ($results['items_processed'] > 0) {
                $sale->update([
                    'stock_status' => 'reserved',
                    'stock_reserved_at' => now(),
                ]);
            } else {
                $sale->update([
                    'stock_status' => 'skipped',
                ]);
                Log::info('OfflineSaleStockService: No items could be reserved', [
                    'sale_id' => $sale->id,
                ]);
            }

            DB::commit();

            return $results;

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('OfflineSaleStockService: Failed to reserve stock', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'action' => 'reserve',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Перевести резерв в продажу (создать ledger entries)
     */
    protected function convertReserveToSold(OfflineSale $sale): array
    {
        try {
            // Получаем активные резервы
            $reservations = StockReservation::where('source_type', 'offline_sale')
                ->where('source_id', $sale->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->get();

            // Создаём ledger entries для фактического списания
            foreach ($reservations as $reservation) {
                StockLedger::create([
                    'company_id' => $reservation->company_id,
                    'occurred_at' => now(),
                    'warehouse_id' => $reservation->warehouse_id,
                    'sku_id' => $reservation->sku_id,
                    'qty_delta' => -$reservation->qty,
                    'cost_delta' => 0,
                    'currency_code' => 'UZS',
                    'source_type' => 'offline_sale_sold',
                    'source_id' => $sale->id,
                ]);

                Log::info('OfflineSaleStockService: Ledger entry created on sold', [
                    'sale_id' => $sale->id,
                    'sku_id' => $reservation->sku_id,
                    'qty' => -$reservation->qty,
                ]);
            }

            // Переводим резервы в CONSUMED
            $this->consumeStockReservations($sale);

            $sale->update([
                'stock_status' => 'sold',
                'stock_sold_at' => now(),
            ]);

            Log::info('OfflineSaleStockService: Reserve converted to sold', [
                'sale_id' => $sale->id,
                'reservations_count' => $reservations->count(),
            ]);

            return [
                'success' => true,
                'action' => 'sold',
                'message' => 'Reserve converted to sold',
            ];

        } catch (\Throwable $e) {
            Log::error('OfflineSaleStockService: Failed to convert reserve to sold', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'action' => 'sold',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Прямое списание без резервирования (для старых продаж)
     */
    protected function sellDirectly(OfflineSale $sale): array
    {
        $results = [
            'success' => true,
            'action' => 'sell_direct',
            'items_processed' => 0,
            'items_failed' => 0,
        ];

        DB::beginTransaction();

        try {
            $items = $sale->items()->with('sku')->get();

            foreach ($items as $item) {
                /** @var OfflineSaleItem $item */
                $quantity = $item->quantity;
                if ($quantity <= 0) {
                    continue;
                }

                $variant = $this->findVariantForItem($sale->company_id, $item);

                if (! $variant) {
                    $results['items_failed']++;

                    continue;
                }

                $stockBefore = $variant->stock_default;

                // Списываем напрямую
                $variant->decrementStockQuietly($quantity);
                $stockAfter = $variant->stock_default;

                // Создаём ledger entry
                $warehouseSku = $this->getOrCreateWarehouseSku($sale->company_id, $variant);
                if ($warehouseSku) {
                    $warehouseId = $this->determineWarehouse($sale->company_id, $sale->warehouse_id);
                    if ($warehouseId) {
                        StockLedger::create([
                            'company_id' => $sale->company_id,
                            'occurred_at' => now(),
                            'warehouse_id' => $warehouseId,
                            'sku_id' => $warehouseSku->id,
                            'qty_delta' => -$quantity,
                            'cost_delta' => 0,
                            'currency_code' => 'UZS',
                            'source_type' => 'offline_sale_sold',
                            'source_id' => $sale->id,
                        ]);
                    }
                }

                Log::info('OfflineSaleStockService: Stock sold directly', [
                    'sale_id' => $sale->id,
                    'variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);

                $results['items_processed']++;
            }

            $sale->update([
                'stock_status' => 'sold',
                'stock_sold_at' => now(),
            ]);

            DB::commit();

            return $results;

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('OfflineSaleStockService: Failed to sell directly', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'action' => 'sell_direct',
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Отменить резерв при отмене продажи
     */
    protected function releaseReserve(OfflineSale $sale): array
    {
        $results = [
            'success' => true,
            'action' => 'release',
            'items_processed' => 0,
            'reservations_released' => 0,
        ];

        DB::beginTransaction();

        try {
            $freshSale = OfflineSale::lockForUpdate()->find($sale->id);
            if (! $freshSale || $freshSale->stock_status === 'released') {
                DB::rollBack();

                return ['success' => true, 'action' => 'none', 'message' => 'Already released'];
            }
            $sale = $freshSale;

            // Получаем активные резервы
            $activeReservations = StockReservation::where('source_type', 'offline_sale')
                ->where('source_id', $sale->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->with('sku.productVariant')
                ->get();

            foreach ($activeReservations as $reservation) {
                $variant = $reservation->sku?->productVariant;
                $qty = $reservation->qty;

                // Отменяем резерв
                $reservation->update(['status' => StockReservation::STATUS_CANCELLED]);
                $results['reservations_released']++;

                if ($variant) {
                    $stockBefore = $variant->stock_default;

                    // Возвращаем остатки
                    $variant->incrementStockQuietly($qty);
                    $stockAfter = $variant->stock_default;

                    Log::info('OfflineSaleStockService: Stock returned from reservation', [
                        'sale_id' => $sale->id,
                        'variant_id' => $variant->id,
                        'quantity' => $qty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                    ]);

                    $results['items_processed']++;
                }
            }

            $sale->update([
                'stock_status' => 'released',
                'stock_released_at' => now(),
            ]);

            DB::commit();

            return $results;

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('OfflineSaleStockService: Failed to release reserve', [
                'sale_id' => $sale->id,
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
     * Найти вариант товара для позиции продажи
     */
    protected function findVariantForItem(int $companyId, OfflineSaleItem $item): ?ProductVariant
    {
        // 1. Если указан sku_id - ищем через warehouse SKU
        if ($item->sku_id) {
            $warehouseSku = WarehouseSku::where('id', $item->sku_id)
                ->where('company_id', $companyId)
                ->first();

            if ($warehouseSku && $warehouseSku->productVariant) {
                return $warehouseSku->productVariant;
            }
        }

        // 2. Если указан product_variant_id - напрямую
        if ($item->product_variant_id) {
            return ProductVariant::where('id', $item->product_variant_id)
                ->where('company_id', $companyId)
                ->first();
        }

        // 3. Если указан product_id без variant - берем дефолтный вариант
        if ($item->product_id) {
            return ProductVariant::where('product_id', $item->product_id)
                ->where('company_id', $companyId)
                ->where('is_default', true)
                ->first();
        }

        return null;
    }

    /**
     * Получить или создать warehouse SKU
     */
    protected function getOrCreateWarehouseSku(int $companyId, ProductVariant $variant): ?WarehouseSku
    {
        try {
            return WarehouseSku::firstOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'company_id' => $companyId,
                ],
                [
                    'product_id' => $variant->product_id,
                    'sku_code' => $variant->sku,
                    'barcode_ean13' => $variant->barcode,
                    'is_active' => true,
                ]
            );
        } catch (\Throwable $e) {
            Log::error('OfflineSaleStockService: Failed to get/create warehouse SKU', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Определить склад для использования
     */
    protected function determineWarehouse(int $companyId, ?int $saleWarehouseId = null): ?int
    {
        // Priority 1: Склад указанный в продаже
        if ($saleWarehouseId) {
            return $saleWarehouseId;
        }

        // Priority 2: Первый активный склад компании
        $warehouse = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('id')
            ->first();

        if ($warehouse) {
            return $warehouse->id;
        }

        // Priority 3: Создать склад по умолчанию
        try {
            $warehouse = Warehouse::create([
                'company_id' => $companyId,
                'name' => 'Склад по умолчанию',
                'code' => 'DEFAULT',
                'is_active' => true,
            ]);

            return $warehouse->id;
        } catch (\Throwable $e) {
            Log::error('OfflineSaleStockService: Failed to create default warehouse', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Создать резерв товара
     */
    protected function createStockReservation(OfflineSale $sale, WarehouseSku $warehouseSku, int $quantity): void
    {
        try {
            $warehouseId = $this->determineWarehouse($sale->company_id, $sale->warehouse_id);

            if (! $warehouseId) {
                return;
            }

            StockReservation::create([
                'company_id' => $sale->company_id,
                'warehouse_id' => $warehouseId,
                'sku_id' => $warehouseSku->id,
                'qty' => $quantity,
                'status' => StockReservation::STATUS_ACTIVE,
                'reason' => "Offline sale: {$sale->sale_type}",
                'source_type' => 'offline_sale',
                'source_id' => $sale->id,
                'expires_at' => now()->addDays(30),
                'created_by' => $sale->created_by,
            ]);

            Log::info('OfflineSaleStockService: Stock reservation created', [
                'sale_id' => $sale->id,
                'sku_id' => $warehouseSku->id,
                'quantity' => $quantity,
            ]);

        } catch (\Throwable $e) {
            Log::error('OfflineSaleStockService: Failed to create stock reservation', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Перевести резервы в CONSUMED
     */
    protected function consumeStockReservations(OfflineSale $sale): void
    {
        try {
            $updated = StockReservation::where('source_type', 'offline_sale')
                ->where('source_id', $sale->id)
                ->where('status', StockReservation::STATUS_ACTIVE)
                ->update(['status' => StockReservation::STATUS_CONSUMED]);

            Log::info('OfflineSaleStockService: Stock reservations consumed', [
                'sale_id' => $sale->id,
                'count' => $updated,
            ]);

        } catch (\Throwable $e) {
            Log::error('OfflineSaleStockService: Failed to consume stock reservations', [
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
