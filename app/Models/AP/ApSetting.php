<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'base_currency_code',
        'allow_overpayment',
    ];

    protected $casts = [
        'allow_overpayment' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
