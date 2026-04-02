<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Связь клиента с заказом маркетплейса.
 * Предотвращает дублирование при повторной синхронизации.
 */
final class MarketplaceCustomerOrder extends Model
{
    protected $fillable = [
        'marketplace_customer_id',
        'order_type',
        'order_id',
        'external_order_id',
        'source',
        'status',
        'total_amount',
        'currency',
        'ordered_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
            'ordered_at' => 'datetime',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCustomer::class, 'marketplace_customer_id');
    }

    /**
     * Полиморфная связь с заказом (UzumOrder, WbOrder, OzonOrder)
     */
    public function order(): MorphTo
    {
        return $this->morphTo('order');
    }

    /**
     * Статус отменён?
     */
    public function isCancelled(): bool
    {
        return in_array($this->status, ['cancelled', 'canceled', 'cancel', 'CANCELLED'], true);
    }

    /**
     * Человекочитаемый статус
     */
    public function getStatusLabel(): string
    {
        $labels = [
            'new' => 'Новый',
            'pending' => 'Ожидание',
            'processing' => 'В обработке',
            'confirmed' => 'Подтверждён',
            'shipped' => 'Отправлен',
            'delivering' => 'Доставляется',
            'delivered' => 'Доставлен',
            'completed' => 'Завершён',
            'cancelled' => 'Отменён',
            'canceled' => 'Отменён',
            'returned' => 'Возврат',
        ];

        return $labels[$this->status] ?? $this->status ?? 'Неизвестен';
    }
}
