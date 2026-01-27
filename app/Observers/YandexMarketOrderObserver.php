<?php

namespace App\Observers;

use App\Events\MarketplaceOrdersUpdated;
use App\Events\StockUpdated;
use App\Models\YandexMarketOrder;
use App\Models\VariantMarketplaceLink;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use Illuminate\Support\Facades\Log;

class YandexMarketOrderObserver
{
    /**
     * Handle the YandexMarketOrder "created" event.
     *
     * NOTE: Stock reduction is handled by OrderStockService::processOrderStatusChange
     * which is called after order sync. Don't duplicate stock logic here.
     */
    public function created(YandexMarketOrder $order): void
    {
        $this->safeBroadcast($order);
    }

    /**
     * Handle the YandexMarketOrder "updated" event.
     *
     * NOTE: Stock changes (including cancellation) are handled by OrderStockService.
     */
    public function updated(YandexMarketOrder $order): void
    {
        // Only broadcast if important fields changed
        if ($order->wasChanged(['status', 'substatus', 'total_price'])) {
            $this->safeBroadcast($order);
        }
    }

    /**
     * Handle the YandexMarketOrder "deleted" event.
     */
    public function deleted(YandexMarketOrder $order): void
    {
        $this->safeBroadcast($order);
    }

    /**
     * Safely broadcast event without breaking the main flow
     */
    protected function safeBroadcast(YandexMarketOrder $order): void
    {
        try {
            broadcast(new MarketplaceOrdersUpdated(
                $order->account?->company_id ?? 0,
                $order->marketplace_account_id,
                'yandex_market'
            ))->toOthers();
        } catch (\Exception $e) {
            Log::debug('YandexMarketOrderObserver broadcast failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check if order status is cancelled
     */
    protected function isOrderCancelled(?string $status): bool
    {
        return in_array(strtoupper($status ?? ''), [
            'CANCELLED',
            'RETURNED',
        ]);
    }

    /**
     * Reduce internal stock for order products
     */
    protected function reduceInternalStock(YandexMarketOrder $order): void
    {
        $orderData = $order->order_data ?? [];
        $items = $orderData['items'] ?? [];

        foreach ($items as $item) {
            $offerId = $item['offerId'] ?? null;
            $shopSku = $item['shopSku'] ?? null;
            $sku = $item['sku'] ?? null;
            $quantity = $item['count'] ?? 1;

            if (!$offerId && !$shopSku && !$sku) {
                Log::debug('YandexMarket order item has no identifier', [
                    'order_id' => $order->order_id,
                    'item' => $item,
                ]);
                continue;
            }

            // Find linked variant
            $link = $this->findVariantLink($order->marketplace_account_id, $offerId, $shopSku, $sku);

            if (!$link || !$link->variant) {
                Log::info('No linked variant found for YandexMarket order item', [
                    'order_id' => $order->order_id,
                    'offer_id' => $offerId,
                    'shop_sku' => $shopSku,
                ]);
                continue;
            }

            // Decrease internal stock
            $oldStock = $link->variant->stock_default ?? 0;
            $newStock = max(0, $oldStock - $quantity);

            // Update stock_default WITHOUT triggering ProductVariantObserver
            $link->variant->stock_default = $newStock;
            $link->variant->saveQuietly();

            // Create ledger entry for warehouse system
            $this->updateWarehouseStock($link->variant, -$quantity, $order, 'YANDEX_ORDER');

            // Fire StockUpdated event to sync to OTHER marketplaces
            event(new StockUpdated($link->variant, $oldStock, $newStock, $link->id));

            Log::info('Internal stock reduced for YandexMarket order', [
                'order_id' => $order->order_id,
                'variant_id' => $link->variant->id,
                'variant_sku' => $link->variant->sku,
                'offer_id' => $offerId,
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
            ]);
        }
    }

    /**
     * Return stock when order is cancelled
     */
    protected function returnInternalStock(YandexMarketOrder $order): void
    {
        $orderData = $order->order_data ?? [];
        $items = $orderData['items'] ?? [];

        foreach ($items as $item) {
            $offerId = $item['offerId'] ?? null;
            $shopSku = $item['shopSku'] ?? null;
            $sku = $item['sku'] ?? null;
            $quantity = $item['count'] ?? 1;

            if (!$offerId && !$shopSku && !$sku) {
                continue;
            }

            $link = $this->findVariantLink($order->marketplace_account_id, $offerId, $shopSku, $sku);

            if (!$link || !$link->variant) {
                continue;
            }

            // Increase internal stock
            $oldStock = $link->variant->stock_default ?? 0;
            $newStock = $oldStock + $quantity;

            // Update stock_default WITHOUT triggering ProductVariantObserver
            $link->variant->stock_default = $newStock;
            $link->variant->saveQuietly();

            // Create ledger entry for warehouse system
            $this->updateWarehouseStock($link->variant, $quantity, $order, 'YANDEX_ORDER_CANCEL');

            // Fire StockUpdated event to sync to OTHER marketplaces
            event(new StockUpdated($link->variant, $oldStock, $newStock, $link->id));

            Log::info('Internal stock returned for cancelled YandexMarket order', [
                'order_id' => $order->order_id,
                'variant_id' => $link->variant->id,
                'variant_sku' => $link->variant->sku,
                'quantity' => $quantity,
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
            ]);
        }
    }

    /**
     * Update stock in warehouse system (stock_ledger)
     */
    protected function updateWarehouseStock($variant, int $qtyDelta, YandexMarketOrder $order, string $sourceType): void
    {
        try {
            $warehouseSku = Sku::where('product_variant_id', $variant->id)->first();

            if (!$warehouseSku) {
                Log::debug('No warehouse SKU found for variant', [
                    'variant_id' => $variant->id,
                    'sku' => $variant->sku,
                ]);
                return;
            }

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

            StockLedger::create([
                'company_id' => $variant->company_id,
                'occurred_at' => now(),
                'warehouse_id' => $warehouse->id,
                'location_id' => null,
                'sku_id' => $warehouseSku->id,
                'qty_delta' => $qtyDelta,
                'cost_delta' => 0,
                'currency_code' => 'RUB',
                'document_id' => null,
                'document_line_id' => null,
                'source_type' => $sourceType,
                'source_id' => $order->id,
                'created_by' => null,
            ]);

            Log::info('Warehouse stock updated for YandexMarket order', [
                'order_id' => $order->order_id,
                'warehouse_sku_id' => $warehouseSku->id,
                'warehouse_id' => $warehouse->id,
                'qty_delta' => $qtyDelta,
                'source_type' => $sourceType,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to update warehouse stock for YandexMarket order', [
                'variant_id' => $variant->id,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Find variant link by YandexMarket identifiers
     */
    protected function findVariantLink(int $accountId, ?string $offerId, ?string $shopSku, ?string $sku): ?VariantMarketplaceLink
    {
        $query = VariantMarketplaceLink::query()
            ->where('marketplace_account_id', $accountId)
            ->where('is_active', true)
            ->with('variant');

        // Strategy 1: Try by marketplace_barcode (if shopSku is the barcode)
        if ($shopSku) {
            $link = (clone $query)->where('marketplace_barcode', $shopSku)->first();
            if ($link) {
                Log::debug('YandexMarketOrderObserver: Found link by marketplace_barcode', ['shopSku' => $shopSku, 'link_id' => $link->id]);
                return $link;
            }
        }

        // Strategy 2: Try by external_sku (shopSku)
        if ($shopSku) {
            $link = (clone $query)->where('external_sku', $shopSku)->first();
            if ($link) {
                Log::debug('YandexMarketOrderObserver: Found link by external_sku', ['shopSku' => $shopSku, 'link_id' => $link->id]);
                return $link;
            }
        }

        // Strategy 3: Try by external_offer_id (offerId)
        if ($offerId) {
            $link = (clone $query)->where('external_offer_id', $offerId)->first();
            if ($link) {
                Log::debug('YandexMarketOrderObserver: Found link by external_offer_id', ['offerId' => $offerId, 'link_id' => $link->id]);
                return $link;
            }
        }

        // Strategy 4: Try by external_sku_id (sku)
        if ($sku) {
            $link = (clone $query)->where('external_sku_id', $sku)->first();
            if ($link) {
                Log::debug('YandexMarketOrderObserver: Found link by external_sku_id', ['sku' => $sku, 'link_id' => $link->id]);
                return $link;
            }
        }

        // Strategy 5: Fallback - search by variant internal barcode
        $searchValue = $shopSku ?? $sku;
        if ($searchValue) {
            $link = (clone $query)
                ->whereHas('variant', function ($q) use ($searchValue) {
                    $q->where('barcode', $searchValue)->orWhere('sku', $searchValue);
                })
                ->first();
            if ($link) {
                Log::debug('YandexMarketOrderObserver: Found link by variant barcode/sku', ['value' => $searchValue, 'link_id' => $link->id]);
                return $link;
            }
        }

        return null;
    }
}
