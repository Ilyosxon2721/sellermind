<?php

namespace App\Observers;

use App\Events\MarketplaceOrdersUpdated;
use App\Events\StockUpdated;
use App\Models\WbOrder;
use App\Models\VariantMarketplaceLink;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\Log;

class WbOrderObserver
{
    /**
     * Handle the WbOrder "created" event.
     */
    public function created(WbOrder $wbOrder): void
    {
        $this->safeBroadcast($wbOrder);

        // Reduce internal stock for linked variants
        $this->reduceInternalStock($wbOrder);
    }

    /**
     * Handle the WbOrder "updated" event.
     */
    public function updated(WbOrder $wbOrder): void
    {
        // Only broadcast if important fields changed
        if ($wbOrder->wasChanged(['status', 'status_normalized', 'wb_status', 'total_amount', 'ordered_at'])) {
            $this->safeBroadcast($wbOrder);

            // Handle cancellation - return stock
            $oldStatus = $wbOrder->getOriginal('status_normalized');
            $newStatus = $wbOrder->status_normalized;

            if ($this->isOrderCancelled($newStatus) && !$this->isOrderCancelled($oldStatus)) {
                $this->returnInternalStock($wbOrder);
            }
        }
    }

    /**
     * Handle the WbOrder "deleted" event.
     */
    public function deleted(WbOrder $wbOrder): void
    {
        $this->safeBroadcast($wbOrder);
    }

    /**
     * Safely broadcast event without breaking the main flow
     */
    protected function safeBroadcast(WbOrder $wbOrder): void
    {
        try {
            broadcast(new MarketplaceOrdersUpdated(
                $wbOrder->account?->company_id ?? 0,
                $wbOrder->marketplace_account_id,
                1 // newOrdersCount
            ))->toOthers();
        } catch (\Exception $e) {
            Log::debug('WbOrderObserver broadcast failed', [
                'order_id' => $wbOrder->id,
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
            'cancel',
            'declined',
            'defect',
        ]);
    }

    /**
     * Reduce internal stock for order with linked variant
     * WB orders have SKU directly in the order, not in items
     */
    protected function reduceInternalStock(WbOrder $order): void
    {
        // WB order structure: each order is one item (sku is in the order itself)
        $sku = $order->sku;
        $nmId = $order->nm_id;
        $article = $order->article;

        if (!$sku && !$nmId && !$article) {
            Log::debug('WbOrder has no SKU/nmId/article for stock reduction', [
                'order_id' => $order->external_order_id,
            ]);
            return;
        }

        // Try to find linked variant by different identifiers
        $link = $this->findVariantLink($order->marketplace_account_id, $sku, $nmId, $article);

        if (!$link || !$link->variant) {
            Log::info('No linked variant found for WB order', [
                'order_id' => $order->external_order_id,
                'sku' => $sku,
                'nm_id' => $nmId,
                'article' => $article,
            ]);
            return;
        }

        // WB order quantity is always 1 per order record
        $quantity = 1;

        // Decrease internal stock in both systems
        $oldStock = $link->variant->stock_default ?? 0;
        $newStock = max(0, $oldStock - $quantity);

        // Update stock_default WITHOUT triggering ProductVariantObserver
        // (to avoid duplicate ledger entries - we create our own below)
        $link->variant->stock_default = $newStock;
        $link->variant->saveQuietly();

        // Create ledger entry for warehouse system
        $this->updateWarehouseStock($link->variant, -$quantity, $order, 'WB_ORDER');

        // Fire StockUpdated event to sync to OTHER marketplaces
        // (saveQuietly bypasses observer, so we need to dispatch manually)
        // Pass link_id to exclude this specific link from sync (already has correct stock)
        event(new StockUpdated($link->variant, $oldStock, $newStock, $link->id));

        Log::info('Internal stock reduced for WB order', [
            'order_id' => $order->external_order_id,
            'variant_id' => $link->variant->id,
            'variant_sku' => $link->variant->sku,
            'external_sku' => $sku,
            'nm_id' => $nmId,
            'quantity' => $quantity,
            'old_stock' => $oldStock,
            'new_stock' => $link->variant->stock_default,
        ]);
    }

    /**
     * Return stock when order is cancelled
     */
    protected function returnInternalStock(WbOrder $order): void
    {
        $sku = $order->sku;
        $nmId = $order->nm_id;
        $article = $order->article;

        if (!$sku && !$nmId && !$article) {
            return;
        }

        $link = $this->findVariantLink($order->marketplace_account_id, $sku, $nmId, $article);

        if (!$link || !$link->variant) {
            return;
        }

        // WB order quantity is always 1 per order record
        $quantity = 1;

        // Increase internal stock in both systems
        $oldStock = $link->variant->stock_default ?? 0;
        $newStock = $oldStock + $quantity;

        // Update stock_default WITHOUT triggering ProductVariantObserver
        $link->variant->stock_default = $newStock;
        $link->variant->saveQuietly();

        // Create ledger entry for warehouse system
        $this->updateWarehouseStock($link->variant, $quantity, $order, 'WB_ORDER_CANCEL');

        // Fire StockUpdated event to sync to OTHER marketplaces
        event(new StockUpdated($link->variant, $oldStock, $newStock, $link->id));

        Log::info('Internal stock returned for cancelled WB order', [
            'order_id' => $order->external_order_id,
            'variant_id' => $link->variant->id,
            'variant_sku' => $link->variant->sku,
            'quantity' => $quantity,
            'old_stock' => $oldStock,
            'new_stock' => $link->variant->stock_default,
        ]);
    }

    /**
     * Update stock in warehouse system (stock_ledger)
     */
    protected function updateWarehouseStock($variant, int $qtyDelta, WbOrder $order, string $sourceType): void
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

            Log::info('Warehouse stock updated for WB order', [
                'order_id' => $order->external_order_id,
                'warehouse_sku_id' => $warehouseSku->id,
                'warehouse_id' => $warehouse->id,
                'qty_delta' => $qtyDelta,
                'source_type' => $sourceType,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update warehouse stock for WB order', [
                'variant_id' => $variant->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find variant link by various WB identifiers
     */
    protected function findVariantLink(int $accountId, ?string $sku, ?string $nmId, ?string $article): ?VariantMarketplaceLink
    {
        $query = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $accountId)
            ->where('is_active', true)
            ->with('variant');

        // Try by external_sku_id first (most specific)
        if ($sku) {
            $link = (clone $query)->where('external_sku_id', $sku)->first();
            if ($link) return $link;

            // Also try external_sku
            $link = (clone $query)->where('external_sku', $sku)->first();
            if ($link) return $link;
        }

        // Try by nm_id (external_offer_id)
        if ($nmId) {
            $link = (clone $query)->where('external_offer_id', $nmId)->first();
            if ($link) return $link;

            $link = (clone $query)->where('external_sku_id', $nmId)->first();
            if ($link) return $link;
        }

        // Try by article
        if ($article) {
            $link = (clone $query)->where('external_sku', $article)->first();
            if ($link) return $link;
        }

        return null;
    }
}
