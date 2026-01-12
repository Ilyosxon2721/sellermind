<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'created_by',
        'name',
        'description',
        'type',
        'discount_value',
        'start_date',
        'end_date',
        'is_active',
        'is_automatic',
        'conditions',
        'notify_before_expiry',
        'notify_days_before',
        'expiry_notification_sent_at',
        'products_count',
        'total_revenue_impact',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'is_active' => 'boolean',
        'is_automatic' => 'boolean',
        'notify_before_expiry' => 'boolean',
        'discount_value' => 'decimal:2',
        'total_revenue_impact' => 'decimal:2',
        'conditions' => 'array',
        'expiry_notification_sent_at' => 'datetime',
    ];

    /**
     * Get the company that owns the promotion.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user who created the promotion.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the products in this promotion.
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'promotion_products')
            ->withPivot([
                'original_price',
                'discounted_price',
                'discount_amount',
                'units_sold',
                'revenue_generated',
                'days_since_last_sale',
                'stock_at_promotion_start',
                'turnover_rate_before',
            ])
            ->withTimestamps();
    }

    /**
     * Get the promotion products pivot records.
     */
    public function promotionProducts(): HasMany
    {
        return $this->hasMany(PromotionProduct::class);
    }

    /**
     * Check if promotion is currently active.
     */
    public function isCurrentlyActive(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();
        return $now->between($this->start_date, $this->end_date);
    }

    /**
     * Check if promotion has expired.
     */
    public function hasExpired(): bool
    {
        return now()->isAfter($this->end_date);
    }

    /**
     * Check if promotion is expiring soon.
     */
    public function isExpiringSoon(int $days = null): bool
    {
        $days = $days ?? $this->notify_days_before;
        $threshold = now()->addDays($days);

        return $this->end_date->isBefore($threshold) && !$this->hasExpired();
    }

    /**
     * Calculate discounted price for a given original price.
     */
    public function calculateDiscountedPrice(float $originalPrice): float
    {
        if ($this->type === 'percentage') {
            return $originalPrice * (1 - ($this->discount_value / 100));
        }

        return max(0, $originalPrice - $this->discount_value);
    }

    /**
     * Calculate discount amount for a given original price.
     */
    public function calculateDiscountAmount(float $originalPrice): float
    {
        return $originalPrice - $this->calculateDiscountedPrice($originalPrice);
    }

    /**
     * Get days until expiration.
     */
    public function getDaysUntilExpiration(): int
    {
        return (int) now()->diffInDays($this->end_date, false);
    }

    /**
     * Scope for active promotions.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now());
    }

    /**
     * Scope for automatic promotions.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    /**
     * Scope for expiring soon promotions.
     */
    public function scopeExpiringSoon($query, int $days = 3)
    {
        $threshold = now()->addDays($days);

        return $query->where('is_active', true)
            ->where('end_date', '<=', $threshold)
            ->where('end_date', '>=', now())
            ->whereNull('expiry_notification_sent_at');
    }
}
