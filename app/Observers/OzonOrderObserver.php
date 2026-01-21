<?php

namespace App\Observers;

use App\Events\MarketplaceOrdersUpdated;
use App\Events\StockUpdated;
use App\Models\OzonOrder;
use App\Models\VariantMarketplaceLink;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\Log;

class OzonOrderObserver
{
    /**
     * Handle the OzonOrder "created" event.
     */
    public function created(OzonOrder $ozonOrder): void
    {
        $this->safeBroadcast($ozonOrder);

        // Reduce internal stock for linked variants
        $this->reduceInternalStock($ozonOrder);
    }

    /**
     * Handle the OzonOrder "updated" event.
     */
    public function updated(OzonOrder $ozonOrder): void
    {
        // Only broadcast if important fields changed
        if ($ozonOrder->wasChanged(['status', 'substatus', 'total_price'])) {
            $this->safeBroadcast($ozonOrder);

            // Handle cancellation - return stock
            $oldStatus = $ozonOrder->getOriginal('status');
            $newStatus = $ozonOrder->status;

            if ($this->isOrderCancelled($newStatus) && !$this->isOrderCancelled($oldStatus)) {
                $this->returnInternalStock($ozonOrder);
            }
        }
    }

    /**
     * Handle the OzonOrder "deleted" event.
     */
    public function deleted(OzonOrder $ozonOrder): void
    {
        $this->safeBroadcast($ozonOrder);
    }

    /**
     * Safely broadcast event without breaking the main flow
     */
    protected function safeBroadcast(OzonOrder $ozonOrder): void
    {
        try {
            broadcast(new MarketplaceOrdersUpdated(
                $ozonOrder->account?->company_id ?? 0,
                $ozonOrder->marketplace_account_id,
                'ozon'
            ))->toOthers();
        } catch (\Exception $e) {
            Log::debug('OzonOrderObserver broadcast failed', [
                'order_id' => $ozonOrder->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if order status is cancelled
     */
    protected function isOrderCancelled(?string $status): bool
    {
        return in_array(strtolower($status ?? ''), [
            'cancelled',
            'canceled',
        ]);
    }

    /**
     * Reduce internal stock for order products
     */
    protected function reduceInternalStock(OzonOrder $order): void
    {
        $products = $order->getProductsList();

        foreach ($products as $product) {
            $sku = $product['sku'] ?? $product['offer_id'] ?? null;
            $quantity = $product['quantity'] ?? 1;

            if (!$sku) {
                Log::debug('Ozon order product has no SKU', [
                    'order_id' => $order->posting_number,
                    'product' => $product,
                ]);
                continue;
            }

            // Find linked variant
            $link = $this->findVariantLink($order->marketplace_account_id, $sku);

            if (!$link || !$link->variant) {
                Log::info('No linked variant found for Ozon order product', [
                    'order_id' => $order->posting_number,
                    'sku' => $sku,
                ]);
                continue;
            }

            // Decrease internal stock in both systems
            $oldStock = $link->variant->stock_default ?? 0;
            $newStock = max(0, $oldStock - $quantity);

            // Update stock_default WITHOUT triggering ProductVariantObserver
            // (to avoid duplicate ledger entries - we create our own below)
            $link->variant->stock_default = $newStock;
            $link->variant->saveQuietly();

            // Create ledger entry for warehouse system
            $this->updateWarehouseStock($link->variant, -$quantity, $order, 'OZON_ORDER');

            // Fire StockUpdated event to sync to OTHER marketplaces
            event(new StockUpdated($link->variant, $oldStock, $newStock));

            Log::info('Internal stock reduced for Ozon order', [
                'order_id' => $order->posting_number,
                'variant_id' => $link->variant->id,
                'variant_sku' => $link->variant->sku,
                'external_sku' => $sku,
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $link->variant->stock_default,
            ]);
        }
    }

    /**
     * Return stock when order is cancelled
     */
    protected function returnInternalStock(OzonOrder $order): void
    {
        $products = $order->getProductsList();

        foreach ($products as $product) {
            $sku = $product['sku'] ?? $product['offer_id'] ?? null;
            $quantity = $product['quantity'] ?? 1;

            if (!$sku) {
                continue;
            }

            $link = $this->findVariantLink($order->marketplace_account_id, $sku);

            if (!$link || !$link->variant) {
                continue;
            }

            // Increase internal stock in both systems
            $oldStock = $link->variant->stock_default ?? 0;
            $newStock = $oldStock + $quantity;

            // Update stock_default WITHOUT triggering ProductVariantObserver
            $link->variant->stock_default = $newStock;
            $link->variant->saveQuietly();

            // Create ledger entry for warehouse system
            $this->updateWarehouseStock($link->variant, $quantity, $order, 'OZON_ORDER_CANCEL');

            // Fire StockUpdated event to sync to OTHER marketplaces
            event(new StockUpdated($link->variant, $oldStock, $newStock));

            Log::info('Internal stock returned for cancelled Ozon order', [
                'order_id' => $order->posting_number,
                'variant_id' => $link->variant->id,
                'variant_sku' => $link->variant->sku,
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $link->variant->stock_default,
            ]);
        }
    }

    /**
     * Update stock in warehouse system (stock_ledger)
     */
    protected function updateWarehouseStock($variant, int $qtyDelta, OzonOrder $order, string $sourceType): void
    {
        try {
            // Find warehouse SKU linked to this variant
            $warehouseSku = Sku::where('product_variant_id', $variant->id)->first();

            if (!$warehouseSku) {
                Log::debug('No warehouse SKU found for variant', [
                    'variant_id' => $variant->id,
                    'sku' => $variant->sku,
                ]);
                return;
            }

            // Get default warehouse for company
            $warehouse = Warehouse::where('company_id', $variant->company_id)
                ->where('is_default', true)
                ->first();

            if (!$warehouse) {
                $warehouse = Warehouse::where('company_id', $variant->company_id)->first();
            }

            if (!$warehouse) {
                Log::warning('No warehouse found for variant company', [
                    'variant_id' => $variant->id,
                    'company_id' => $variant->company_id,
                ]);
                return;
            }

            // Create ledger entry
            StockLedger::create([
                'company_id' => $variant->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouse->id,
                'location_id' => null,
                'sku_id' => $warehouseSku->id,
                'qty_delta' => $qtyDelta,
                'cost_delta' => 0,
                'currency_code' => 'UZS',
                'document_id' => null,
                'document_line_id' => null,
                'source_type' => $sourceType,
                'source_id' => $order->id,
                'created_by' => null,
            ]);

            Log::info('Warehouse stock updated for Ozon order', [
                'order_id' => $order->posting_number,
                'warehouse_sku_id' => $warehouseSku->id,
                'warehouse_id' => $warehouse->id,
                'qty_delta' => $qtyDelta,
                'source_type' => $sourceType,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update warehouse stock for Ozon order', [
                'variant_id' => $variant->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find variant link by Ozon SKU
     */
    protected function findVariantLink(int $accountId, ?string $sku): ?VariantMarketplaceLink
    {
        if (!$sku) {
            return null;
        }

        $query = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $accountId)
            ->where('is_active', true)
            ->with('variant');

        // Try by external_sku first
        $link = (clone $query)->where('external_sku', $sku)->first();
        if ($link) return $link;

        // Try by external_offer_id
        $link = (clone $query)->where('external_offer_id', $sku)->first();
        if ($link) return $link;

        // Try by external_sku_id
        $link = (clone $query)->where('external_sku_id', $sku)->first();
        if ($link) return $link;

        return null;
    }
}
