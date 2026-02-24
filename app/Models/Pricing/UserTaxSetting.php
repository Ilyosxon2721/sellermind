<?php

declare(strict_types=1);

namespace App\Models\Pricing;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Налоговые настройки пользователя
 *
 * Привязка к компании (unique). Определяет систему налогообложения,
 * ставку налога и включение в расчёт цены.
 */
class UserTaxSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'tax_system',
        'tax_rate',
        'include_in_price',
    ];

    protected $casts = [
        'tax_rate' => 'decimal:2',
        'include_in_price' => 'boolean',
    ];

    /**
     * Фильтр по компании
     */
    public function scopeByCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
