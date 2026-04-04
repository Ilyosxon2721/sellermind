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
     * Получить реальный остаток со всех складов (из warehouse системы)
     *
     * Используется кэшированное значение если загружено через loadWarehouseStocks()
     */
    public function getWarehouseStock(): float
    {
        // Если уже загружен batch-запросом
        if (isset($this->attributes['_warehouse_stock'])) {
            return (float) $this->attributes['_warehouse_stock'];
        }

        // Fallback — загрузить для одного товара
        $variantIds = $this->product?->variants?->pluck('id') ?? collect();

        if ($variantIds->isEmpty()) {
            return 0;
        }

        return (float) \Illuminate\Support\Facades\DB::table('stock_ledger')
            ->join('skus', 'skus.id', '=', 'stock_ledger.sku_id')
            ->whereIn('skus.product_variant_id', $variantIds)
            ->sum('stock_ledger.qty_delta');
    }

    /**
     * Batch-загрузка остатков для коллекции StoreProduct
     *
     * Один SQL-запрос вместо N+1
     *
     * @param  \Illuminate\Support\Collection<int, StoreProduct>  $storeProducts
     */
    public static function loadWarehouseStocks($storeProducts): void
    {
        // Собираем все product_id → variant_ids
        $productIds = $storeProducts->pluck('product_id')->unique()->filter();

        if ($productIds->isEmpty()) {
            return;
        }

        // Один запрос: product_id → SUM(qty_delta)
        $stocks = \Illuminate\Support\Facades\DB::table('stock_ledger')
            ->join('skus', 'skus.id', '=', 'stock_ledger.sku_id')
            ->join('product_variants', 'product_variants.id', '=', 'skus.product_variant_id')
            ->whereIn('product_variants.product_id', $productIds)
            ->groupBy('product_variants.product_id')
            ->selectRaw('product_variants.product_id, SUM(stock_ledger.qty_delta) as total_stock')
            ->pluck('total_stock', 'product_id');

        // Записываем в атрибуты каждого StoreProduct
        foreach ($storeProducts as $sp) {
            $sp->attributes['_warehouse_stock'] = $stocks->get($sp->product_id, 0);
        }
    }

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

        // Подгружаем варианты если не загружены
        if (! $this->relationLoaded('product')) {
            $this->load('product.variants');
        } elseif ($this->product && ! $this->product->relationLoaded('variants')) {
            $this->product->load('variants');
        }

        return (float) ($this->product?->variants?->first()?->price_default ?? 0);
    }
}
