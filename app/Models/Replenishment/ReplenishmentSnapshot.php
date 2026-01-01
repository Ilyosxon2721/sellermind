<?php

namespace App\Models\Replenishment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplenishmentSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'sku_id',
        'calculated_at',
        'on_hand',
        'reserved',
        'available',
        'avg_daily_demand',
        'lead_time_days',
        'safety_stock',
        'reorder_qty',
        'risk_level',
        'next_stockout_date',
        'meta_json',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'next_stockout_date' => 'date',
        'meta_json' => 'array',
        'on_hand' => 'float',
        'reserved' => 'float',
        'available' => 'float',
        'avg_daily_demand' => 'float',
        'safety_stock' => 'float',
        'reorder_qty' => 'float',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
