<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WarehouseLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'warehouse_id',
        'code',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->whereHas('warehouse', fn($q) => $q->where('company_id', $companyId));
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }
}
