<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Шаблоны расходов
 *
 * Привязка к компании. Позволяет быстро применять типовые расходы
 * (упаковка, доставка до склада, прочие) к товарам.
 */
class ExpenseTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'marketplace',
        'packaging_cost',
        'delivery_to_warehouse',
        'other_costs',
        'target_margin_percent',
        'is_default',
    ];

    protected $casts = [
        'packaging_cost' => 'decimal:2',
        'delivery_to_warehouse' => 'decimal:2',
        'other_costs' => 'decimal:2',
        'target_margin_percent' => 'decimal:2',
        'is_default' => 'boolean',
    ];

    /**
     * Фильтр по компании
     */
    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
