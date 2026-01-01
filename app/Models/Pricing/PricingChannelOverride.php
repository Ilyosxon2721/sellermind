<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingChannelOverride extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'scenario_id',
        'channel_code',
        'override_target_margin_percent',
        'override_promo_reserve_percent',
        'override_rounding_step',
        'meta_json',
    ];

    protected $casts = [
        'override_target_margin_percent' => 'float',
        'override_promo_reserve_percent' => 'float',
        'override_rounding_step' => 'float',
        'meta_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
