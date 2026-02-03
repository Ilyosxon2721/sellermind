<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $company_id
 * @property int $product_id
 * @property string $sku
 * @property string|null $barcode
 * @property string|null $article_suffix
 * @property string|null $option_values_summary
 * @property float|null $purchase_price
 * @property float|null $price_default
 * @property float|null $old_price_default
 * @property int|null $stock_default
 * @property int|null $weight_g
 * @property int|null $length_mm
 * @property int|null $width_mm
 * @property int|null $height_mm
 * @property int|null $main_image_id
 * @property bool $is_active
 * @property bool $is_deleted
 * @property-read Product $product
 * @property-read ProductImage|null $mainImage
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariantOptionValue> $optionValueLinks
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOptionValue> $optionValues
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductAttributeValue> $attributeValues
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductChannelVariantSetting> $channelVariantSettings
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'product_id',
        'sku',
        'barcode',
        'article_suffix',
        'option_values_summary',
        'purchase_price',
        'price_default',
        'old_price_default',
        'stock_default',
        'weight_g',
        'length_mm',
        'width_mm',
        'height_mm',
        'main_image_id',
        'is_active',
        'is_deleted',
    ];

    protected function casts(): array
    {
        return [
            'purchase_price' => 'decimal:2',
            'price_default' => 'decimal:2',
            'old_price_default' => 'decimal:2',
            'stock_default' => 'integer',
            'weight_g' => 'integer',
            'length_mm' => 'integer',
            'width_mm' => 'integer',
            'height_mm' => 'integer',
            'is_active' => 'boolean',
            'is_deleted' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValueLinks(): HasMany
    {
        return $this->hasMany(ProductVariantOptionValue::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(
            ProductOptionValue::class,
            'product_variant_option_values',
            'product_variant_id',
            'product_option_value_id'
        );
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->whereNull('product_id');
    }

    public function channelVariantSettings(): HasMany
    {
        return $this->hasMany(ProductChannelVariantSetting::class);
    }

    public function mainImage(): BelongsTo
    {
        return $this->belongsTo(ProductImage::class, 'main_image_id');
    }

    public function getDisplayNameAttribute(): string
    {
        $parts = array_filter([$this->option_values_summary, $this->sku]);

        return implode(' • ', $parts);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Связи с маркетплейсами
     */
    public function marketplaceLinks(): HasMany
    {
        return $this->hasMany(VariantMarketplaceLink::class, 'product_variant_id');
    }

    /**
     * Активные связи с маркетплейсами
     */
    public function activeMarketplaceLinks(): HasMany
    {
        return $this->marketplaceLinks()->where('is_active', true);
    }

    /**
     * Получить текущий остаток
     */
    public function getCurrentStock(): int
    {
        return $this->stock_default ?? 0;
    }

    /**
     * Обновить остаток
     *
     * StockUpdated event fires automatically via ProductVariantObserver
     * when stock_default changes. No need to fire it manually here.
     */
    public function updateStock(int $newStock): bool
    {
        return $this->update(['stock_default' => $newStock]);
    }

    /**
     * Уменьшить остаток (при заказе)
     */
    public function decrementStock(int $quantity): bool
    {
        $newStock = max(0, $this->stock_default - $quantity);

        return $this->updateStock($newStock);
    }

    /**
     * Увеличить остаток (при отмене заказа)
     */
    public function incrementStock(int $quantity): bool
    {
        $newStock = $this->stock_default + $quantity;

        return $this->updateStock($newStock);
    }

    /**
     * Уменьшить остаток БЕЗ срабатывания Observer
     * Используется в OrderStockService и ConsumeInSupplyReservations,
     * которые сами создают записи в stock_ledger и вызывают синхронизацию.
     * Обычный decrementStock() вызывает Observer, который создаёт дублирующую запись в ledger.
     */
    public function decrementStockQuietly(int $quantity): bool
    {
        $newStock = max(0, $this->stock_default - $quantity);
        $this->stock_default = $newStock;

        return $this->updateQuietly(['stock_default' => $newStock]);
    }

    /**
     * Увеличить остаток БЕЗ срабатывания Observer
     * Используется в OrderStockService и ConsumeInSupplyReservations,
     * которые сами создают записи в stock_ledger и вызывают синхронизацию.
     */
    public function incrementStockQuietly(int $quantity): bool
    {
        $newStock = $this->stock_default + $quantity;
        $this->stock_default = $newStock;

        return $this->updateQuietly(['stock_default' => $newStock]);
    }

    /**
     * Получить связь с warehouse SKU
     */
    public function warehouseSku(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Warehouse\Sku::class, 'id', 'product_variant_id');
    }

    /**
     * Получить остаток товара на конкретном складе из warehouse системы
     */
    public function getWarehouseStock(int $warehouseId): float
    {
        $warehouseSku = \App\Models\Warehouse\Sku::where('product_variant_id', $this->id)->first();

        if (! $warehouseSku) {
            return 0;
        }

        return \App\Models\Warehouse\StockLedger::where('sku_id', $warehouseSku->id)
            ->where('warehouse_id', $warehouseId)
            ->sum('qty_delta');
    }

    /**
     * Получить общий остаток со всех складов из warehouse системы
     */
    public function getTotalWarehouseStock(): float
    {
        $warehouseSku = \App\Models\Warehouse\Sku::where('product_variant_id', $this->id)->first();

        if (! $warehouseSku) {
            return 0;
        }

        return \App\Models\Warehouse\StockLedger::where('sku_id', $warehouseSku->id)
            ->sum('qty_delta');
    }

    /**
     * Получить доступный остаток (с учётом резервов)
     */
    public function getAvailableWarehouseStock(int $warehouseId): float
    {
        $warehouseSku = \App\Models\Warehouse\Sku::where('product_variant_id', $this->id)->first();

        if (! $warehouseSku) {
            return 0;
        }

        // Текущий остаток
        $currentStock = $this->getWarehouseStock($warehouseId);

        // Активные резервы
        $activeReservations = \App\Models\Warehouse\StockReservation::where('sku_id', $warehouseSku->id)
            ->where('warehouse_id', $warehouseId)
            ->where('status', \App\Models\Warehouse\StockReservation::STATUS_ACTIVE)
            ->sum('qty');

        return max(0, $currentStock - $activeReservations);
    }
}
