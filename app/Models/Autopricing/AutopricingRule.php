<?php

namespace App\Models\Autopricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutopricingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'policy_id',
        'scope_type',
        'scope_id',
        'rule_type',
        'params_json',
        'is_active',
        'priority',
    ];

    protected $casts = [
        'params_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
