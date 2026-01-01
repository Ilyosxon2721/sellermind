<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockReservation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_RELEASED = 'RELEASED';
    public const STATUS_CONSUMED = 'CONSUMED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'company_id',
        'warehouse_id',
        'sku_id',
        'qty',
        'status',
        'reason',
        'source_type',
        'source_id',
        'expires_at',
        'created_by',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'expires_at' => 'datetime',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
