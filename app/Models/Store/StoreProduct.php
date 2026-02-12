<?php

namespace App\Models\Store;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreProduct extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'product_id',
        'custom_name',
        'custom_description',
        'custom_price',
        'is_visible',
        'is_featured',
        'position',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'custom_price' => 'decimal:2',
        'position' => 'integer',
    ];

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ==================
    // Methods
    // ==================

    /**
     * Получить отображаемое имя товара (кастомное или оригинальное)
     */
    public function getDisplayName(): string
    {
        return $this->custom_name ?: $this->product->name;
    }

    /**
     * Получить отображаемую цену товара (кастомную или оригинальную)
     */
    public function getDisplayPrice(): float
    {
        return (float) ($this->custom_price ?: $this->product->price);
    }
}
