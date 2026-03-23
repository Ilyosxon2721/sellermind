<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Модель JWT токена для Uzum API
 *
 * @property int $id
 * @property string $token
 * @property string $iid
 * @property \Carbon\Carbon $expires_at
 * @property bool $is_active
 * @property int $requests_count
 */
class UzumToken extends Model
{
    protected $table = 'uzum_token_pool';

    protected $fillable = [
        'token',
        'iid',
        'expires_at',
        'is_active',
        'requests_count',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'requests_count' => 'integer',
    ];

    /**
     * Истёк ли токен
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Скоро истечёт (осталось меньше 2 минут)
     */
    public function isExpiringSoon(): bool
    {
        return $this->expires_at->diffInSeconds(now()) < config('uzum-crawler.token_pool.refresh_before_expire_seconds', 120);
    }

    /**
     * Можно ли использовать токен
     */
    public function isUsable(): bool
    {
        return $this->is_active && ! $this->isExpired();
    }

    /**
     * Достигнут ли лимит запросов для ротации
     */
    public function shouldRotate(): bool
    {
        return $this->requests_count >= config('uzum-crawler.token_pool.max_requests_per_token', 8);
    }

    /**
     * Инкремент счётчика запросов
     */
    public function incrementRequests(): void
    {
        $this->increment('requests_count');
    }

    /**
     * Деактивировать токен
     */
    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Scope для активных токенов
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope для валидных (не истёкших) токенов
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope для используемых токенов
     */
    public function scopeUsable($query)
    {
        return $query->active()->valid();
    }

    /**
     * Scope для токенов, требующих ротации
     */
    public function scopeNeedingRotation($query)
    {
        $maxRequests = config('uzum-crawler.token_pool.max_requests_per_token', 8);

        return $query->where('requests_count', '>=', $maxRequests);
    }

    /**
     * Scope для токенов, скоро истекающих
     */
    public function scopeExpiringSoon($query)
    {
        $threshold = now()->addSeconds(
            config('uzum-crawler.token_pool.refresh_before_expire_seconds', 120)
        );

        return $query->where('expires_at', '<=', $threshold);
    }
}
