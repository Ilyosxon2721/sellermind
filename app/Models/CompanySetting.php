<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'allow_negative_stock',
        'costing_method',
        'locations_enabled',
    ];

    protected $casts = [
        'allow_negative_stock' => 'boolean',
        'locations_enabled' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
