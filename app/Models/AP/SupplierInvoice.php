<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_PARTIALLY_PAID = 'PARTIALLY_PAID';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'company_id',
        'supplier_id',
        'invoice_no',
        'status',
        'issue_date',
        'due_date',
        'currency_code',
        'exchange_rate',
        'amount_subtotal',
        'amount_tax',
        'amount_total',
        'amount_paid',
        'amount_outstanding',
        'related_type',
        'related_id',
        'notes',
        'confirmed_at',
        'created_by',
        'confirmed_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'confirmed_at' => 'datetime',
        'amount_subtotal' => 'float',
        'amount_tax' => 'float',
        'amount_total' => 'float',
        'amount_paid' => 'float',
        'amount_outstanding' => 'float',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(SupplierInvoiceLine::class, 'invoice_id');
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
