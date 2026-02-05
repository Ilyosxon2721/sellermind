<?php

// file: app/Models/MarketplaceSyncSchedule.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceSyncSchedule extends Model
{
    public const TYPE_PRODUCTS = 'products';

    public const TYPE_PRICES = 'prices';

    public const TYPE_STOCKS = 'stocks';

    public const TYPE_ORDERS = 'orders';

    public const TYPE_PAYOUTS = 'payouts';

    public const TYPE_ANALYTICS = 'analytics';

    public const TYPE_AUTOMATION = 'automation';

    protected $fillable = [
        'marketplace_account_id',
        'sync_type',
        'cron_expression',
        'is_active',
        'last_run_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_run_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class, 'marketplace_account_id');
    }

    /**
     * Get sync type label
     */
    public function getSyncTypeLabel(): string
    {
        return match ($this->sync_type) {
            self::TYPE_PRODUCTS => 'Товары',
            self::TYPE_PRICES => 'Цены',
            self::TYPE_STOCKS => 'Остатки',
            self::TYPE_ORDERS => 'Заказы',
            self::TYPE_PAYOUTS => 'Выплаты',
            self::TYPE_ANALYTICS => 'Аналитика',
            self::TYPE_AUTOMATION => 'Автоматизация',
            default => $this->sync_type,
        };
    }

    /**
     * Get human-readable cron description
     */
    public function getCronDescription(): string
    {
        $expr = $this->cron_expression;

        // Common patterns
        $patterns = [
            '* * * * *' => 'Каждую минуту',
            '*/5 * * * *' => 'Каждые 5 минут',
            '*/10 * * * *' => 'Каждые 10 минут',
            '*/15 * * * *' => 'Каждые 15 минут',
            '*/30 * * * *' => 'Каждые 30 минут',
            '0 * * * *' => 'Каждый час',
            '0 */2 * * *' => 'Каждые 2 часа',
            '0 */4 * * *' => 'Каждые 4 часа',
            '0 */6 * * *' => 'Каждые 6 часов',
            '0 */12 * * *' => 'Каждые 12 часов',
            '0 0 * * *' => 'Ежедневно в полночь',
            '0 8 * * *' => 'Ежедневно в 8:00',
            '0 0 * * 1' => 'Каждый понедельник',
            '0 0 1 * *' => 'Первого числа каждого месяца',
        ];

        return $patterns[$expr] ?? $expr;
    }

    /**
     * Get available sync types
     */
    public static function getSyncTypes(): array
    {
        return [
            self::TYPE_PRODUCTS => 'Товары',
            self::TYPE_PRICES => 'Цены',
            self::TYPE_STOCKS => 'Остатки',
            self::TYPE_ORDERS => 'Заказы',
            self::TYPE_PAYOUTS => 'Выплаты',
            self::TYPE_ANALYTICS => 'Аналитика',
            self::TYPE_AUTOMATION => 'Автоматизация',
        ];
    }

    /**
     * Get common cron presets
     */
    public static function getCronPresets(): array
    {
        return [
            '*/5 * * * *' => 'Каждые 5 минут',
            '*/15 * * * *' => 'Каждые 15 минут',
            '*/30 * * * *' => 'Каждые 30 минут',
            '0 * * * *' => 'Каждый час',
            '0 */2 * * *' => 'Каждые 2 часа',
            '0 */6 * * *' => 'Каждые 6 часов',
            '0 0 * * *' => 'Раз в сутки (полночь)',
            '0 8 * * *' => 'Раз в сутки (8:00)',
        ];
    }
}
