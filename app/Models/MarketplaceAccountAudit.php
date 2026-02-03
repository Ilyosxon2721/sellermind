<?php

// file: app/Models/MarketplaceAccountAudit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceAccountAudit extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'marketplace_account_id',
        'user_id',
        'event',
        'ip_address',
        'user_agent',
        'old_values',
        'new_values',
        'description',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    // Event types
    public const EVENT_CREATED = 'created';

    public const EVENT_UPDATED = 'updated';

    public const EVENT_DELETED = 'deleted';

    public const EVENT_CREDENTIALS_CHANGED = 'credentials_changed';

    public const EVENT_SYNCED = 'synced';

    public const EVENT_ERROR = 'error';

    public const EVENT_ACTIVATED = 'activated';

    public const EVENT_DEACTIVATED = 'deactivated';

    /**
     * Get available event types
     */
    public static function eventTypes(): array
    {
        return [
            self::EVENT_CREATED => 'Создан',
            self::EVENT_UPDATED => 'Обновлён',
            self::EVENT_DELETED => 'Удалён',
            self::EVENT_CREDENTIALS_CHANGED => 'Изменены учётные данные',
            self::EVENT_SYNCED => 'Синхронизирован',
            self::EVENT_ERROR => 'Ошибка',
            self::EVENT_ACTIVATED => 'Активирован',
            self::EVENT_DEACTIVATED => 'Деактивирован',
        ];
    }

    /**
     * Get event label
     */
    public function getEventLabel(): string
    {
        return self::eventTypes()[$this->event] ?? $this->event;
    }

    /**
     * Marketplace account relationship
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * User relationship
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Create audit record
     */
    public static function log(
        int $accountId,
        string $event,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $description = null
    ): self {
        $request = request();
        $user = auth()->user();

        return self::create([
            'marketplace_account_id' => $accountId,
            'user_id' => $user?->id,
            'event' => $event,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'description' => $description,
            'created_at' => now(),
        ]);
    }

    /**
     * Log credentials change (without exposing actual values)
     */
    public static function logCredentialsChange(int $accountId, array $changedFields): self
    {
        return self::log(
            $accountId,
            self::EVENT_CREDENTIALS_CHANGED,
            null,
            ['changed_fields' => $changedFields],
            'Изменены поля: '.implode(', ', $changedFields)
        );
    }

    /**
     * Get recent audits for account
     */
    public static function recentForAccount(int $accountId, int $limit = 20)
    {
        return self::where('marketplace_account_id', $accountId)
            ->with('user:id,name,email')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Mask sensitive values in arrays
     */
    public static function maskSensitiveValues(array $values): array
    {
        $sensitiveKeys = ['api_key', 'client_secret', 'oauth_token', 'oauth_refresh_token', 'password'];

        foreach ($sensitiveKeys as $key) {
            if (isset($values[$key])) {
                $values[$key] = '***MASKED***';
            }
        }

        return $values;
    }
}
