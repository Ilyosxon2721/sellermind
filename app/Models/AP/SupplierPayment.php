<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierPayment extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';

    public const STATUS_POSTED = 'POSTED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'company_id',
        'supplier_id',
        'payment_no',
        'status',
        'paid_at',
        'currency_code',
        'exchange_rate',
        'amount_total',
        'method',
        'reference',
        'comment',
        'created_by',
        'posted_by',
        'posted_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'posted_at' => 'datetime',
        'amount_total' => 'float',
        'exchange_rate' => 'float',
    ];

    public function allocations(): HasMany
    {
        return $this->hasMany(SupplierPaymentAllocation::class, 'payment_id');
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
