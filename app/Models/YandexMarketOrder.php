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
        // Stock tracking fields
        'stock_status',
        'stock_reserved_at',
        'stock_sold_at',
        'stock_released_at',
    ];

    protected function casts(): array
    {
        return [
            'order_data' => 'array',
            'total_price' => 'decimal:2',
            'created_at_ym' => 'datetime',
            'updated_at_ym' => 'datetime',
            'stock_reserved_at' => 'datetime',
            'stock_sold_at' => 'datetime',
            'stock_released_at' => 'datetime',
        ];
    }

    // ========== Stock Status Methods ==========

    /**
     * Check if order is a completed sale (revenue)
     * stock_sold_at is set when order reaches DELIVERED status
     */
    public function isSold(): bool
    {
        return $this->stock_status === 'sold' && $this->stock_sold_at !== null;
    }

    /**
     * Check if order is in transit (not yet completed)
     */
    public function isInTransit(): bool
    {
        return in_array($this->status, ['PROCESSING', 'DELIVERY', 'PICKUP', 'RESERVED'])
            && !$this->isCancelled()
            && !$this->isSold();
    }

    /**
     * Check if order is cancelled
     */
    public function isCancelled(): bool
    {
        return in_array($this->status, ['CANCELLED', 'RETURNED']);
    }

    /**
     * Get normalized status for unified reporting
     */
    public function getNormalizedStatus(): string
    {
        if ($this->isCancelled()) {
            return 'cancelled';
        }
        if ($this->isSold()) {
            return 'delivered';
        }
        return 'processing';
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Alias for marketplaceAccount for consistency with other models
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    // ========== Scopes ==========

    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }

    public function scopeSold($query)
    {
        return $query->where('stock_status', 'sold')->whereNotNull('stock_sold_at');
    }

    public function scopeInTransit($query)
    {
        return $query->whereIn('status', ['PROCESSING', 'DELIVERY', 'PICKUP', 'RESERVED'])
            ->whereNotIn('status', ['CANCELLED', 'RETURNED'])
            ->where(function ($q) {
                $q->where('stock_status', '!=', 'sold')->orWhereNull('stock_sold_at');
            });
    }

    public function scopeCancelled($query)
    {
        return $query->whereIn('status', ['CANCELLED', 'RETURNED']);
    }

    public function scopeInPeriod($query, $from, $to)
    {
        return $query->whereBetween('created_at_ym', [$from, $to]);
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
