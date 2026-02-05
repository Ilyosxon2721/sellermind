<?php

// file: app/Models/MarketplacePricingRule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplacePricingRule extends Model
{
    public const MODE_FIXED_MARGIN = 'fixed_margin';

    public const MODE_TARGET_ROI = 'target_roi';

    public const MODE_COPY_FROM_CHANNEL = 'copy_from_channel';

    public const MODE_CUSTOM = 'custom';

    public const ROUNDING_NONE = 'none';

    public const ROUNDING_TO_1 = 'to_1';

    public const ROUNDING_TO_10 = 'to_10';

    public const ROUNDING_TO_50 = 'to_50';

    public const ROUNDING_TO_100 = 'to_100';

    public const ROUNDING_TO_9_99 = 'to_9_99';

    protected $fillable = [
        'marketplace_account_id',
        'internal_category_id',
        'product_id',
        'mode',
        'margin_percent',
        'target_roi_percent',
        'price_source',
        'min_price',
        'max_price',
        'rounding_rule',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'margin_percent' => 'float',
            'target_roi_percent' => 'float',
            'min_price' => 'float',
            'max_price' => 'float',
            'is_active' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function internalCategory(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class, 'internal_category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Calculate price based on rule settings
     */
    public function calculatePrice(float $basePrice, float $costPrice = 0): float
    {
        $price = match ($this->mode) {
            self::MODE_FIXED_MARGIN => $basePrice * (1 + ($this->margin_percent ?? 0) / 100),
            self::MODE_TARGET_ROI => $costPrice > 0 ? $costPrice * (1 + ($this->target_roi_percent ?? 0) / 100) : $basePrice,
            default => $basePrice,
        };

        // Apply rounding
        $price = $this->applyRounding($price);

        // Apply min/max constraints
        if ($this->min_price && $price < $this->min_price) {
            $price = $this->min_price;
        }

        if ($this->max_price && $price > $this->max_price) {
            $price = $this->max_price;
        }

        return $price;
    }

    /**
     * Apply rounding rule to price
     */
    protected function applyRounding(float $price): float
    {
        return match ($this->rounding_rule) {
            self::ROUNDING_TO_1 => round($price),
            self::ROUNDING_TO_10 => round($price / 10) * 10,
            self::ROUNDING_TO_50 => round($price / 50) * 50,
            self::ROUNDING_TO_100 => round($price / 100) * 100,
            self::ROUNDING_TO_9_99 => floor($price / 10) * 10 - 0.01, // 199.99, 299.99, etc.
            default => $price,
        };
    }

    /**
     * Find most specific rule for product
     */
    public static function findForProduct(int $accountId, ?int $categoryId, ?int $productId): ?self
    {
        // Most specific: product-level rule
        if ($productId) {
            $rule = self::where('marketplace_account_id', $accountId)
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->first();

            if ($rule) {
                return $rule;
            }
        }

        // Category-level rule
        if ($categoryId) {
            $rule = self::where('marketplace_account_id', $accountId)
                ->where('internal_category_id', $categoryId)
                ->whereNull('product_id')
                ->where('is_active', true)
                ->first();

            if ($rule) {
                return $rule;
            }
        }

        // Account-level default rule
        return self::where('marketplace_account_id', $accountId)
            ->whereNull('internal_category_id')
            ->whereNull('product_id')
            ->where('is_active', true)
            ->first();
    }
}
