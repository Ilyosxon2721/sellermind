<?php

// file: app/Models/WildberriesWarehouse.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WildberriesWarehouse extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'warehouse_id',
        'warehouse_name',
        'warehouse_type',
        'office_id',
        'address',
        'city',
        'is_active',
    ];

    protected $casts = [
        'warehouse_id' => 'integer',
        'is_active' => 'boolean',
    ];

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WildberriesStock::class, 'wildberries_warehouse_id');
    }

    // ========== Helpers ==========

    /**
     * Check if warehouse is FBO (Fulfillment by Ozon-like)
     */
    public function isFbo(): bool
    {
        return strtoupper($this->warehouse_type) === 'FBO';
    }

    /**
     * Check if warehouse is FBS (Fulfillment by Seller)
     */
    public function isFbs(): bool
    {
        return strtoupper($this->warehouse_type) === 'FBS';
    }

    /**
     * Get total stock in this warehouse
     */
    public function getTotalStock(): int
    {
        return $this->stocks()->sum('quantity');
    }

    /**
     * Scope: active warehouses
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }
}
