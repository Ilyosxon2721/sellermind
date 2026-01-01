<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriceCalculation extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'calculated_at',
        'scenario_id',
        'channel_code',
        'sku_id',
        'unit_cost',
        'currency_code',
        'min_price',
        'recommended_price',
        'breakdown_json',
        'confidence',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'unit_cost' => 'float',
        'min_price' => 'float',
        'recommended_price' => 'float',
        'breakdown_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
