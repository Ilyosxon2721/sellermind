<?php

declare(strict_types=1);

namespace App\Models\Kpi;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use Illuminate\Database\Eloquent\Factories\HasFactory;
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
    use HasFactory;

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
        'offline_sale_types',
        'store_ids',
        'sale_sources',
        'is_active',
        'is_manual',
        'label_metric1',
        'label_metric2',
        'label_metric3',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_manual' => 'boolean',
            'sort_order' => 'integer',
            'marketplace_account_ids' => 'array',
            'offline_sale_types' => 'array',
            'store_ids' => 'array',
            'sale_sources' => 'array',
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
     * Привязана ли сфера к типам ручных продаж (retail, wholesale, direct)
     */
    public function hasOfflineSaleLink(): bool
    {
        return ! empty($this->offline_sale_types);
    }

    /**
     * Получить привязанные типы ручных продаж
     */
    public function getOfflineSaleTypes(): array
    {
        return $this->offline_sale_types ?? [];
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

    /**
     * Привязана ли сфера к интернет-магазину (StoreOrder)
     */
    public function hasStoreLink(): bool
    {
        return ! empty($this->store_ids);
    }

    /**
     * Привязана ли сфера к ручным/POS продажам (Sale)
     */
    public function hasSaleSourceLink(): bool
    {
        return ! empty($this->sale_sources);
    }

    /**
     * Ручная сфера — данные вводятся вручную, без автосбора
     */
    public function isManual(): bool
    {
        return $this->is_manual || (! $this->hasMarketplaceLink() && ! $this->hasOfflineSaleLink() && ! $this->hasStoreLink() && ! $this->hasSaleSourceLink());
    }

    /**
     * Получить название метрики (с fallback на стандартное)
     */
    public function getMetricLabel(int $num): string
    {
        return match ($num) {
            1 => $this->label_metric1 ?? 'Оборот',
            2 => $this->label_metric2 ?? 'Маржа',
            3 => $this->label_metric3 ?? 'Заказы',
            default => '',
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
