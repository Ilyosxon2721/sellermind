<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Комиссии маркетплейсов по категориям
 *
 * Глобальная таблица (без привязки к компании).
 * Содержит процент комиссии, диапазоны цен и период действия.
 */
class MarketplaceCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'category_id',
        'fulfillment_type',
        'commission_percent',
        'commission_min',
        'commission_max',
        'price_ranges',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'commission_percent' => 'decimal:2',
        'commission_min' => 'decimal:2',
        'commission_max' => 'decimal:2',
        'price_ranges' => 'array',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Категория, к которой относится комиссия
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MarketplaceCategory::class, 'category_id');
    }

    /**
     * Только активные комиссии (действующие на текущую дату)
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = Carbon::today();

        return $query->where('is_active', true)
            ->where('effective_from', '<=', $now)
            ->where(function (Builder $q) use ($now): void {
                $q->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $now);
            });
    }

    /**
     * Фильтр по маркетплейсу
     */
    public function scopeForMarketplace(Builder $query, string $marketplace): Builder
    {
        return $query->where('marketplace', $marketplace);
    }

    /**
     * Рассчитать комиссию для указанной цены
     *
     * Если заданы price_ranges, сначала ищет подходящий диапазон.
     * Иначе использует commission_percent с ограничением min/max.
     */
    public function calculateCommission(float $price): float
    {
        // Проверяем диапазоны цен (если заданы)
        if (! empty($this->price_ranges) && is_array($this->price_ranges)) {
            foreach ($this->price_ranges as $range) {
                $from = (float) ($range['from'] ?? 0);
                $to = (float) ($range['to'] ?? PHP_FLOAT_MAX);
                $percent = (float) ($range['percent'] ?? $this->commission_percent);

                if ($price >= $from && $price <= $to) {
                    return round($price * $percent / 100, 2);
                }
            }
        }

        // Расчёт по базовому проценту
        $commission = round($price * (float) $this->commission_percent / 100, 2);

        // Ограничение минимумом
        if ($this->commission_min !== null) {
            $commission = max($commission, (float) $this->commission_min);
        }

        // Ограничение максимумом
        if ($this->commission_max !== null) {
            $commission = min($commission, (float) $this->commission_max);
        }

        return $commission;
    }
}
