<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Эквайринг маркетплейсов
 *
 * Глобальная таблица (без привязки к компании).
 * Процент эквайринга зависит от частоты выплат.
 */
class MarketplaceAcquiring extends Model
{
    use HasFactory;

    protected $table = 'marketplace_acquiring';

    protected $fillable = [
        'marketplace',
        'payout_frequency',
        'rate_percent',
        'effective_from',
        'effective_to',
        'is_active',
    ];

    protected $casts = [
        'rate_percent' => 'decimal:2',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Только активные записи (действующие на текущую дату)
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
}
