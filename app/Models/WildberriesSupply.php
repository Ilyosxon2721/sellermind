<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WildberriesSupply extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'supply_id',
        'name',
        'status',
        'is_large_cargo',
        'is_cross_border',
        'orders_count',
        'created_at_wb',
        'closed_at',
        'cancelled_at',
        'raw_data',
    ];

    protected $casts = [
        'is_large_cargo' => 'boolean',
        'is_cross_border' => 'boolean',
        'created_at_wb' => 'datetime',
        'closed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'raw_data' => 'array',
    ];

    /**
     * Get the marketplace account that owns the supply
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Get the orders in this supply
     */
    public function orders(): HasMany
    {
        return $this->hasMany(WildberriesOrder::class, 'supply_id', 'supply_id');
    }

    /**
     * Check if supply is active
     */
    public function isActive(): bool
    {
        return $this->status === 'created' || $this->status === 'in_progress';
    }

    /**
     * Check if supply is closed
     */
    public function isClosed(): bool
    {
        return $this->status === 'delivered' || $this->status === 'cancelled';
    }

    /**
     * Scope for active supplies
     */
    public function scopeActive($query)
    {
        return $query->whereIn('status', ['created', 'in_progress']);
    }

    /**
     * Scope for delivered supplies
     */
    public function scopeDelivered($query)
    {
        return $query->where('status', 'delivered');
    }

    /**
     * Scope for cancelled supplies
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }
}
