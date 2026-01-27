<?php

namespace App\Observers;

use App\Events\StockUpdated;
use App\Events\UzumOrderUpdated;
use App\Models\UzumOrder;
use App\Models\VariantMarketplaceLink;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\Log;

class UzumOrderObserver
{
    /**
     * Handle the UzumOrder "created" event.
     *
     * NOTE: Stock reduction is handled by OrderStockService::processOrderStatusChange
     * which is called after order items are synced. Don't duplicate stock logic here.
     */
    public function created(UzumOrder $uzumOrder): void
    {
        $this->safeBroadcast($uzumOrder, 'created');
    }

    /**
     * Handle the UzumOrder "updated" event.
     */
    public function updated(UzumOrder $uzumOrder): void
    {
        // Только если изменился статус или другие важные поля
        if ($uzumOrder->wasChanged(['status', 'total_amount', 'ordered_at'])) {
            $this->safeBroadcast($uzumOrder, 'updated');
        }
    }

    /**
     * Handle the UzumOrder "deleted" event.
     */
    public function deleted(UzumOrder $uzumOrder): void
    {
        $this->safeBroadcast($uzumOrder, 'deleted');
    }

    /**
     * Safely broadcast event without breaking the main flow
     */
    protected function safeBroadcast(UzumOrder $uzumOrder, string $action): void
    {
        try {
            broadcast(new UzumOrderUpdated($uzumOrder, $action))->toOthers();
        } catch (\Exception $e) {
            Log::debug('UzumOrderObserver broadcast failed', [
                'order_id' => $uzumOrder->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * Reduce internal stock for order items with linked variants
     */
    protected function reduceInternalStock(UzumOrder $order): void
    {
        // Load order items if not already loaded
        if (!$order->relationLoaded('items')) {
            $order->load('items');
        }

        foreach ($order->items as $item) {
            // Get barcode from raw_payload (most reliable identifier from Uzum API)
            $rawPayload = $item->raw_payload ?? [];
            $barcode = $rawPayload['barcode'] ?? null;
            $externalOfferId = $item->external_offer_id;

            if (!$barcode && !$externalOfferId) {
                Log::debug('Uzum order item has no barcode or external_offer_id', [
                    'order_id' => $order->external_order_id,
                    'item_name' => $item->name,
                ]);
                continue;
            }

            // Try to find linked variant using multiple strategies
            $link = $this->findVariantLink($order->marketplace_account_id, $externalOfferId, $barcode);

            if (!$link || !$link->variant) {
                Log::info('No linked variant found for Uzum order item', [
                    'order_id' => $order->external_order_id,
                    'external_offer_id' => $externalOfferId,
                    'barcode' => $barcode,
                    'item_name' => $item->name,
                ]);
                continue;
            }

            // Decrease internal stock in both systems
            $oldStock = $link->variant->stock_default ?? 0;
            $newStock = max(0, $oldStock - $item->quantity);

            // Update stock_default WITHOUT triggering ProductVariantObserver
            // (to avoid duplicate ledger entries - we create our own below)
            $link->variant->stock_default = $newStock;
            $link->variant->saveQuietly();

            // Create ledger entry for warehouse system
            $this->reduceWarehouseStock($link->variant, $item->quantity, $order);

            // Fire StockUpdated event to sync to OTHER marketplaces
            // (saveQuietly bypasses observer, so we need to dispatch manually)
            // Pass link_id to exclude this specific link from sync (important for Uzum with multiple shops)
            event(new StockUpdated($link->variant, $oldStock, $newStock, $link->id));

            Log::info('Internal stock reduced for Uzum order', [
                'order_id' => $order->external_order_id,
                'variant_id' => $link->variant->id,
                'sku' => $link->variant->sku,
                'barcode' => $barcode,
                'quantity' => $item->quantity,
                'old_stock' => $oldStock,
                'new_stock' => $link->variant->stock_default,
            ]);
        }
    }

    /**
     * Reduce stock in warehouse system (stock_ledger)
     */
    protected function reduceWarehouseStock($variant, int $quantity, UzumOrder $order): void
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

            // Create negative ledger entry (stock out)
            StockLedger::create([
                'company_id' => $variant->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouse->id,
                'location_id' => null,
                'sku_id' => $warehouseSku->id,
                'qty_delta' => -$quantity, // Negative for stock out
                'cost_delta' => 0, // Cost will be calculated if needed
                'currency_code' => 'UZS',
                'document_id' => null,
                'document_line_id' => null,
                'source_type' => 'UZUM_ORDER',
                'source_id' => $order->id,
                'created_by' => null,
            ]);

            Log::info('Warehouse stock reduced for Uzum order', [
                'order_id' => $order->external_order_id,
                'warehouse_sku_id' => $warehouseSku->id,
                'warehouse_id' => $warehouse->id,
                'quantity' => $quantity,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to reduce warehouse stock', [
                'variant_id' => $variant->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find VariantMarketplaceLink using multiple lookup strategies
     */
    protected function findVariantLink(int $accountId, ?string $externalOfferId, ?string $barcode): ?VariantMarketplaceLink
    {
        // Strategy 1: Find by marketplace_barcode (баркод маркетплейса может отличаться от внутреннего)
        if ($barcode) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $accountId)
                ->where('marketplace_barcode', $barcode)
                ->where('is_active', true)
                ->with('variant')
                ->first();

            if ($link) {
                Log::debug('Found link by marketplace_barcode', [
                    'barcode' => $barcode,
                    'link_id' => $link->id,
                ]);
                return $link;
            }
        }

        // Strategy 2: Find by external_sku_id (if external_offer_id is a SKU ID)
        if ($externalOfferId) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $accountId)
                ->where('external_sku_id', $externalOfferId)
                ->where('is_active', true)
                ->with('variant')
                ->first();

            if ($link) {
                Log::debug('Found link by external_sku_id', [
                    'external_offer_id' => $externalOfferId,
                    'link_id' => $link->id,
                ]);
                return $link;
            }
        }

        // Strategy 3: Find by variant internal barcode (если баркод совпадает с внутренним)
        if ($barcode) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $accountId)
                ->where('is_active', true)
                ->whereHas('variant', function ($query) use ($barcode) {
                    $query->where('barcode', $barcode);
                })
                ->with('variant')
                ->first();

            if ($link) {
                Log::debug('Found link by variant internal barcode', [
                    'barcode' => $barcode,
                    'link_id' => $link->id,
                ]);
                return $link;
            }
        }

        // Strategy 4: Find by external_offer_id field
        if ($externalOfferId) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $accountId)
                ->where('external_offer_id', $externalOfferId)
                ->where('is_active', true)
                ->with('variant')
                ->first();

            if ($link) {
                Log::debug('Found link by external_offer_id', [
                    'external_offer_id' => $externalOfferId,
                    'link_id' => $link->id,
                ]);
                return $link;
            }
        }

        return null;
    }
}
