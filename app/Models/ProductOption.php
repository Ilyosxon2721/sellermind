<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property string $code
 * @property string $name
 * @property string $type
 * @property bool $is_variant_dimension
 * @property-read Product $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOptionValue> $values
 */
class ProductOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'code',
        'name',
        'type',
        'is_variant_dimension',
    ];

    protected function casts(): array
    {
        return [
            'is_variant_dimension' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
