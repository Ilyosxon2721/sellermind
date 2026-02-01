<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Inventory;
use App\Models\InventoryItem;
use App\Models\WarehouseStock;

final class InventoryService
{
    /**
     * Добавить позиции в инвентаризацию на основе складских остатков
     *
     * @param  array<int, int>|null  $productIds
     */
    public function addInventoryItems(Inventory $inventory, ?array $productIds = null): void
    {
        $query = WarehouseStock::where('warehouse_id', $inventory->warehouse_id)
            ->where('quantity', '>', 0)
            ->with('product');

        if ($productIds && count($productIds) > 0) {
            $query->whereIn('product_id', $productIds);
        }

        $stocks = $query->get();

        foreach ($stocks as $stock) {
            InventoryItem::create([
                'inventory_id' => $inventory->id,
                'product_id' => $stock->product_id,
                'expected_quantity' => $stock->quantity,
                'unit_price' => $stock->product->cost_price ?? 0,
                'status' => 'pending',
            ]);
        }

        $inventory->total_items = $stocks->count();
        $inventory->save();
    }
}
