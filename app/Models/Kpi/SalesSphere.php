<?php

declare(strict_types=1);

namespace App\Models\Kpi;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Сфера продаж для KPI (произвольная: WB, Ozon, Instagram, розница и т.д.)
 *
 * @property int $id
 * @property int $company_id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property string $color
 * @property string $icon
 * @property int|null $marketplace_account_id
 * @property bool $is_active
 * @property int $sort_order
 */
final class SalesSphere extends Model
{
    protected $table = 'kpi_sales_spheres';

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'description',
        'color',
        'icon',
        'marketplace_account_id',
        'marketplace_account_ids',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'marketplace_account_ids' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function marketplaceAccount(): BelongsTo
    {
        return $this->belongsTo(MarketplaceAccount::class);
    }

    public function plans(): HasMany
    {
        return $this->hasMany(KpiPlan::class, 'kpi_sales_sphere_id');
    }

    /**
     * Привязана ли сфера к маркетплейсу (для автосбора данных)
     */
    public function hasMarketplaceLink(): bool
    {
        return ! empty($this->marketplace_account_ids) || $this->marketplace_account_id !== null;
    }

    /**
     * Получить все привязанные ID маркетплейс-аккаунтов
     */
    public function getLinkedAccountIds(): array
    {
        if (! empty($this->marketplace_account_ids)) {
            return $this->marketplace_account_ids;
        }

        if ($this->marketplace_account_id !== null) {
            return [$this->marketplace_account_id];
        }

        return [];
    }

    /**
     * Загрузить привязанные маркетплейс-аккаунты
     */
    public function getLinkedAccounts(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->getLinkedAccountIds();

        if (empty($ids)) {
            return new \Illuminate\Database\Eloquent\Collection();
        }

        return MarketplaceAccount::whereIn('id', $ids)->get();
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
