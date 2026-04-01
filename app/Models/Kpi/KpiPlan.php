<?php

declare(strict_types=1);

namespace App\Models\Kpi;

use App\Models\Company;
use App\Models\Finance\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * KPI-план сотрудника по конкретной сфере за месяц
 *
 * @property int $id
 * @property int $company_id
 * @property int $employee_id
 * @property int $kpi_sales_sphere_id
 * @property int $kpi_bonus_scale_id
 * @property int $period_year
 * @property int $period_month
 * @property float $target_revenue
 * @property float $target_margin
 * @property int $target_orders
 * @property int $weight_revenue
 * @property int $weight_margin
 * @property int $weight_orders
 * @property float $actual_revenue
 * @property float $actual_margin
 * @property int $actual_orders
 * @property float $achievement_percent
 * @property float $bonus_amount
 * @property string $status
 */
final class KpiPlan extends Model
{
    use HasFactory;

    protected $table = 'kpi_plans';

    protected $appends = ['sphere_currency', 'bonus_amount_uzs'];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CALCULATED = 'calculated';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * Статусы отменённых заказов маркетплейсов (исключаются из расчёта KPI)
     */
    public const CANCELLED_ORDER_STATUSES = ['cancelled', 'canceled', 'CANCELED', 'PENDING_CANCELLATION'];

    protected $fillable = [
        'company_id',
        'employee_id',
        'branch_id',
        'parent_plan_id',
        'plan_type',
        'kpi_sales_sphere_id',
        'kpi_bonus_scale_id',
        'period_year',
        'period_month',
        'target_revenue',
        'target_margin',
        'target_orders',
        'weight_revenue',
        'weight_margin',
        'weight_orders',
        'actual_revenue',
        'actual_margin',
        'actual_orders',
        'achievement_percent',
        'bonus_amount',
        'status',
        'calculated_at',
        'approved_by',
        'approved_at',
        'notes',
        'currency',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'target_revenue' => 'float',
            'target_margin' => 'float',
            'target_orders' => 'integer',
            'weight_revenue' => 'integer',
            'weight_margin' => 'integer',
            'weight_orders' => 'integer',
            'actual_revenue' => 'float',
            'actual_margin' => 'float',
            'actual_orders' => 'integer',
            'achievement_percent' => 'float',
            'bonus_amount' => 'float',
            'calculated_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Branch::class);
    }

    public function parentPlan(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_plan_id');
    }

    public function childPlans(): HasMany
    {
        return $this->hasMany(self::class, 'parent_plan_id');
    }

    public function salesSphere(): BelongsTo
    {
        return $this->belongsTo(SalesSphere::class, 'kpi_sales_sphere_id');
    }

    public function bonusScale(): BelongsTo
    {
        return $this->belongsTo(BonusScale::class, 'kpi_bonus_scale_id');
    }

    /**
     * Это план на филиал?
     */
    public function isBranchPlan(): bool
    {
        return $this->plan_type === 'branch';
    }

    /**
     * Процент распределения плана филиала (сумма дочерних / цель филиала)
     */
    public function getDistributionPercentAttribute(): float
    {
        if (! $this->isBranchPlan()) {
            return 0;
        }

        $childrenRevenue = $this->childPlans()->sum('target_revenue');

        return $this->target_revenue > 0
            ? round($childrenRevenue / $this->target_revenue * 100, 1)
            : 0;
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Рассчитать взвешенный процент выполнения
     */
    public function calculateAchievement(): float
    {
        $revenuePct = $this->target_revenue > 0
            ? min($this->actual_revenue / $this->target_revenue * 100, 200)
            : 0;

        $marginPct = $this->target_margin > 0
            ? min($this->actual_margin / $this->target_margin * 100, 200)
            : 0;

        $ordersPct = $this->target_orders > 0
            ? min($this->actual_orders / $this->target_orders * 100, 200)
            : 0;

        $totalWeight = $this->weight_revenue + $this->weight_margin + $this->weight_orders;

        if ($totalWeight === 0) {
            return 0;
        }

        return round(
            ($revenuePct * $this->weight_revenue + $marginPct * $this->weight_margin + $ordersPct * $this->weight_orders) / $totalWeight,
            2
        );
    }

    /**
     * Рассчитать бонус по шкале
     */
    public function calculateBonus(): float
    {
        $scale = $this->bonusScale;
        if (! $scale) {
            return 0;
        }

        $scale->load('tiers');
        $tier = $scale->getTierForPercent($this->achievement_percent);

        if (! $tier) {
            return 0;
        }

        return round($tier->calculateBonus($this->actual_revenue, $this->actual_margin), 2);
    }

    /**
     * Валюта плана (из поля currency, или автоопределение по сфере)
     */
    public function getSphereCurrencyAttribute(): string
    {
        // Приоритет: явно указанная валюта в плане
        if (! empty($this->currency) && $this->currency !== 'UZS') {
            return $this->currency;
        }

        if (! empty($this->currency)) {
            return $this->currency;
        }

        // Fallback: автоопределение по маркетплейсу
        $sphere = $this->salesSphere;
        if (! $sphere || $sphere->isManual()) {
            return 'UZS';
        }

        $accountIds = $sphere->getLinkedAccountIds();
        if (empty($accountIds)) {
            return 'UZS';
        }

        $marketplace = \App\Models\MarketplaceAccount::whereIn('id', $accountIds)
            ->value('marketplace');

        return match ($marketplace) {
            'wb', 'wildberries', 'ozon', 'ym', 'yandex_market' => 'RUB',
            default => 'UZS',
        };
    }

    /**
     * Бонус конвертированный в UZS
     */
    public function getBonusAmountUzsAttribute(): float
    {
        if ($this->bonus_amount == 0) {
            return 0;
        }

        $currency = $this->sphere_currency;

        if ($currency === 'UZS') {
            return $this->bonus_amount;
        }

        $rate = \Illuminate\Support\Facades\Cache::get("exchange_rate:{$currency}_UZS", 0);

        if ($rate <= 0) {
            return $this->bonus_amount;
        }

        return round($this->bonus_amount * $rate, 2);
    }

    public function getPeriodLabelAttribute(): string
    {
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март',
            4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
            7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь',
            10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
        ];

        return ($months[$this->period_month] ?? '') . ' ' . $this->period_year;
    }

    public function scopeByCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeForPeriod($query, int $year, int $month)
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function scopeForEmployee($query, int $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_CALCULATED]);
    }
}
