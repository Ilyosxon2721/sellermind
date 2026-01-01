<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        // Units are global, but keep signature
        return $query;
    }
}
