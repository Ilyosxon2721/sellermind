<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class YandexMarketOrder extends Model
{
    protected $table = 'yandex_market_orders';

    protected $fillable = [
        'marketplace_account_id',
        'order_id',
        'status',
        'status_normalized',
        'substatus',
        'order_data',
        'total_price',
        'currency',
        'customer_name',
        'customer_phone',
        'delivery_type',
        'delivery_service',
        'items_count',
        'created_at_ym',
        'updated_at_ym',
    ];

    protected function casts(): array
    {
        return [
            'order_data' => 'array',
            'total_price' => 'decimal:2',
            'created_at_ym' => 'datetime',
            'updated_at_ym' => 'datetime',
        ];
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Get status label in Russian
     */
    public function getStatusLabel(): string
    {
        return match ($this->status) {
            'PROCESSING' => 'В обработке',
            'DELIVERY' => 'В доставке',
            'PICKUP' => 'Ожидает самовывоз',
            'DELIVERED' => 'Доставлен',
            'CANCELLED' => 'Отменён',
            'RETURNED' => 'Возвращён',
            'UNPAID' => 'Не оплачен',
            'PENDING' => 'Ожидание',
            'RESERVED' => 'Зарезервирован',
            default => $this->status ?? 'Неизвестен',
        };
    }

    /**
     * Get status color for UI
     */
    public function getStatusColor(): string
    {
        return match ($this->status) {
            'PROCESSING', 'RESERVED' => 'blue',
            'DELIVERY' => 'indigo',
            'PICKUP' => 'purple',
            'DELIVERED' => 'green',
            'CANCELLED' => 'red',
            'RETURNED' => 'orange',
            'UNPAID', 'PENDING' => 'yellow',
            default => 'gray',
        };
    }

    /**
     * Get substatus label
     */
    public function getSubstatusLabel(): ?string
    {
        if (!$this->substatus) return null;

        return match ($this->substatus) {
            'STARTED' => 'Подтверждён',
            'READY_TO_SHIP' => 'Готов к отправке',
            'SHIPPED' => 'Отправлен',
            'RESERVATION_EXPIRED' => 'Резерв истёк',
            'USER_NOT_PAID' => 'Не оплачен',
            'USER_UNREACHABLE' => 'Покупатель недоступен',
            'SHOP_FAILED' => 'Ошибка магазина',
            'USER_CHANGED_MIND' => 'Покупатель передумал',
            'DELIVERY_SERVICE_FAILED' => 'Ошибка доставки',
            'PICKUP_EXPIRED' => 'Время самовывоза истекло',
            'PROCESSING_EXPIRED' => 'Время обработки истекло',
            default => $this->substatus,
        };
    }
}
