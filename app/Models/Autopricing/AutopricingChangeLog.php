<?php

namespace App\Models\Autopricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutopricingChangeLog extends Model
{
    use HasFactory;

    protected $table = 'autopricing_change_log';

    protected $fillable = [
        'company_id',
        'proposal_id',
        'channel_code',
        'sku_id',
        'old_price',
        'new_price',
        'applied_by',
        'applied_by_system',
        'method',
        'payload_json',
    ];

    protected $casts = [
        'old_price' => 'float',
        'new_price' => 'float',
        'applied_by_system' => 'boolean',
        'payload_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
