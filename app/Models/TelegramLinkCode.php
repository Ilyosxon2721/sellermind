<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramLinkCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'marketplace_account_id',
        'code',
        'expires_at',
        'is_used',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Пользователь, которому принадлежит код
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Аккаунт маркетплейса, к которому привязывается Telegram
     */
    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    /**
     * Сгенерировать код привязки пользователя к аккаунту маркетплейса
     *
     * Удаляет неиспользованные коды для данного аккаунта,
     * генерирует 6-значный цифровой код с TTL 10 минут.
     */
    public static function generateForAccount(int $userId, int $accountId): self
    {
        // Инвалидировать предыдущие коды для этого аккаунта маркетплейса
        self::where('marketplace_account_id', $accountId)
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Генерировать уникальный 6-значный цифровой код
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->where('is_used', false)->exists());

        return self::create([
            'user_id' => $userId,
            'marketplace_account_id' => $accountId,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Сгенерировать код привязки только для пользователя (без аккаунта маркетплейса)
     */
    public static function generate(int $userId): self
    {
        // Инвалидировать предыдущие коды пользователя
        self::where('user_id', $userId)
            ->whereNull('marketplace_account_id')
            ->where('is_used', false)
            ->update(['is_used' => true]);

        // Генерировать уникальный 6-значный цифровой код
        do {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (self::where('code', $code)->where('is_used', false)->exists());

        return self::create([
            'user_id' => $userId,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
        ]);
    }

    /**
     * Проверить валидность кода
     */
    public function isValid(): bool
    {
        return ! $this->is_used && $this->expires_at->isFuture();
    }

    /**
     * Отметить код как использованный
     */
    public function markAsUsed(): void
    {
        $this->update(['is_used' => true]);
    }
}
