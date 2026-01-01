<?php

namespace App\Observers;

use App\Events\StockUpdated;
use App\Models\ProductVariant;

class ProductVariantObserver
{
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
        // Use wasChanged() to see if 'stock_default' was part of the update.
        if ($variant->wasChanged('stock_default')) {
            // getOriginal() provides the value before the update.
            $oldStock = $variant->getOriginal('stock_default') ?? 0;
            $newStock = $variant->stock_default ?? 0;

            // Fire the event only if the stock value actually changed.
            if ($oldStock !== $newStock) {
                event(new StockUpdated($variant, $oldStock, $newStock));
            }
        }
    }
}