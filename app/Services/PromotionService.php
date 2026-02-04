<?php

namespace App\Services;

use App\Models\Company;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PromotionService
{
    /**
     * Detect slow-moving products for a company.
     */
    public function detectSlowMovingProducts(int $companyId, array $criteria = []): Collection
    {
        $minDaysNoSale = $criteria['min_days_no_sale'] ?? 30;
        $minStock = $criteria['min_stock'] ?? 5;
        $minPrice = $criteria['min_price'] ?? 100;

        // Get variants with their last sale date
        $variants = ProductVariant::whereHas('product', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('is_active', true)
            ->where('stock_default', '>=', $minStock)
            ->where('price', '>=', $minPrice)
            ->with(['product'])
            ->get();

        $slowMoving = collect();

        foreach ($variants as $variant) {
            $daysSinceLastSale = $this->getDaysSinceLastSale($variant);
            $turnoverRate = $this->calculateTurnoverRate($variant);

            if ($daysSinceLastSale >= $minDaysNoSale || $turnoverRate < 0.1) {
                $slowMoving->push([
                    'variant' => $variant,
                    'days_since_last_sale' => $daysSinceLastSale,
                    'turnover_rate' => $turnoverRate,
                    'stock' => $variant->stock_default,
                    'recommended_discount' => $this->calculateRecommendedDiscount($daysSinceLastSale, $turnoverRate),
                ]);
            }
        }

        return $slowMoving->sortByDesc('days_since_last_sale');
    }

    /**
     * Get days since last sale for a variant.
     */
    protected function getDaysSinceLastSale(ProductVariant $variant): int
    {
        // Check marketplace orders
        $lastSaleDate = DB::table('marketplace_order_items')
            ->where('product_variant_id', $variant->id)
            ->max('created_at');

        if (! $lastSaleDate) {
            // If no sales found, assume it's been 365 days
            return 365;
        }

        return now()->diffInDays($lastSaleDate);
    }

    /**
     * Calculate turnover rate (sales per day).
     */
    protected function calculateTurnoverRate(ProductVariant $variant, int $days = 90): float
    {
        $salesCount = DB::table('marketplace_order_items')
            ->where('product_variant_id', $variant->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->sum('quantity');

        return $salesCount / $days;
    }

    /**
     * Calculate recommended discount percentage.
     */
    protected function calculateRecommendedDiscount(int $daysSinceLastSale, float $turnoverRate): int
    {
        // Base discount on urgency
        if ($daysSinceLastSale >= 180) {
            return 50; // Very slow, aggressive discount
        } elseif ($daysSinceLastSale >= 90) {
            return 35; // Quite slow
        } elseif ($daysSinceLastSale >= 60) {
            return 25; // Moderately slow
        } elseif ($daysSinceLastSale >= 30) {
            return 15; // Slightly slow
        }

        return 10; // Minimum discount
    }

    /**
     * Create automatic promotion for slow-moving products.
     */
    public function createAutomaticPromotion(
        Company $company,
        array $slowMovingProducts,
        array $options = []
    ): Promotion {
        $duration = $options['duration_days'] ?? 30;
        $maxDiscount = $options['max_discount'] ?? 50;
        $userId = $options['user_id'] ?? null;

        DB::beginTransaction();

        try {
            // Create promotion
            $promotion = Promotion::create([
                'company_id' => $company->id,
                'created_by' => $userId,
                'name' => 'Автоакция '.now()->format('d.m.Y'),
                'description' => 'Автоматическая акция на медленно движущиеся товары',
                'type' => 'percentage',
                'discount_value' => $maxDiscount,
                'start_date' => now(),
                'end_date' => now()->addDays($duration),
                'is_active' => true,
                'is_automatic' => true,
                'conditions' => [
                    'min_days_no_sale' => 30,
                    'min_stock' => 5,
                    'max_discount' => $maxDiscount,
                ],
                'notify_before_expiry' => true,
                'notify_days_before' => 3,
            ]);

            // Attach products
            foreach ($slowMovingProducts as $item) {
                $variant = $item['variant'];
                $discountPercent = min($item['recommended_discount'], $maxDiscount);

                $originalPrice = (float) $variant->price;
                $discountedPrice = $originalPrice * (1 - ($discountPercent / 100));
                $discountAmount = $originalPrice - $discountedPrice;

                $promotion->products()->attach($variant->id, [
                    'original_price' => $originalPrice,
                    'discounted_price' => $discountedPrice,
                    'discount_amount' => $discountAmount,
                    'days_since_last_sale' => $item['days_since_last_sale'],
                    'stock_at_promotion_start' => $variant->stock_default,
                    'turnover_rate_before' => $item['turnover_rate'],
                ]);
            }

            // Update products count
            $promotion->update(['products_count' => count($slowMovingProducts)]);

            DB::commit();

            return $promotion;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Apply promotion to variant (update price).
     */
    public function applyPromotionToVariant(Promotion $promotion, ProductVariant $variant): bool
    {
        $promotionProduct = $promotion->promotionProducts()
            ->where('product_variant_id', $variant->id)
            ->first();

        if (! $promotionProduct) {
            return false;
        }

        // Update variant price to discounted price
        $variant->update([
            'price' => $promotionProduct->discounted_price,
        ]);

        return true;
    }

    /**
     * Remove promotion from variant (restore original price).
     */
    public function removePromotionFromVariant(Promotion $promotion, ProductVariant $variant): bool
    {
        $promotionProduct = $promotion->promotionProducts()
            ->where('product_variant_id', $variant->id)
            ->first();

        if (! $promotionProduct) {
            return false;
        }

        // Restore original price
        $variant->update([
            'price' => $promotionProduct->original_price,
        ]);

        return true;
    }

    /**
     * Apply promotion to all products.
     */
    public function applyPromotion(Promotion $promotion): int
    {
        $count = 0;

        foreach ($promotion->promotionProducts as $promotionProduct) {
            $variant = $promotionProduct->variant;
            if ($variant) {
                $variant->update([
                    'price' => $promotionProduct->discounted_price,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Remove promotion from all products (restore prices).
     */
    public function removePromotion(Promotion $promotion): int
    {
        $count = 0;

        foreach ($promotion->promotionProducts as $promotionProduct) {
            $variant = $promotionProduct->variant;
            if ($variant) {
                $variant->update([
                    'price' => $promotionProduct->original_price,
                ]);
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get promotion statistics.
     */
    public function getPromotionStats(Promotion $promotion): array
    {
        $products = $promotion->promotionProducts;

        return [
            'total_products' => $products->count(),
            'total_units_sold' => $products->sum('units_sold'),
            'total_revenue' => $products->sum('revenue_generated'),
            'total_discount_given' => $products->sum(function ($p) {
                return $p->discount_amount * $p->units_sold;
            }),
            'average_roi' => $products->avg(function ($p) {
                return $p->calculateROI();
            }),
            'performing_well_count' => $products->filter(function ($p) {
                return $p->isPerformingWell();
            })->count(),
        ];
    }

    /**
     * Update sales stats for promotion products.
     * This should be called when orders are created/updated.
     */
    public function updateSalesStats(ProductVariant $variant, int $quantity, float $price): void
    {
        // Find active promotions for this variant
        $promotionProducts = PromotionProduct::whereHas('promotion', function ($query) {
            $query->active();
        })
            ->where('product_variant_id', $variant->id)
            ->get();

        foreach ($promotionProducts as $promotionProduct) {
            $promotionProduct->increment('units_sold', $quantity);
            $promotionProduct->increment('revenue_generated', $price * $quantity);
        }
    }
}
