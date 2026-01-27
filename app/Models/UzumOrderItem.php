<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UzumOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uzum_order_id',
        'external_offer_id',
        'name',
        'quantity',
        'price',
        'total_price',
        'raw_payload',
    ];

    /**
     * Get barcode from raw_payload
     */
    public function getBarcodeAttribute(): ?string
    {
        return $this->raw_payload['barcode'] ?? null;
    }

    /**
     * Get skuId from raw_payload (for variant matching)
     */
    public function getSkuIdAttribute(): ?string
    {
        return $this->raw_payload['skuId'] ?? $this->external_offer_id;
    }

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'raw_payload' => 'array',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(UzumOrder::class, 'uzum_order_id');
    }
}
