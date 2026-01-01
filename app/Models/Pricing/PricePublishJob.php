<?php

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricePublishJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'scenario_id',
        'channel_code',
        'status',
        'payload_json',
        'result_json',
        'created_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'result_json' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
