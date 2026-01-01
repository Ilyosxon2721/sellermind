<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_order_id',
        'external_sku_id',
        'sku_id',
        'qty',
        'price',
        'payload_json',
    ];

    protected $casts = [
        'qty' => 'decimal:3',
        'price' => 'decimal:2',
        'payload_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        return $query->whereHas('order.channel', function ($q) use ($companyId) {
            $q->where('company_id', $companyId);
        });
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(ChannelOrder::class, 'channel_order_id');
    }
}
