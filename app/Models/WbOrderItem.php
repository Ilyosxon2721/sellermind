<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WbOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'wb_order_id',
        'external_offer_id',
        'name',
        'quantity',
        'price',
        'total_price',
        'raw_payload',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(WbOrder::class, 'wb_order_id');
    }
}
