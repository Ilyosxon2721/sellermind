<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderConfirmLog extends Model
{
    protected $fillable = [
        'uzum_order_id',
        'status',
        'error_message',
        'error_code',
        'confirmed_at',
    ];

    protected $casts = [
        'confirmed_at' => 'datetime',
    ];
}
