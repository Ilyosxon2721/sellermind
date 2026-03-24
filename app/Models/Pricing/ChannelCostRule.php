<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Model;

final class ChannelCostRule extends Model
{
    protected $fillable = [
        'company_id',
        'channel_code',
        'name',
        'commission_percent',
        'commission_fixed',
        'logistics_fixed',
        'return_logistics_fixed',
        'payment_fee_percent',
        'return_percent',
        'storage_cost_per_day',
        'vat_percent',
        'turnover_tax_percent',
        'profit_tax_percent',
        'other_percent',
        'other_fixed',
        'comment',
    ];

    protected $casts = [
        'commission_percent' => 'float',
        'commission_fixed' => 'float',
        'logistics_fixed' => 'float',
        'return_logistics_fixed' => 'float',
        'payment_fee_percent' => 'float',
        'return_percent' => 'float',
        'storage_cost_per_day' => 'float',
        'vat_percent' => 'float',
        'turnover_tax_percent' => 'float',
        'profit_tax_percent' => 'float',
        'other_percent' => 'float',
        'other_fixed' => 'float',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
