<?php
// file: app/Models/WildberriesProduct.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WildberriesProduct extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'local_product_id',
        'nm_id',
        'imt_id',
        'chrt_id',
        'vendor_code',
        'supplier_article',
        'barcode',
        'title',
        'description',
        'subject_name',
        'subject_id',
        'brand',
        'tech_size',
        'color',
        'price',
        'discount_percent',
        'price_with_discount',
        'spp',
        'stock_total',
        'photos',
        'videos',
        'characteristics',
        'is_active',
        'moderation_status',
        'raw_data',
        'synced_at',
    ];

    protected $casts = [
        'nm_id' => 'integer',
        'imt_id' => 'integer',
        'chrt_id' => 'integer',
        'subject_id' => 'integer',
        'price' => 'decimal:2',
        'discount_percent' => 'integer',
        'price_with_discount' => 'decimal:2',
        'spp' => 'decimal:2',
        'stock_total' => 'integer',
        'photos' => 'array',
        'videos' => 'array',
        'characteristics' => 'array',
        'is_active' => 'boolean',
        'raw_data' => 'array',
        'synced_at' => 'datetime',
    ];

    // ========== Relationships ==========

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function localProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'local_product_id');
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(WildberriesStock::class, 'wildberries_product_id');
    }

    // ========== Helpers ==========

    /**
     * Get primary photo URL
     */
    public function getPrimaryPhotoUrl(): ?string
    {
        if (empty($this->photos)) {
            return null;
        }

        foreach ($this->photos as $photo) {
            if ($photo['is_main'] ?? false) {
                return $photo['url'] ?? null;
            }
        }

        return $this->photos[0]['url'] ?? null;
    }

    /**
     * Calculate total stock across all warehouses
     */
    public function calculateTotalStock(): int
    {
        // Use quantity_full (total stock including reserved) instead of quantity (only available)
        return $this->stocks()->sum('quantity_full');
    }

    /**
     * Update stock_total from related stocks
     */
    public function syncStockTotal(): void
    {
        $this->update(['stock_total' => $this->calculateTotalStock()]);
    }

    /**
     * Get display price (with discount applied)
     */
    public function getDisplayPrice(): float
    {
        return $this->price_with_discount ?? $this->price ?? 0;
    }

    /**
     * Mark product as synced
     */
    public function markSynced(): void
    {
        $this->update(['synced_at' => now()]);
    }

    /**
     * Scope: active products only
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: filter by account
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    /**
     * Scope: with low stock
     */
    public function scopeLowStock($query, int $threshold = 5)
    {
        return $query->where('stock_total', '<=', $threshold);
    }
}
