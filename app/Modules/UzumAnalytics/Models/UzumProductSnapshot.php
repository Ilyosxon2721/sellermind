<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель снепшота товара Uzum
 *
 * @property int $id
 * @property int $product_id
 * @property int $category_id
 * @property string $shop_slug
 * @property string $title
 * @property float $price
 * @property float|null $original_price
 * @property float $rating
 * @property int $reviews_count
 * @property int $orders_count
 * @property \Carbon\Carbon $scraped_at
 */
final class UzumProductSnapshot extends Model
{
    protected $table = 'uzum_products_snapshots';

    protected $fillable = [
        'product_id',
        'category_id',
        'shop_slug',
        'title',
        'price',
        'original_price',
        'rating',
        'reviews_count',
        'orders_count',
        'scraped_at',
    ];

    protected $casts = [
        'product_id' => 'integer',
        'category_id' => 'integer',
        'price' => 'decimal:2',
        'original_price' => 'decimal:2',
        'rating' => 'decimal:2',
        'reviews_count' => 'integer',
        'orders_count' => 'integer',
        'scraped_at' => 'datetime',
    ];

    /**
     * Категория товара
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(UzumCategory::class, 'category_id');
    }

    /**
     * Есть ли скидка на товар
     */
    public function hasDiscount(): bool
    {
        return $this->original_price !== null && $this->original_price > $this->price;
    }

    /**
     * Процент скидки
     */
    public function discountPercentage(): float
    {
        if (! $this->hasDiscount()) {
            return 0.0;
        }

        return round((($this->original_price - $this->price) / $this->original_price) * 100, 2);
    }

    /**
     * Scope для фильтрации по товару
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope для фильтрации по категории
     */
    public function scopeForCategory($query, int $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope для фильтрации по магазину
     */
    public function scopeForShop($query, string $shopSlug)
    {
        return $query->where('shop_slug', $shopSlug);
    }

    /**
     * Scope для последних снепшотов
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('scraped_at', '>=', now()->subDays($days));
    }
}
