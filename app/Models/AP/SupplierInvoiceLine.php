<?php

namespace App\Models\AP;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierInvoiceLine extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'sku_id',
        'description',
        'qty',
        'unit_cost',
        'amount_line',
        'meta_json',
    ];

    protected $casts = [
        'qty' => 'float',
        'unit_cost' => 'float',
        'amount_line' => 'float',
        'meta_json' => 'array',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'invoice_id');
    }
}
