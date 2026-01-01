<?php

namespace App\Models\Autopricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutopricingPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'is_active',
        'channel_code',
        'scenario_id',
        'mode',
        'priority',
        'cooldown_hours',
        'max_changes_per_day',
        'max_delta_percent',
        'max_delta_amount',
        'min_price_guard',
        'max_price_guard',
        'max_price_value',
        'comment',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'max_price_guard' => 'boolean',
        'min_price_guard' => 'boolean',
        'max_delta_percent' => 'float',
        'max_delta_amount' => 'float',
        'max_price_value' => 'float',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
