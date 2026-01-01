<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChannelOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'external_order_id',
        'status',
        'payload_json',
        'created_at_channel',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at_channel' => 'datetime',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        // channels привязаны к company; join по связи в сервисах
        return $query;
    }

    public function items(): HasMany
    {
        return $this->hasMany(ChannelOrderItem::class);
    }
}
