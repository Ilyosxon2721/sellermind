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
        broadcast(new UzumOrderUpdated($uzumOrder, 'created'))->toOthers();
        
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
            broadcast(new UzumOrderUpdated($uzumOrder, 'updated'))->toOthers();
        }
    }

    /**
     * Handle the UzumOrder "deleted" event.
     */
    public function deleted(UzumOrder $uzumOrder): void
    {
        broadcast(new UzumOrderUpdated($uzumOrder, 'deleted'))->toOthers();
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
            if (!$item->external_offer_id) {
                continue;
            }
            
            // Find linked variant by SKU ID (external_offer_id in Uzum is the SKU ID)
            $link = VariantMarketplaceLink::query()
                ->where('marketplace_account_id', $order->marketplace_account_id)
                ->where('external_sku_id', $item->external_offer_id)
                ->where('is_active', true)
                ->with('variant')
                ->first();
            
            if (!$link || !$link->variant) {
                Log::info('No linked variant found for Uzum order item', [
                    'order_id' => $order->external_order_id,
                    'sku_id' => $item->external_offer_id,
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
                'sku_id' => $item->external_offer_id,
                'quantity' => $item->quantity,
                'old_stock' => $oldStock,
                'new_stock' => $link->variant->stock_default,
            ]);
        }
    }
}
