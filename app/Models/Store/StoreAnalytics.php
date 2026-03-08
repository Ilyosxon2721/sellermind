<?php

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StoreAnalytics extends Model
{
    public $timestamps = false;

    const CREATED_AT = 'created_at';

    const UPDATED_AT = null;

    protected $fillable = [
        'store_id',
        'date',
        'visits',
        'unique_visitors',
        'page_views',
        'cart_additions',
        'checkouts_started',
        'orders_completed',
        'revenue',
        'average_order',
    ];

    protected $casts = [
        'date' => 'date',
        'visits' => 'integer',
        'unique_visitors' => 'integer',
        'page_views' => 'integer',
        'cart_additions' => 'integer',
        'checkouts_started' => 'integer',
        'orders_completed' => 'integer',
        'revenue' => 'decimal:2',
        'average_order' => 'decimal:2',
    ];

    // ==================
    // Relationships
    // ==================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    // ==================
    // Methods
    // ==================

    /**
     * Получить коэффициент конверсии в процентах
     */
    public function getConversionRate(): float
    {
        if ($this->visits === 0) {
            return 0.0;
        }

        return round($this->orders_completed / $this->visits * 100, 2);
    }
}
