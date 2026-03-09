<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Модель подписки на Web Push уведомления
 *
 * @property int $id
 * @property int $user_id
 * @property string $endpoint
 * @property string $endpoint_hash
 * @property string $public_key
 * @property string $auth_token
 * @property string|null $content_encoding
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read User $user
 */
final class PushSubscription extends Model
{
    /**
     * Атрибуты, которые можно массово присваивать
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'endpoint',
        'endpoint_hash',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    /**
     * Boot метод модели - автоматически генерирует hash при сохранении
     */
    protected static function boot(): void
    {
        parent::boot();

        self::saving(function (self $subscription): void {
            $subscription->endpoint_hash = hash('sha256', $subscription->endpoint);
        });
    }

    /**
     * Пользователь, которому принадлежит подписка
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Найти подписку по endpoint
     */
    public static function findByEndpoint(string $endpoint): ?self
    {
        $hash = hash('sha256', $endpoint);

        return self::where('endpoint_hash', $hash)->first();
    }

    /**
     * Получить все подписки пользователя
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function getByUser(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)->get();
    }

    /**
     * Преобразовать в формат для библиотеки web-push
     *
     * @return array<string, mixed>
     */
    public function toWebPushFormat(): array
    {
        return [
            'endpoint' => $this->endpoint,
            'keys' => [
                'p256dh' => $this->public_key,
                'auth' => $this->auth_token,
            ],
            'contentEncoding' => $this->content_encoding ?? 'aesgcm',
        ];
    }
}
