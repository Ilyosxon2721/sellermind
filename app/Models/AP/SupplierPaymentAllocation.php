<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPaymentAllocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'invoice_id',
        'amount_allocated',
    ];

    protected $casts = [
        'amount_allocated' => 'float',
    ];

    public function payment(): BelongsTo
    {
        return $this->belongsTo(SupplierPayment::class, 'payment_id');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'invoice_id');
    }
}
