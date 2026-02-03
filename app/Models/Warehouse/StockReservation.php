<?php

namespace App\Models\Warehouse;

use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function sku(): BelongsTo
    {
        return $this->belongsTo(Sku::class);
    }
}
