<?php

// file: app/Models/MarketplaceStock.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceStock extends Model
{
    protected $fillable = [
        'marketplace_product_id',
        'warehouse_code',
        'warehouse_name',
        'stock',
        'reserved_stock',
    ];

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
            'reserved_stock' => 'integer',
        ];
    }

    public function marketplaceProduct(): BelongsTo
    {
        return $this->belongsTo(MarketplaceProduct::class, 'marketplace_product_id');
    }

    /**
     * Get available stock (total - reserved)
     */
    public function getAvailableStock(): int
    {
        return max(0, $this->stock - $this->reserved_stock);
    }

    /**
     * Reserve stock for an order
     */
    public function reserve(int $quantity): bool
    {
        if ($this->getAvailableStock() < $quantity) {
            return false;
        }

        $this->increment('reserved_stock', $quantity);

        return true;
    }

    /**
     * Release reserved stock
     */
    public function release(int $quantity): void
    {
        $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
    }

    /**
     * Deduct stock (after order is shipped)
     */
    public function deduct(int $quantity): void
    {
        $this->decrement('stock', min($quantity, $this->stock));
        $this->decrement('reserved_stock', min($quantity, $this->reserved_stock));
    }

    /**
     * Update or create stock for a product-warehouse combination
     */
    public static function updateStock(
        int $marketplaceProductId,
        string $warehouseCode,
        int $stock,
        ?string $warehouseName = null
    ): self {
        return self::updateOrCreate(
            [
                'marketplace_product_id' => $marketplaceProductId,
                'warehouse_code' => $warehouseCode,
            ],
            [
                'stock' => $stock,
                'warehouse_name' => $warehouseName,
            ]
        );
    }
}
