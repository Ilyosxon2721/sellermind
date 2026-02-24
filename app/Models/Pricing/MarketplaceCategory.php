<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Справочник категорий маркетплейсов
 *
 * Глобальная таблица (без привязки к компании).
 * Используется для определения комиссий и логистических тарифов.
 */
class MarketplaceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'marketplace',
        'category_id',
        'name',
        'parent_id',
        'path',
    ];

    /**
     * Дочерние категории
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id', 'category_id')
            ->where('marketplace', $this->marketplace);
    }

    /**
     * Комиссии по данной категории
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(MarketplaceCommission::class, 'category_id');
    }

    /**
     * Фильтр по маркетплейсу
     */
    public function scopeForMarketplace(Builder $query, string $marketplace): Builder
    {
        return $query->where('marketplace', $marketplace);
    }
}
