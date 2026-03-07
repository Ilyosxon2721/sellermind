<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Тарифы логистики маркетплейсов
 *
 * Глобальная таблица (без привязки к компании).
 * Содержит тарифы доставки, возврата, обработки и хранения.
 */
class MarketplaceLogistics extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'fulfillment_type',
        'logistics_type',
        'region',
        'volume_from',
        'volume_to',
        'weight_from',
        'weight_to',
        'rate',
        'rate_type',
        'currency',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'rate' => 'decimal:2',
        'volume_from' => 'decimal:2',
        'volume_to' => 'decimal:2',
        'weight_from' => 'decimal:2',
        'weight_to' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Только активные тарифы (действующие на текущую дату)
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
     * Только тарифы доставки (logistics_type = delivery)
     */
    public function scopeForDelivery(Builder $query): Builder
    {
        return $query->where('logistics_type', 'delivery');
    }
}
