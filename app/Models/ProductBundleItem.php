<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Компонент комплекта — связь между продуктом-комплектом и вариантом-компонентом
 *
 * @property int $id
 * @property int $company_id
 * @property int $bundle_product_id
 * @property int $component_variant_id
 * @property int $quantity
 * @property-read Product $bundleProduct
 * @property-read ProductVariant $componentVariant
 */
class ProductBundleItem extends Model
{
    protected $fillable = [
        'company_id',
        'bundle_product_id',
        'component_variant_id',
        'quantity',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
        ];
    }

    public function bundleProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'bundle_product_id');
    }

    public function componentVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'component_variant_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Максимальное количество комплектов, которое можно собрать из этого компонента
     */
    public function getAvailableKits(): int
    {
        $stock = $this->componentVariant->getCurrentStock();

        return $this->quantity > 0 ? intdiv($stock, $this->quantity) : 0;
    }
}
