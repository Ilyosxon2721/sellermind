<?php

namespace App\Services;

use App\Models\ProductVariant;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use App\Services\Stock\StockSyncService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для управления резервированием и отгрузкой товаров при продаже
 */
class SaleReservationService
{
    public function __construct(
        protected StockSyncService $stockSyncService
    ) {}

    /**
     * Зарезервировать товары для продажи
     *
     * @param Sale $sale
     * @return array
     * @throws \Exception
     */
    public function reserveStock(Sale $sale): array
    {
        if ($sale->status !== 'draft') {
            throw new \Exception('Можно резервировать только черновики продаж');
        }

        if (!$sale->warehouse_id) {
            throw new \Exception('Не указан склад для списания');
        }

        return DB::transaction(function () use ($sale) {
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($sale->items as $item) {
                // Пропускаем расходы (expenses)
                if (!$item->product_variant_id || ($item->metadata['is_expense'] ?? false)) {
                    continue;
                }

                try {
                    $this->reserveItem($sale, $item);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to reserve stock for sale item', [
                        'sale_id' => $sale->id,
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Если хотя бы один товар не зарезервирован, откатываем всё
            if ($results['failed'] > 0) {
                throw new \Exception('Не удалось зарезервировать все товары: ' . json_encode($results['errors']));
            }

            return $results;
        });
    }

    /**
     * Зарезервировать один товар
     *
     * @param Sale $sale
     * @param SaleItem $item
     * @return StockReservation
     * @throws \Exception
     */
    protected function reserveItem(Sale $sale, SaleItem $item): StockReservation
    {
        $variant = $item->productVariant;

        if (!$variant) {
            throw new \Exception("Товар не найден (ID: {$item->product_variant_id})");
        }

        // Находим Warehouse\Sku для этого варианта
        $warehouseSku = \App\Models\Warehouse\Sku::where('product_variant_id', $variant->id)->first();

        if (!$warehouseSku) {
            throw new \Exception("Товар '{$item->product_name}' не зарегистрирован на складе. Запустите синхронизацию: php artisan warehouse:sync-variants");
        }

        // Проверка доступного остатка
        $availableStock = $this->getAvailableStock($variant, $sale->warehouse_id);

        if ($availableStock < $item->quantity) {
            throw new \Exception(
                "Недостаточно товара '{$item->product_name}'. Доступно: {$availableStock}, требуется: {$item->quantity}"
            );
        }

        // Создаём резерв (используем Warehouse\Sku ID)
        $reservation = StockReservation::create([
            'company_id' => $sale->company_id,
            'warehouse_id' => $sale->warehouse_id,
            'sku_id' => $warehouseSku->id,
            'qty' => $item->quantity,
            'status' => StockReservation::STATUS_ACTIVE,
            'reason' => 'Резерв для продажи',
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'expires_at' => null, // Резерв не истекает до отгрузки/отмены
            'created_by' => auth()->id(),
        ]);

        // ПРИМЕЧАНИЕ: Остаток НЕ уменьшается физически при резервировании
        // Физическое списание происходит при отгрузке через stock_ledger

        // Обновляем item
        $item->update([
            'stock_deducted' => true,
            'stock_deducted_at' => now(),
            'metadata' => array_merge($item->metadata ?? [], [
                'reservation_id' => $reservation->id,
                'reserved_qty' => $item->quantity,
                'warehouse_id' => $sale->warehouse_id,
                'warehouse_sku_id' => $warehouseSku->id,
            ]),
        ]);

        Log::info('Stock reserved for sale item', [
            'sale_id' => $sale->id,
            'item_id' => $item->id,
            'reservation_id' => $reservation->id,
            'warehouse_sku_id' => $warehouseSku->id,
            'quantity' => $item->quantity,
        ]);

        return $reservation;
    }

    /**
     * Отгрузить товары (перевести резерв в потребление)
     *
     * @param Sale $sale
     * @param array|null $itemIds Конкретные позиции для отгрузки (null = все)
     * @return array
     * @throws \Exception
     */
    public function shipStock(Sale $sale, ?array $itemIds = null): array
    {
        if (!in_array($sale->status, ['confirmed', 'completed'])) {
            throw new \Exception('Можно отгружать только подтверждённые продажи');
        }

        return DB::transaction(function () use ($sale, $itemIds) {
            $results = [
                'success' => 0,
                'failed' => 0,
                'items_shipped' => [],
                'errors' => [],
            ];

            $items = $itemIds
                ? $sale->items()->whereIn('id', $itemIds)->get()
                : $sale->items;

            foreach ($items as $item) {
                // Пропускаем расходы и уже отгруженные товары
                if (!$item->product_variant_id || ($item->metadata['is_expense'] ?? false)) {
                    continue;
                }

                if ($item->metadata['shipped'] ?? false) {
                    continue; // Уже отгружено
                }

                try {
                    $this->shipItem($sale, $item);
                    $results['success']++;
                    $results['items_shipped'][] = $item->id;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to ship sale item', [
                        'sale_id' => $sale->id,
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $results;
        });
    }

    /**
     * Отгрузить один товар
     *
     * @param Sale $sale
     * @param SaleItem $item
     * @return void
     * @throws \Exception
     */
    protected function shipItem(Sale $sale, SaleItem $item): void
    {
        $reservationId = $item->metadata['reservation_id'] ?? null;

        if (!$reservationId) {
            throw new \Exception('Резерв не найден для позиции');
        }

        $reservation = StockReservation::find($reservationId);

        if (!$reservation) {
            throw new \Exception("Резерв #{$reservationId} не найден");
        }

        // Получаем warehouse_sku_id из metadata (сохранен при резервировании)
        $warehouseSkuId = $item->metadata['warehouse_sku_id'] ?? null;

        if (!$warehouseSkuId) {
            // Fallback: ищем по product_variant_id
            $variant = $item->productVariant;
            $warehouseSku = \App\Models\Warehouse\Sku::where('product_variant_id', $variant->id)->first();
            $warehouseSkuId = $warehouseSku?->id;
        }

        if (!$warehouseSkuId) {
            throw new \Exception("Warehouse SKU не найден для товара '{$item->product_name}'");
        }

        // Переводим резерв в статус CONSUMED (потреблён)
        $reservation->update([
            'status' => StockReservation::STATUS_CONSUMED,
        ]);

        // Создаём движение в stock_ledger (списание со склада)
        StockLedger::create([
            'company_id' => $sale->company_id,
            'occurred_at' => now(),
            'warehouse_id' => $sale->warehouse_id,
            'sku_id' => $warehouseSkuId,  // Используем правильный Warehouse\Sku ID
            'qty_delta' => -$item->quantity, // Отрицательное значение = списание
            'cost_delta' => -($item->cost_price ?? 0) * $item->quantity,
            'document_id' => null,
            'document_line_id' => null,
            'source_type' => Sale::class,
            'source_id' => $sale->id,
            'created_by' => auth()->id(),
        ]);

        // Обновляем metadata в item
        $item->update([
            'metadata' => array_merge($item->metadata ?? [], [
                'shipped' => true,
                'shipped_at' => now()->toIso8601String(),
            ]),
        ]);

        // ВАЖНО: Синхронизируем остатки с маркетплейсами ТОЛЬКО после отгрузки
        if ($item->product_variant_id) {
            $variant = $item->productVariant;
            $this->stockSyncService->syncVariantStock($variant);

            // Получаем актуальный остаток из warehouse
            $currentStock = \App\Models\Warehouse\StockLedger::where('sku_id', $warehouseSkuId)
                ->where('warehouse_id', $sale->warehouse_id)
                ->sum('qty_delta');

            Log::info('Stock synced to marketplaces after shipment', [
                'sale_id' => $sale->id,
                'item_id' => $item->id,
                'variant_id' => $variant->id,
                'new_stock' => $currentStock,
            ]);
        }

        Log::info('Sale item shipped', [
            'sale_id' => $sale->id,
            'item_id' => $item->id,
            'quantity' => $item->quantity,
        ]);
    }

    /**
     * Отменить резервы и вернуть товар на склад
     *
     * @param Sale $sale
     * @return array
     * @throws \Exception
     */
    public function cancelReservations(Sale $sale): array
    {
        return DB::transaction(function () use ($sale) {
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => [],
            ];

            foreach ($sale->items as $item) {
                if (!$item->product_variant_id || !$item->stock_deducted) {
                    continue;
                }

                // Нельзя отменить уже отгруженные товары
                if ($item->metadata['shipped'] ?? false) {
                    $results['errors'][] = [
                        'item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'error' => 'Товар уже отгружен, отмена невозможна',
                    ];
                    continue;
                }

                try {
                    $this->cancelItemReservation($item);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'item_id' => $item->id,
                        'product_name' => $item->product_name,
                        'error' => $e->getMessage(),
                    ];

                    Log::error('Failed to cancel reservation for sale item', [
                        'sale_id' => $sale->id,
                        'item_id' => $item->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            return $results;
        });
    }

    /**
     * Отменить резерв одного товара
     *
     * @param SaleItem $item
     * @return void
     * @throws \Exception
     */
    protected function cancelItemReservation(SaleItem $item): void
    {
        $reservationId = $item->metadata['reservation_id'] ?? null;

        if ($reservationId) {
            $reservation = StockReservation::find($reservationId);

            if ($reservation) {
                $reservation->update([
                    'status' => StockReservation::STATUS_CANCELLED,
                ]);
            }
        }

        // Возвращаем остаток
        if ($item->productVariant) {
            $item->productVariant->incrementStock((int)$item->quantity);

            // Синхронизируем обновлённые остатки с маркетплейсами
            $this->stockSyncService->syncVariantStock($item->productVariant);

            Log::info('Stock returned and synced after cancellation', [
                'item_id' => $item->id,
                'variant_id' => $item->product_variant_id,
                'quantity' => $item->quantity,
                'new_stock' => $item->productVariant->stock_default,
            ]);
        }

        // Обновляем item
        $item->update([
            'stock_deducted' => false,
            'stock_deducted_at' => null,
            'metadata' => array_merge($item->metadata ?? [], [
                'cancelled' => true,
                'cancelled_at' => now()->toIso8601String(),
            ]),
        ]);

        Log::info('Reservation cancelled for sale item', [
            'item_id' => $item->id,
            'reservation_id' => $reservationId,
        ]);
    }

    /**
     * Получить доступный остаток товара на складе
     * (реальный остаток - активные резервы)
     *
     * @param ProductVariant $variant
     * @param int $warehouseId
     * @return float
     */
    public function getAvailableStock(ProductVariant $variant, int $warehouseId): float
    {
        // Находим Warehouse\Sku для этого варианта
        $warehouseSku = \App\Models\Warehouse\Sku::where('product_variant_id', $variant->id)->first();

        if (!$warehouseSku) {
            // Если товар не зарегистрирован в warehouse, возвращаем 0
            return 0;
        }

        // Получаем текущий остаток из stock_ledger
        $currentStock = \App\Models\Warehouse\StockLedger::where('sku_id', $warehouseSku->id)
            ->where('warehouse_id', $warehouseId)
            ->sum('qty_delta');

        // Считаем активные резервы на этом складе
        $activeReservations = StockReservation::where('sku_id', $warehouseSku->id)
            ->where('warehouse_id', $warehouseId)
            ->where('status', StockReservation::STATUS_ACTIVE)
            ->sum('qty');

        return max(0, $currentStock - $activeReservations);
    }

    /**
     * Получить активные резервы для продажи
     *
     * @param Sale $sale
     * @return \Illuminate\Support\Collection
     */
    public function getActiveReservations(Sale $sale)
    {
        return StockReservation::where('source_type', Sale::class)
            ->where('source_id', $sale->id)
            ->where('status', StockReservation::STATUS_ACTIVE)
            ->get();
    }

    /**
     * Проверить, все ли товары отгружены
     *
     * @param Sale $sale
     * @return bool
     */
    public function isFullyShipped(Sale $sale): bool
    {
        $totalItems = $sale->items()
            ->whereNotNull('product_variant_id')
            ->count();

        if ($totalItems === 0) {
            return true;
        }

        $shippedItems = $sale->items()
            ->whereNotNull('product_variant_id')
            ->where('metadata->shipped', true)
            ->count();

        return $shippedItems === $totalItems;
    }
}
