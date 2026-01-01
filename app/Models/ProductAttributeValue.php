<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $product_id
 * @property int|null $product_variant_id
 * @property int $attribute_id
 * @property string|null $value_string
 * @property float|null $value_number
 * @property array|null $value_json
 * @property-read Attribute $attribute
 * @property-read Product|null $product
 * @property-read ProductVariant|null $variant
 */
class ProductAttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'product_variant_id',
        'attribute_id',
        'value_string',
        'value_number',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_number' => 'decimal:3',
            'value_json' => 'array',
        ];
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
