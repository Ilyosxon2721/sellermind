<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MarketplaceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MarketplacePollingState extends Model
{
    protected $table = 'marketplace_polling_states';

    protected $fillable = [
        'store_id', 'marketplace', 'endpoint', 'last_cursor',
        'last_poll_at', 'poll_interval_sec', 'consecutive_errors',
        'is_active', 'is_locked', 'locked_at',
    ];

    protected $casts = [
        'marketplace' => MarketplaceType::class,
        'is_active' => 'boolean',
        'is_locked' => 'boolean',
        'last_poll_at' => 'datetime',
        'locked_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'store_id');
    }

    /**
     * Проверить, устарела ли блокировка (более 5 минут)
     */
    public function isStale(): bool
    {
        return $this->is_locked
            && $this->locked_at
            && $this->locked_at->diffInMinutes(now()) > 5;
    }

    /**
     * Сбросить счётчик последовательных ошибок
     */
    public function resetErrors(): void
    {
        $this->update(['consecutive_errors' => 0]);
    }
}
