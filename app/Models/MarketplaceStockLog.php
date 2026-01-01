<?php
// file: app/Models/MarketplaceStockLog.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceStockLog extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'marketplace_product_id',
        'wildberries_warehouse_id',
        'direction',
        'status',
        'payload',
        'message',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
