<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sku extends Model
{
    use HasFactory;

    protected $table = 'skus';

    protected $fillable = [
        'product_id',
        'product_variant_id',
        'company_id',
        'sku_code',
        'barcode_ean13',
        'attributes_json',
        'weight_g',
        'length_mm',
        'width_mm',
        'height_mm',
        'is_active',
    ];

    protected $casts = [
        'attributes_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\ProductVariant::class);
    }

    public function stockLedger(): HasMany
    {
        return $this->hasMany(StockLedger::class, 'sku_id');
    }

    /**
     * Получить текущий остаток на складе
     *
     * @param int|null $warehouseId
     * @return float
     */
    public function getCurrentBalance(?int $warehouseId = null): float
    {
        $query = StockLedger::where('sku_id', $this->id);

        if ($warehouseId) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $query->sum('qty_delta');
    }
}
