<?php

declare(strict_types=1);

namespace App\Models\Kpi;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ступень шкалы бонусов
 *
 * @property int $id
 * @property int $kpi_bonus_scale_id
 * @property int $min_percent
 * @property int|null $max_percent
 * @property string $bonus_type
 * @property float $bonus_value
 */
final class BonusScaleTier extends Model
{
    protected $table = 'kpi_bonus_scale_tiers';

    public const TYPE_FIXED = 'fixed';

    public const TYPE_PERCENT_REVENUE = 'percent_revenue';

    public const TYPE_PERCENT_MARGIN = 'percent_margin';

    protected $fillable = [
        'kpi_bonus_scale_id',
        'min_percent',
        'max_percent',
        'bonus_type',
        'bonus_value',
    ];

    protected function casts(): array
    {
        return [
            'min_percent' => 'integer',
            'max_percent' => 'integer',
            'bonus_value' => 'float',
        ];
    }

    public function scale(): BelongsTo
    {
        return $this->belongsTo(BonusScale::class, 'kpi_bonus_scale_id');
    }

    /**
     * Рассчитать бонус для данной ступени
     */
    public function calculateBonus(float $actualRevenue, float $actualMargin): float
    {
        return match ($this->bonus_type) {
            self::TYPE_PERCENT_REVENUE => $actualRevenue * $this->bonus_value / 100,
            self::TYPE_PERCENT_MARGIN => $actualMargin * $this->bonus_value / 100,
            self::TYPE_FIXED => $this->bonus_value,
            default => 0,
        };
    }
}
