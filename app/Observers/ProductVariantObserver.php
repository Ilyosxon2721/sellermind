<?php

namespace App\Observers;

use App\Events\StockUpdated;
use App\Models\ProductVariant;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\Log;

class ProductVariantObserver
{
    /**
     * Handle the ProductVariant "created" event.
     * Автоматически создаёт SKU в складской системе и начальный остаток
     */
    public function created(ProductVariant $variant): void
    {
        try {
            $sku = $this->syncToWarehouseSku($variant);

            // If variant has initial stock, create stock ledger entry
            if ($sku && $variant->stock_default > 0) {
                $this->createInitialStock($variant, $sku);
            }
        } catch (\Exception $e) {
            Log::warning('ProductVariantObserver::created failed', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Handle the ProductVariant "updated" event.
     *
     * Fires after the model is successfully saved. We can check which attributes were changed
     * and dispatch events accordingly.
     *
     * @param  \App\Models\ProductVariant  $variant
     * @return void
     */
    public function updated(ProductVariant $variant): void
    {
        try {
            // Sync SKU/barcode changes to warehouse system
            if ($variant->wasChanged(['sku', 'barcode', 'weight_g', 'length_mm', 'width_mm', 'height_mm', 'is_active'])) {
                $this->syncToWarehouseSku($variant);
            }

            // Use wasChanged() to see if 'stock_default' was part of the update.
            if ($variant->wasChanged('stock_default')) {
                // getOriginal() provides the value before the update.
                $oldStock = $variant->getOriginal('stock_default') ?? 0;
                $newStock = $variant->stock_default ?? 0;

                // Fire the event only if the stock value actually changed.
                if ($oldStock !== $newStock) {
                    // Sync to warehouse stock_ledger
                    $this->syncStockToWarehouse($variant, $oldStock, $newStock);

                    // Fire event for marketplace sync
                    event(new StockUpdated($variant, $oldStock, $newStock));
                }
            }
        } catch (\Exception $e) {
            Log::warning('ProductVariantObserver::updated failed', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Синхронизировать ProductVariant в Warehouse\Sku
     * @return Sku|null
     */
    protected function syncToWarehouseSku(ProductVariant $variant): ?Sku
    {
        // Только для активных, не удалённых вариантов
        if ($variant->is_deleted) {
            return null;
        }

        try {
            $sku = Sku::updateOrCreate(
                ['product_variant_id' => $variant->id],
                [
                    'product_id' => $variant->product_id,
                    'company_id' => $variant->company_id,
                    'sku_code' => $variant->sku,
                    'barcode_ean13' => $variant->barcode,
                    'weight_g' => $variant->weight_g,
                    'length_mm' => $variant->length_mm,
                    'width_mm' => $variant->width_mm,
                    'height_mm' => $variant->height_mm,
                    'is_active' => $variant->is_active,
                ]
            );

            Log::debug('ProductVariant synced to Warehouse SKU', [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'sku_id' => $sku->id,
            ]);

            return $sku;
        } catch (\Exception $e) {
            Log::warning('Failed to sync ProductVariant to Warehouse SKU', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Создать начальный остаток при создании варианта товара
     */
    protected function createInitialStock(ProductVariant $variant, Sku $sku): void
    {
        try {
            // Get default warehouse for this company
            $warehouse = Warehouse::where('company_id', $variant->company_id)
                ->where('is_default', true)
                ->first();

            if (!$warehouse) {
                // Try to get any warehouse for this company
                $warehouse = Warehouse::where('company_id', $variant->company_id)->first();
            }

            if (!$warehouse) {
                Log::warning('No warehouse found for initial stock', [
                    'variant_id' => $variant->id,
                    'company_id' => $variant->company_id,
                ]);
                return;
            }

            StockLedger::create([
                'company_id' => $variant->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouse->id,
                'sku_id' => $sku->id,
                'qty_delta' => $variant->stock_default,
                'cost_delta' => ($variant->purchase_price ?? 0) * $variant->stock_default,
                'source_type' => 'initial_stock',
                'source_id' => $variant->id,
            ]);

            Log::info('Initial stock created for variant', [
                'variant_id' => $variant->id,
                'sku_id' => $sku->id,
                'warehouse_id' => $warehouse->id,
                'qty' => $variant->stock_default,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to create initial stock', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Синхронизировать изменение stock_default в warehouse stock_ledger
     */
    protected function syncStockToWarehouse(ProductVariant $variant, int $oldStock, int $newStock): void
    {
        try {
            // Find SKU in warehouse system
            $sku = Sku::where('product_variant_id', $variant->id)->first();
            if (!$sku) {
                // Try to create it
                $sku = $this->syncToWarehouseSku($variant);
            }

            if (!$sku) {
                Log::warning('No SKU found for stock sync', [
                    'variant_id' => $variant->id,
                ]);
                return;
            }

            // Get default warehouse for this company
            $warehouse = Warehouse::where('company_id', $variant->company_id)
                ->where('is_default', true)
                ->first();

            if (!$warehouse) {
                $warehouse = Warehouse::where('company_id', $variant->company_id)->first();
            }

            if (!$warehouse) {
                Log::warning('No warehouse found for stock sync', [
                    'variant_id' => $variant->id,
                    'company_id' => $variant->company_id,
                ]);
                return;
            }

            $delta = $newStock - $oldStock;

            StockLedger::create([
                'company_id' => $variant->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouse->id,
                'sku_id' => $sku->id,
                'qty_delta' => $delta,
                'cost_delta' => ($variant->purchase_price ?? 0) * $delta,
                'source_type' => 'stock_adjustment',
                'source_id' => $variant->id,
            ]);

            Log::info('Stock synced to warehouse ledger', [
                'variant_id' => $variant->id,
                'sku_id' => $sku->id,
                'warehouse_id' => $warehouse->id,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'delta' => $delta,
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to sync stock to warehouse', [
                'variant_id' => $variant->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
