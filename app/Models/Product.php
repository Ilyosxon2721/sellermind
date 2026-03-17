<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $article
 * @property string|null $brand_name
 * @property int|null $category_id
 * @property string|null $description_short
 * @property string|null $description_full
 * @property string|null $country_of_origin
 * @property string|null $manufacturer
 * @property string|null $unit
 * @property string|null $care_instructions
 * @property string|null $composition
 * @property int|null $package_weight_g
 * @property int|null $package_length_mm
 * @property int|null $package_width_mm
 * @property int|null $package_height_mm
 * @property bool $is_active
 * @property bool $is_archived
 * @property bool $is_bundle
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductBundleItem> $bundleItems
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductVariant> $variants
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductOption> $options
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductImage> $images
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductAttributeValue> $attributeValues
 * @property-read \Illuminate\Database\Eloquent\Collection<int, ProductChannelSetting> $channelSettings
 */
class Product extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'article',
        'brand_name',
        'category_id',
        'description_short',
        'description_full',
        'country_of_origin',
        'manufacturer',
        'unit',
        'care_instructions',
        'composition',
        'package_weight_g',
        'package_length_mm',
        'package_width_mm',
        'package_height_mm',
        'is_active',
        'is_archived',
        'is_bundle',
        'risment_product_id',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'package_weight_g' => 'integer',
            'package_length_mm' => 'integer',
            'package_width_mm' => 'integer',
            'package_height_mm' => 'integer',
            'is_active' => 'boolean',
            'is_archived' => 'boolean',
            'is_bundle' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function variantsActive(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->where('is_active', true);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function mainImage(): HasOne
    {
        return $this->hasOne(ProductImage::class)->where('is_main', true);
    }

    public function attributeValues(): HasMany
    {
        return $this->hasMany(ProductAttributeValue::class)->whereNull('product_variant_id');
    }

    public function channelSettings(): HasMany
    {
        return $this->hasMany(ProductChannelSetting::class);
    }

    /**
     * Компоненты комплекта (только для is_bundle=true)
     */
    public function bundleItems(): HasMany
    {
        return $this->hasMany(ProductBundleItem::class, 'bundle_product_id');
    }

    /**
     * Рассчитать доступный остаток комплекта
     * Остаток = min(stock_компонента / количество_в_комплекте) по всем компонентам
     */
    public function getBundleStock(): int
    {
        if (! $this->is_bundle) {
            return 0;
        }

        $items = $this->bundleItems()->with('componentVariant')->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $minKits = PHP_INT_MAX;

        foreach ($items as $item) {
            $stock = $item->componentVariant->getCurrentStock();
            $kits = $item->quantity > 0 ? intdiv($stock, $item->quantity) : 0;
            $minKits = min($minKits, $kits);
        }

        return $minKits === PHP_INT_MAX ? 0 : $minKits;
    }

    /**
     * Списать компоненты комплекта при продаже
     *
     * @param int $bundleQty Количество проданных комплектов
     * @return array Результаты списания по каждому компоненту
     */
    public function deductBundleStock(int $bundleQty): array
    {
        if (! $this->is_bundle || $bundleQty <= 0) {
            return [];
        }

        $results = [];
        $items = $this->bundleItems()->with('componentVariant')->get();

        foreach ($items as $item) {
            $deductQty = $item->quantity * $bundleQty;
            $variant = $item->componentVariant;
            $oldStock = $variant->getCurrentStock();
            $variant->decrementStock($deductQty);
            $results[] = [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'deducted' => $deductQty,
                'old_stock' => $oldStock,
                'new_stock' => $variant->getCurrentStock(),
            ];
        }

        return $results;
    }

    /**
     * Вернуть компоненты комплекта при отмене продажи
     *
     * @param int $bundleQty Количество возвращённых комплектов
     * @return array Результаты возврата
     */
    public function returnBundleStock(int $bundleQty): array
    {
        if (! $this->is_bundle || $bundleQty <= 0) {
            return [];
        }

        $results = [];
        $items = $this->bundleItems()->with('componentVariant')->get();

        foreach ($items as $item) {
            $returnQty = $item->quantity * $bundleQty;
            $variant = $item->componentVariant;
            $oldStock = $variant->getCurrentStock();
            $variant->incrementStock($returnQty);
            $results[] = [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'returned' => $returnQty,
                'old_stock' => $oldStock,
                'new_stock' => $variant->getCurrentStock(),
            ];
        }

        return $results;
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeBundles(Builder $query): Builder
    {
        return $query->where('is_bundle', true);
    }

    public function scopeNotBundles(Builder $query): Builder
    {
        return $query->where('is_bundle', false);
    }
}
