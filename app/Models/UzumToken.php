<?php

// file: app/Models/UzumToken.php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Модель токена из пула для краулера Uzum Analytics
 *
 * @property int $id
 * @property string $token JWT токен
 * @property \Carbon\Carbon $expires_at Время истечения
 * @property int $request_count Количество использованных запросов
 * @property bool $is_active Токен активен
 */
class UzumToken extends Model
{
    protected $table = 'uzum_token_pool';

    protected $fillable = [
        'token',
        'expires_at',
        'request_count',
        'is_active',
    ];

    protected $casts = [
        'expires_at'    => 'datetime',
        'request_count' => 'integer',
        'is_active'     => 'boolean',
    ];

    /**
     * Скоп: активные и не истёкшие токены (с запасом 2 минуты)
     */
    public function scopeActive(Builder $query): Builder
    {
        $refreshBefore = (int) config('uzum-crawler.token_pool.refresh_before', 2);

        return $query
            ->where('is_active', true)
            ->where('expires_at', '>', now()->addMinutes($refreshBefore));
    }

    /**
     * Токен истекает в ближайшие N минут
     */
    public function isExpiringSoon(): bool
    {
        $refreshBefore = (int) config('uzum-crawler.token_pool.refresh_before', 2);

        return $this->expires_at->lt(now()->addMinutes($refreshBefore));
    }

    /**
     * Токен достиг лимита запросов
     */
    public function isFull(): bool
    {
        $maxRequests = (int) config('uzum-crawler.token_pool.max_requests', 8);

        return $this->request_count >= $maxRequests;
    }

    /**
     * Увеличить счётчик запросов
     */
    public function incrementRequests(): void
    {
        $this->increment('request_count');
    }
}
