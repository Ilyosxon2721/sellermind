<?php

namespace App\Observers;

use App\Events\UzumOrderUpdated;
use App\Models\UzumOrder;
use App\Models\VariantMarketplaceLink;
use Illuminate\Support\Facades\Log;

class UzumOrderObserver
{
    /**
     * Handle the UzumOrder "created" event.
     */
    public function created(UzumOrder $uzumOrder): void
    {
        $this->safeBroadcast($uzumOrder, 'created');

        // Reduce internal stock for linked variants
        $this->reduceInternalStock($uzumOrder);
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

            // Decrease internal stock
            $oldStock = $link->variant->stock_default;
            $link->variant->decrementStock($item->quantity);

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
     * Find VariantMarketplaceLink using multiple lookup strategies
     */
    protected function findVariantLink(int $accountId, ?string $externalOfferId, ?string $barcode): ?VariantMarketplaceLink
    {
        // Strategy 1: Find by external_sku_id (if external_offer_id is a SKU ID)
        if ($externalOfferId) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $accountId)
                ->where('external_sku_id', $externalOfferId)
                ->where('is_active', true)
                ->with('variant')
                ->first();

            if ($link) {
                return $link;
            }
        }

        // Strategy 2: Find by variant barcode (most reliable for Uzum)
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
                return $link;
            }
        }

        // Strategy 3: Find by external_offer_id field
        if ($externalOfferId) {
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $accountId)
                ->where('external_offer_id', $externalOfferId)
                ->where('is_active', true)
                ->with('variant')
                ->first();

            if ($link) {
                return $link;
            }
        }

        return null;
    }
}
