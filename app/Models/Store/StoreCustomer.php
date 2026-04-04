<?php

declare(strict_types=1);

namespace App\Models\Store;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Покупатель витрины
 */
class StoreCustomer extends Model
{
    protected $fillable = [
        'store_id',
        'name',
        'phone',
        'email',
        'password_hash',
        'default_city',
        'default_address',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = ['password_hash'];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(StoreOrder::class)->latest();
    }
}
