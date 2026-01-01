<?php

namespace App\Models\Warehouse;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChannelSkuMap extends Model
{
    use HasFactory;

    protected $fillable = [
        'channel_id',
        'sku_id',
        'external_sku_id',
        'external_offer_id',
        'barcode',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function scopeByCompany($query, int $companyId)
    {
        // channels привязаны к company; join по связи в сервисах
        return $query;
    }
}
