<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_option_id
 * @property string $value
 * @property string|null $code
 * @property string|null $color_hex
 * @property int $sort_order
 * @property-read ProductOption $option
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariant> $variants
 */
class ProductOptionValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_option_id',
        'value',
        'code',
        'color_hex',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'product_variant_option_values',
            'product_option_value_id',
            'product_variant_id'
        );
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
