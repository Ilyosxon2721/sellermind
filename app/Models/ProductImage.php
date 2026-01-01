<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property int|null $variant_id
 * @property string $file_path
 * @property string|null $alt_text
 * @property bool $is_main
 * @property int $sort_order
 * @property-read Product $product
 * @property-read ProductVariant|null $variant
 */
class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'variant_id',
        'file_path',
        'alt_text',
        'is_main',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_main' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
