<?php

namespace App\Models\Autopricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutopricingProposal extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'calculated_at',
        'policy_id',
        'channel_code',
        'sku_id',
        'current_price',
        'min_price',
        'recommended_price',
        'proposed_price',
        'delta_amount',
        'delta_percent',
        'status',
        'reasons_json',
        'safety_flags_json',
        'applied_job_id',
        'applied_at',
        'error_message',
    ];

    protected $casts = [
        'calculated_at' => 'datetime',
        'current_price' => 'float',
        'min_price' => 'float',
        'recommended_price' => 'float',
        'proposed_price' => 'float',
        'delta_amount' => 'float',
        'delta_percent' => 'float',
        'reasons_json' => 'array',
        'safety_flags_json' => 'array',
        'applied_at' => 'datetime',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
