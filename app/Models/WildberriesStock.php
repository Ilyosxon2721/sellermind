<?php
// file: app/Models/WildberriesStock.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WildberriesStock extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'wildberries_product_id',
        'wildberries_warehouse_id',
        'quantity',
        'quantity_full',
        'in_way_to_client',
        'in_way_from_client',
        'reserved',
        'sku',
        'last_change_date',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'quantity_full' => 'integer',
        'in_way_to_client' => 'integer',
        'in_way_from_client' => 'integer',
        'reserved' => 'integer',
        'last_change_date' => 'datetime',
    ];

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(WildberriesProduct::class, 'wildberries_product_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(WildberriesWarehouse::class, 'wildberries_warehouse_id');
    }

    // ========== Helpers ==========

    /**
     * Get available quantity (total minus reserved and in transit)
     */
    public function getAvailableQuantity(): int
    {
        return max(0, $this->quantity - $this->reserved);
    }

    /**
     * Get total quantity including in-transit
     */
    public function getTotalWithInTransit(): int
    {
        return $this->quantity + $this->in_way_to_client + $this->in_way_from_client;
    }

    /**
     * Scope: for account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    /**
     * Scope: for product
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('wildberries_product_id', $productId);
    }

    /**
     * Scope: with stock (quantity > 0)
     */
    public function scopeWithStock($query)
    {
        return $query->where('quantity', '>', 0);
    }
}
