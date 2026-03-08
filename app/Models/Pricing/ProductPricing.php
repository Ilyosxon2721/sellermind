<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Расчёт цен товаров
 *
 * Привязка к компании. Содержит все затраты, рассчитанные комиссии,
 * логистику, эквайринг, рекомендованные и текущие цены, маржинальность.
 */
class ProductPricing extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'marketplace',
        'marketplace_sku',
        'marketplace_category_id',
        'fulfillment_type',
        'cost_price',
        'packaging_cost',
        'delivery_to_warehouse',
        'other_costs',
        'length_cm',
        'width_cm',
        'height_cm',
        'weight_kg',
        'total_cost',
        'commission_amount',
        'logistics_cost',
        'acquiring_amount',
        'storage_cost',
        'total_expenses',
        'recommended_price',
        'current_price',
        'min_price',
        'target_margin_percent',
        'actual_margin_percent',
        'actual_margin_amount',
        'roi_percent',
        'currency',
        'last_calculated_at',
    ];

    protected $casts = [
        'cost_price' => 'decimal:2',
        'packaging_cost' => 'decimal:2',
        'delivery_to_warehouse' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'length_cm' => 'decimal:2',
        'width_cm' => 'decimal:2',
        'height_cm' => 'decimal:2',
        'weight_kg' => 'decimal:3',
        'total_cost' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'logistics_cost' => 'decimal:2',
        'acquiring_amount' => 'decimal:2',
        'storage_cost' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'recommended_price' => 'decimal:2',
        'current_price' => 'decimal:2',
        'min_price' => 'decimal:2',
        'target_margin_percent' => 'decimal:2',
        'actual_margin_percent' => 'decimal:2',
        'actual_margin_amount' => 'decimal:2',
        'roi_percent' => 'decimal:2',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Товар
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Категория маркетплейса
     */
    public function marketplaceCategory(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'marketplace_category_id');
    }

    /**
     * Фильтр по компании
     */
    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Объём товара в литрах (Д * Ш * В / 1000)
     */
    public function getVolumeLitersAttribute(): ?float
    {
        if ($this->length_cm === null || $this->width_cm === null || $this->height_cm === null) {
            return null;
        }

        return round((float) $this->length_cm * (float) $this->width_cm * (float) $this->height_cm / 1000, 3);
    }

    /**
     * Общая себестоимость (сумма всех затратных полей)
     */
    public function getTotalCostPriceAttribute(): float
    {
        return round(
            (float) $this->cost_price
            + (float) $this->packaging_cost
            + (float) $this->delivery_to_warehouse
            + (float) $this->other_costs,
            2,
        );
    }

    /**
     * Товар прибыльный (маржа > 0)
     */
    public function isProfitable(): bool
    {
        return $this->actual_margin_amount !== null && (float) $this->actual_margin_amount > 0;
    }

    /**
     * Цвет маржинальности для UI
     *
     * - red — убыточный (< 0%)
     * - orange — низкая маржа (0-10%)
     * - yellow — средняя маржа (10-20%)
     * - green — хорошая маржа (> 20%)
     */
    public function getMarginColorAttribute(): string
    {
        $margin = (float) ($this->actual_margin_percent ?? 0);

        if ($margin < 0) {
            return 'red';
        }

        if ($margin < 10) {
            return 'orange';
        }

        if ($margin < 20) {
            return 'yellow';
        }

        return 'green';
    }
}
