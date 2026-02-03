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
     *
     * NOTE: Stock reduction is handled by OrderStockService::processOrderStatusChange
     * which is called after order sync. Don't duplicate stock logic here.
     */
    public function created(OzonOrder $ozonOrder): void
    {
        $this->safeBroadcast($ozonOrder);
    }

    /**
     * Handle the OzonOrder "updated" event.
     *
     * NOTE: Stock changes (including cancellation) are handled by OrderStockService.
     */
    public function updated(OzonOrder $ozonOrder): void
    {
        // Only broadcast if important fields changed
        if ($ozonOrder->wasChanged(['status', 'substatus', 'total_price'])) {
            $this->safeBroadcast($ozonOrder);
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
            $barcode = $product['barcode'] ?? null;
            $quantity = $product['quantity'] ?? 1;

            if (! $sku && ! $barcode) {
                Log::debug('Ozon order product has no SKU or barcode', [
                    'order_id' => $order->posting_number,
                    'product' => $product,
                ]);

                continue;
            }

            // Find linked variant
            $link = $this->findVariantLink($order->marketplace_account_id, $sku, $barcode);

            if (! $link || ! $link->variant) {
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
            // Pass link_id to exclude this specific link from sync
            event(new StockUpdated($link->variant, $oldStock, $newStock, $link->id));

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
            $barcode = $product['barcode'] ?? null;
            $quantity = $product['quantity'] ?? 1;

            if (! $sku && ! $barcode) {
                continue;
            }

            $link = $this->findVariantLink($order->marketplace_account_id, $sku, $barcode);

            if (! $link || ! $link->variant) {
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
            event(new StockUpdated($link->variant, $oldStock, $newStock, $link->id));

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

            if (! $warehouseSku) {
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

            if (! $warehouse) {
                $warehouse = Warehouse::where('company_id', $variant->company_id)->first();
            }

            if (! $warehouse) {
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
     * Find variant link by Ozon SKU/barcode
     */
    protected function findVariantLink(int $accountId, ?string $sku, ?string $barcode = null): ?VariantMarketplaceLink
    {
        if (! $sku && ! $barcode) {
            return null;
        }

        $query = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $accountId)
            ->where('is_active', true)
            ->with('variant');

        // Strategy 1: Try by marketplace_barcode (приоритетный поиск по баркоду маркетплейса)
        if ($barcode) {
            $link = (clone $query)->where('marketplace_barcode', $barcode)->first();
            if ($link) {
                Log::debug('OzonOrderObserver: Found link by marketplace_barcode', ['barcode' => $barcode, 'link_id' => $link->id]);

                return $link;
            }
        }

        // Strategy 2: Try marketplace_barcode with SKU (sometimes SKU is the barcode)
        if ($sku) {
            $link = (clone $query)->where('marketplace_barcode', $sku)->first();
            if ($link) {
                Log::debug('OzonOrderObserver: Found link by marketplace_barcode (from sku)', ['sku' => $sku, 'link_id' => $link->id]);

                return $link;
            }
        }

        // Strategy 3: Try by external_sku
        if ($sku) {
            $link = (clone $query)->where('external_sku', $sku)->first();
            if ($link) {
                Log::debug('OzonOrderObserver: Found link by external_sku', ['sku' => $sku, 'link_id' => $link->id]);

                return $link;
            }
        }

        // Strategy 4: Try by external_offer_id
        if ($sku) {
            $link = (clone $query)->where('external_offer_id', $sku)->first();
            if ($link) {
                Log::debug('OzonOrderObserver: Found link by external_offer_id', ['sku' => $sku, 'link_id' => $link->id]);

                return $link;
            }
        }

        // Strategy 5: Try by external_sku_id
        if ($sku) {
            $link = (clone $query)->where('external_sku_id', $sku)->first();
            if ($link) {
                Log::debug('OzonOrderObserver: Found link by external_sku_id', ['sku' => $sku, 'link_id' => $link->id]);

                return $link;
            }
        }

        // Strategy 6: Fallback - ищем по внутреннему баркоду варианта
        $searchBarcode = $barcode ?? $sku;
        if ($searchBarcode) {
            $link = (clone $query)
                ->whereHas('variant', function ($q) use ($searchBarcode) {
                    $q->where('barcode', $searchBarcode);
                })
                ->first();
            if ($link) {
                Log::debug('OzonOrderObserver: Found link by variant internal barcode', ['barcode' => $searchBarcode, 'link_id' => $link->id]);

                return $link;
            }
        }

        return null;
    }
}
