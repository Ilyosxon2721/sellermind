<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceShop extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'external_id',
        'name',
        'raw_payload',
    ];

    protected $casts = [
        'raw_payload' => 'array',
    ];

    public function account()
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }
}
