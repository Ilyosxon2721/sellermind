<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingScenario extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'target_margin_percent',
        'target_profit_fixed',
        'promo_reserve_percent',
        'tax_mode',
        'vat_percent',
        'profit_tax_percent',
        'rounding_mode',
        'rounding_step',
        'is_default',
    ];

    protected $casts = [
        'target_margin_percent' => 'float',
        'target_profit_fixed' => 'float',
        'promo_reserve_percent' => 'float',
        'vat_percent' => 'float',
        'profit_tax_percent' => 'float',
        'rounding_step' => 'float',
        'is_default' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
