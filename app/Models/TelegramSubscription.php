<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Подписка на Telegram-уведомления о заказах.
 * Позволяет гибко фильтровать: по маркетплейсу, аккаунту, типу события.
 */
final class TelegramSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'chat_id',
        'marketplace',
        'marketplace_account_id',
        'notify_new',
        'notify_status',
        'notify_cancel',
        'is_active',
        'daily_summary',
        'summary_time',
    ];

    protected $casts = [
        'notify_new' => 'boolean',
        'notify_status' => 'boolean',
        'notify_cancel' => 'boolean',
        'is_active' => 'boolean',
        'daily_summary' => 'boolean',
    ];

    /**
     * Пользователь-владелец подписки
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Аккаунт маркетплейса (опционально)
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Только активные подписки
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Подписки для конкретного маркетплейса (или без фильтра)
     */
    public function scopeForMarketplace(Builder $query, string $marketplace): Builder
    {
        return $query->where(function (Builder $q) use ($marketplace) {
            $q->whereNull('marketplace')
                ->orWhere('marketplace', $marketplace);
        });
    }

    /**
     * Подписки для конкретного аккаунта (или без фильтра)
     */
    public function scopeForAccount(Builder $query, int $accountId): Builder
    {
        return $query->where(function (Builder $q) use ($accountId) {
            $q->whereNull('marketplace_account_id')
                ->orWhere('marketplace_account_id', $accountId);
        });
    }

    /**
     * Подписки с включённым дневным отчётом
     */
    public function scopeDailySummary(Builder $query): Builder
    {
        return $query->where('daily_summary', true);
    }

    /**
     * Проверить, нужно ли уведомлять для данного статуса заказа
     */
    public function shouldNotifyForStatus(string $status): bool
    {
        return match ($status) {
            'new' => $this->notify_new,
            'cancelled', 'returned' => $this->notify_cancel,
            default => $this->notify_status,
        };
    }
}
