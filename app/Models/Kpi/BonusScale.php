<?php

declare(strict_types=1);

namespace App\Models\Kpi;

use App\Models\Company;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Шкала бонусов (ступенчатая)
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property bool $is_default
 */
final class BonusScale extends Model
{
    protected $table = 'kpi_bonus_scales';

    protected $fillable = [
        'company_id',
        'name',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(BonusScaleTier::class, 'kpi_bonus_scale_id')->orderBy('min_percent');
    }

    /**
     * Найти подходящую ступень по проценту выполнения
     */
    public function getTierForPercent(float $achievementPercent): ?BonusScaleTier
    {
        return $this->tiers
            ->first(function (BonusScaleTier $tier) use ($achievementPercent) {
                $aboveMin = $achievementPercent >= $tier->min_percent;
                $belowMax = $tier->max_percent === null || $achievementPercent < $tier->max_percent;

                return $aboveMin && $belowMax;
            });
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }
}
