<?php

namespace App\Models\Store;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'custom_old_price',
        'is_visible',
        'is_featured',
        'position',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
        'is_featured' => 'boolean',
        'custom_price' => 'decimal:2',
        'custom_old_price' => 'decimal:2',
        'position' => 'integer',
    ];

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(StoreReview::class)->latest();
    }

    public function approvedReviews(): HasMany
    {
        return $this->hasMany(StoreReview::class)
            ->where('is_approved', true)
            ->latest();
    }

    /**
     * Средний рейтинг одобренных отзывов
     */
    public function getAverageRatingAttribute(): ?float
    {
        $avg = $this->approvedReviews()->avg('rating');

        return $avg !== null ? round((float) $avg, 1) : null;
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
     * Получить отображаемую цену товара (кастомную или из варианта)
     */
    public function getDisplayPrice(): float
    {
        if ($this->custom_price > 0) {
            return (float) $this->custom_price;
        }

        return (float) ($this->product?->variants?->first()?->price_default ?? 0);
    }
}
