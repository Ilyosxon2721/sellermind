<?php

namespace App\Models\Replenishment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReplenishmentSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'sku_id',
        'is_enabled',
        'policy',
        'reorder_point',
        'min_qty',
        'max_qty',
        'safety_stock',
        'lead_time_days',
        'review_period_days',
        'demand_window_days',
        'rounding_step',
        'min_order_qty',
        'supplier_id',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'reorder_point' => 'float',
        'min_qty' => 'float',
        'max_qty' => 'float',
        'safety_stock' => 'float',
        'lead_time_days' => 'integer',
        'review_period_days' => 'integer',
        'demand_window_days' => 'integer',
        'rounding_step' => 'float',
        'min_order_qty' => 'float',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
