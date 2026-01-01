<?php

namespace App\Models\Autopricing;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AutopricingDailyCounter extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'date',
        'policy_id',
        'channel_code',
        'changes_count',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
