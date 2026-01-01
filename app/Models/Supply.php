<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supply extends Model
{
    protected $fillable = [
        'marketplace_account_id',
        'external_supply_id',
        'barcode_path',
        'name',
        'status',
        'description',
        'orders_count',
        'total_amount',
        'closed_at',
        'sent_at',
        'delivered_at',
        'metadata',
        // FBS новые поля
        'cargo_type',
        'boxes_count',
        'delivery_started_at',
    ];

    protected $casts = [
        'orders_count' => 'integer',
        'boxes_count' => 'integer',
        'total_amount' => 'decimal:2',
        'closed_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
        'delivery_started_at' => 'datetime',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Статусы поставки
     */
    public const STATUS_DRAFT = 'draft'; // Черновик
    public const STATUS_IN_ASSEMBLY = 'in_assembly'; // На сборке
    public const STATUS_READY = 'ready'; // Готова к отправке
    public const STATUS_SENT = 'sent'; // Отправлена
    public const STATUS_DELIVERED = 'delivered'; // Доставлена
    public const STATUS_CANCELLED = 'cancelled'; // Отменена

    /**
     * Получить аккаунт маркетплейса
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Получить заказы в поставке
     */
    public function orders(): HasMany
    {
        return $this->hasMany(MarketplaceOrder::class, 'supply_id', 'external_supply_id');
    }

    /**
     * Получить короба (тары) поставки
     */
    public function tares(): HasMany
    {
        return $this->hasMany(Tare::class);
    }

    /**
     * Alias для tares (для обратной совместимости)
     */
    public function boxes(): HasMany
    {
        return $this->tares();
    }

    /**
     * Проверить, можно ли добавлять заказы в поставку
     */
    public function canAddOrders(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_IN_ASSEMBLY])
            && is_null($this->closed_at);
    }

    /**
     * Проверить, можно ли редактировать поставку
     */
    public function canEdit(): bool
    {
        return !in_array($this->status, [self::STATUS_SENT, self::STATUS_DELIVERED]);
    }

    /**
     * Получить название статуса
     */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'Черновик',
            self::STATUS_IN_ASSEMBLY => 'На сборке',
            self::STATUS_READY => 'Готова',
            self::STATUS_SENT => 'Отправлена',
            self::STATUS_DELIVERED => 'Доставлена',
            self::STATUS_CANCELLED => 'Отменена',
            default => $this->status,
        };
    }

    /**
     * Получить цвет статуса для UI
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_DRAFT => 'gray',
            self::STATUS_IN_ASSEMBLY => 'blue',
            self::STATUS_READY => 'green',
            self::STATUS_SENT => 'purple',
            self::STATUS_DELIVERED => 'emerald',
            self::STATUS_CANCELLED => 'red',
            default => 'gray',
        };
    }

    /**
     * Пересчитать количество заказов и общую сумму
     */
    public function recalculateStats(): void
    {
        $this->orders_count = $this->orders()->count();
        $this->total_amount = $this->orders()->sum('total_amount');
        $this->save();
    }

    /**
     * Закрыть поставку для добавления заказов
     */
    public function close(): void
    {
        $this->closed_at = now();
        $this->status = self::STATUS_READY;
        $this->save();
    }

    /**
     * Отправить поставку
     */
    public function markAsSent(): void
    {
        $this->sent_at = now();
        $this->status = self::STATUS_SENT;
        $this->save();
    }

    /**
     * Отметить поставку как доставленную
     */
    public function markAsDelivered(): void
    {
        $this->delivered_at = now();
        $this->status = self::STATUS_DELIVERED;
        $this->save();
    }

    /**
     * Scope для открытых поставок (можно добавлять заказы)
     */
    public function scopeOpen($query)
    {
        return $query->whereIn('status', [self::STATUS_DRAFT, self::STATUS_IN_ASSEMBLY])
            ->whereNull('closed_at');
    }

    /**
     * Scope для поставок конкретного аккаунта
     */
    public function scopeForAccount($query, int $accountId)
    {
        return $query->where('marketplace_account_id', $accountId);
    }
}
