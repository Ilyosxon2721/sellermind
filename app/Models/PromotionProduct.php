<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class PromotionProduct extends Pivot
{
    protected $table = 'promotion_products';

    public $incrementing = true;

    protected $fillable = [
        'promotion_id',
        'product_variant_id',
        'original_price',
        'discounted_price',
        'discount_amount',
        'units_sold',
        'revenue_generated',
        'days_since_last_sale',
        'stock_at_promotion_start',
        'turnover_rate_before',
    ];

    protected $casts = [
        'original_price' => 'decimal:2',
        'discounted_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'revenue_generated' => 'decimal:2',
        'turnover_rate_before' => 'decimal:4',
    ];

    /**
     * Get the promotion.
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Get the product variant.
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    /**
     * Calculate ROI (Return on Investment).
     */
    public function calculateROI(): float
    {
        if ($this->discount_amount <= 0) {
            return 0;
        }

        $totalDiscount = $this->discount_amount * $this->units_sold;
        if ($totalDiscount <= 0) {
            return 0;
        }

        return ($this->revenue_generated / $totalDiscount) * 100;
    }

    /**
     * Check if promotion is performing well for this product.
     */
    public function isPerformingWell(): bool
    {
        // Performance criteria:
        // - At least 5 units sold
        // - ROI > 150% (generated at least 1.5x the discount given)
        return $this->units_sold >= 5 && $this->calculateROI() > 150;
    }
}
