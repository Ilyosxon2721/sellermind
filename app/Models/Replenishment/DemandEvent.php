<?php

namespace App\Models\Replenishment;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemandEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'sku_id',
        'occurred_at',
        'qty',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'qty' => 'float',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
