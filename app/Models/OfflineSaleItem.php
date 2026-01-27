<?php

namespace App\Models;

use App\Models\Warehouse\Sku;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfflineSaleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'offline_sale_id',
        'sku_id',
        'product_id',
        'sku_code',
        'product_name',
        'description',
        'quantity',
        'unit_price',
        'unit_cost',
        'discount_percent',
        'discount_amount',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
            'unit_price' => 'decimal:2',
            'unit_cost' => 'decimal:2',
            'discount_percent' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'line_total' => 'decimal:2',
        ];
    }

    // ========== Relationships ==========

    public function sale(): BelongsTo
    {
        return $this->belongsTo(OfflineSale::class, 'offline_sale_id');
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    // ========== Helpers ==========

    public function calculateLineTotal(): float
    {
        $subtotal = $this->quantity * $this->unit_price;
        $discount = $this->discount_amount ?: ($subtotal * ($this->discount_percent / 100));
        return $subtotal - $discount;
    }

    public function getProfit(): float
    {
        $totalCost = $this->quantity * $this->unit_cost;
        return $this->line_total - $totalCost;
    }

    public function getProfitMargin(): float
    {
        if ($this->line_total <= 0) {
            return 0;
        }
        return ($this->getProfit() / $this->line_total) * 100;
    }

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($item) {
            // Auto-calculate line_total if not set
            if (!$item->line_total) {
                $item->line_total = $item->calculateLineTotal();
            }
        });

        static::saved(function ($item) {
            // Recalculate parent sale totals
            $item->sale?->recalculateTotals();
        });

        static::deleted(function ($item) {
            // Recalculate parent sale totals
            $item->sale?->recalculateTotals();
        });
    }
}
