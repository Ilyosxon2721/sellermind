<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UzumOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace_account_id',
        'external_order_id',
        'status',
        'status_normalized',
        'uzum_status', // Оригинальный статус из API Uzum
        'delivery_type',
        'shop_id',
        'customer_name',
        'customer_phone',
        'total_amount',
        'currency',
        'ordered_at',
        'delivered_at',
        'delivery_address_full',
        'delivery_city',
        'delivery_street',
        'delivery_home',
        'delivery_flat',
        'delivery_longitude',
        'delivery_latitude',
        'raw_payload',
        // Stock tracking fields
        'stock_status',
        'stock_reserved_at',
        'stock_sold_at',
        'stock_released_at',
    ];

    protected $casts = [
        'ordered_at' => 'datetime',
        'delivered_at' => 'datetime',
        'raw_payload' => 'array',
        'total_amount' => 'decimal:2',
        'stock_reserved_at' => 'datetime',
        'stock_sold_at' => 'datetime',
        'stock_released_at' => 'datetime',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UzumOrderItem::class);
    }

    /**
     * Получить корректную дату заказа из оригинального timestamp API
     *
     * MySQL хранит datetime без timezone. Старые записи хранят UTC вместо local time
     * из-за бага в Carbon::createFromTimestampMs() без параметра timezone.
     * Этот метод вычисляет правильную дату из raw_payload (оригинальный API ответ).
     */
    public function resolvedOrderedAt(): ?Carbon
    {
        $payload = $this->raw_payload ?? [];
        $dateCreated = $payload['dateCreated'] ?? null;

        if (is_numeric($dateCreated)) {
            return Carbon::createFromTimestampMs((int) $dateCreated, config('app.timezone'));
        }

        return $this->ordered_at;
    }
}
