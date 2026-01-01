<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingSkuOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'scenario_id',
        'sku_id',
        'cost_override',
        'min_profit_fixed',
        'target_margin_percent',
        'promo_reserve_percent',
        'is_excluded',
        'meta_json',
    ];

    protected $casts = [
        'cost_override' => 'float',
        'min_profit_fixed' => 'float',
        'target_margin_percent' => 'float',
        'promo_reserve_percent' => 'float',
        'is_excluded' => 'boolean',
        'meta_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
