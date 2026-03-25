<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Models\Finance\Employee;
use App\Models\Kpi\KpiPlan;
use App\Models\Kpi\SalesSphere;
use App\Models\MarketplaceAccount;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Сервис расчёта KPI и бонусов сотрудников
 */
final class KpiCalculationService
{
    public function __construct(
        private readonly KpiMarginCalculator $marginCalculator,
    ) {}

    /**
     * Собрать фактические данные по сфере за период
     *
     * @return array{revenue: float, margin: float, orders: int}
     */
    public function collectActuals(KpiPlan $plan): array
    {
        $sphere = $plan->salesSphere;
        $hasMarketplace = $sphere->hasMarketplaceLink();
        $hasOffline = $sphere->hasOfflineSaleLink();

        // Если нет привязок — ручной ввод
        if (! $hasMarketplace && ! $hasOffline) {
            return [
                'revenue' => $plan->actual_revenue,
                'margin' => $plan->actual_margin,
                'orders' => $plan->actual_orders,
            ];
        }

        // Агрегируем из всех привязанных источников
        $revenue = 0.0;
        $margin = 0.0;
        $orders = 0;

        if ($hasMarketplace) {
            $mpData = $this->collectMarketplaceActuals($plan, $sphere);
            $revenue += $mpData['revenue'];
            $margin += $mpData['margin'];
            $orders += $mpData['orders'];
        }

        if ($hasOffline) {
            $offData = $this->collectOfflineSaleActuals($plan, $sphere);
            $revenue += $offData['revenue'];
            $margin += $offData['margin'];
            $orders += $offData['orders'];
        }

        return compact('revenue', 'margin', 'orders');
    }

    /**
     * Собрать фактические данные из заказов маркетплейсов
     *
     * Читает напрямую из таблиц wb_orders, uzum_orders, ozon_orders,
     * yandex_market_orders, wildberries_orders (Statistics API).
     *
     * @return array{revenue: float, margin: float, orders: int}
     */
    private function collectMarketplaceActuals(KpiPlan $plan, SalesSphere $sphere): array
    {
        $accountIds = $sphere->getLinkedAccountIds();
        $periodStart = Carbon::create($plan->period_year, $plan->period_month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        if (empty($accountIds)) {
            return ['revenue' => 0.0, 'margin' => 0.0, 'orders' => 0];
        }

        // Группируем аккаунты по маркетплейсам
        $accounts = MarketplaceAccount::whereIn('id', $accountIds)->get();
        $accountsByMarketplace = $accounts->groupBy('marketplace');

        $totalRevenue = 0.0;
        $totalMargin = 0.0;
        $totalOrders = 0;

        foreach ($accountsByMarketplace as $marketplace => $mpAccounts) {
            $mpAccountIds = $mpAccounts->pluck('id')->toArray();
            $stats = $this->getMarketplaceStats($marketplace, $mpAccountIds, $periodStart, $periodEnd);
            $totalRevenue += $stats['revenue'];
            $totalOrders += $stats['orders'];
            $totalMargin += $this->marginCalculator->calculateMargin(
                $marketplace,
                $mpAccountIds,
                $periodStart,
                $periodEnd,
                $plan->company_id,
            );
        }

        return [
            'revenue' => $totalRevenue,
            'margin' => $totalMargin,
            'orders' => $totalOrders,
        ];
    }

    /**
     * Получить статистику по конкретному маркетплейсу за период
     *
     * @return array{revenue: float, orders: int}
     */
    private function getMarketplaceStats(string $marketplace, array $accountIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        return match ($marketplace) {
            'wb', 'wildberries' => $this->getWbStats($accountIds, $periodStart, $periodEnd),
            'uzum' => $this->getUzumStats($accountIds, $periodStart, $periodEnd),
            'ozon' => $this->getOzonStats($accountIds, $periodStart, $periodEnd),
            'ym', 'yandex_market' => $this->getYmStats($accountIds, $periodStart, $periodEnd),
            default => ['revenue' => 0.0, 'orders' => 0],
        };
    }

    /**
     * Статистика Wildberries из Statistics API (wildberries_orders) + FBS (wb_orders)
     *
     * @return array{revenue: float, orders: int}
     */
    private function getWbStats(array $accountIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        // WB Statistics API — финансовые данные (for_pay = сумма к оплате продавцу)
        $wbStatsRow = DB::table('wildberries_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$periodStart, $periodEnd])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->selectRaw('COALESCE(SUM(for_pay), 0) as revenue, COUNT(*) as orders')
            ->first();

        $statsRevenue = (float) ($wbStatsRow->revenue ?? 0);
        $statsOrders = (int) ($wbStatsRow->orders ?? 0);

        // Если Statistics API не синхронизировался — берём из FBS заказов (wb_orders)
        if ($statsRevenue == 0) {
            $wbFbsRow = DB::table('wb_orders')
                ->whereIn('marketplace_account_id', $accountIds)
                ->whereBetween('ordered_at', [$periodStart, $periodEnd])
                ->whereNotIn('status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
                ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as orders')
                ->first();

            return [
                'revenue' => (float) ($wbFbsRow->revenue ?? 0),
                'orders' => (int) ($wbFbsRow->orders ?? 0),
            ];
        }

        return [
            'revenue' => $statsRevenue,
            'orders' => $statsOrders,
        ];
    }

    /**
     * Статистика Uzum из uzum_orders
     *
     * @return array{revenue: float, orders: int}
     */
    private function getUzumStats(array $accountIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $row = DB::table('uzum_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$periodStart, $periodEnd])
            ->whereNotIn('status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(total_amount), 0) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * Статистика Ozon из ozon_orders
     *
     * @return array{revenue: float, orders: int}
     */
    private function getOzonStats(array $accountIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $row = DB::table('ozon_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$periodStart, $periodEnd])
            ->whereNotIn('status', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * Статистика Yandex Market из yandex_market_orders
     *
     * @return array{revenue: float, orders: int}
     */
    private function getYmStats(array $accountIds, Carbon $periodStart, Carbon $periodEnd): array
    {
        $row = DB::table('yandex_market_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$periodStart, $periodEnd])
            ->whereNotIn('status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * Собрать фактические данные из ручных продаж (OfflineSale) по типам
     *
     * @return array{revenue: float, margin: float, orders: int}
     */
    private function collectOfflineSaleActuals(KpiPlan $plan, SalesSphere $sphere): array
    {
        $saleTypes = $sphere->getOfflineSaleTypes();
        $periodStart = Carbon::create($plan->period_year, $plan->period_month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        // Оборот и количество заказов из завершённых OfflineSale
        $salesData = OfflineSale::byCompany($plan->company_id)
            ->whereIn('sale_type', $saleTypes)
            ->whereIn('status', [OfflineSale::STATUS_CONFIRMED, OfflineSale::STATUS_SHIPPED, OfflineSale::STATUS_DELIVERED])
            ->whereBetween('sale_date', [$periodStart, $periodEnd])
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(*) as total_orders')
            ->first();

        $revenue = (float) ($salesData->total_revenue ?? 0);
        $orders = (int) ($salesData->total_orders ?? 0);

        // Маржа: выручка - себестоимость из OfflineSaleItem
        $costData = OfflineSaleItem::whereHas('sale', function ($q) use ($plan, $saleTypes, $periodStart, $periodEnd) {
            $q->where('company_id', $plan->company_id)
                ->whereIn('sale_type', $saleTypes)
                ->whereIn('status', [OfflineSale::STATUS_CONFIRMED, OfflineSale::STATUS_SHIPPED, OfflineSale::STATUS_DELIVERED])
                ->whereBetween('sale_date', [$periodStart, $periodEnd]);
        })
            ->selectRaw('COALESCE(SUM(line_total), 0) as total_sales, COALESCE(SUM(unit_cost * quantity), 0) as total_cost')
            ->first();

        $margin = (float) (($costData->total_sales ?? 0) - ($costData->total_cost ?? 0));

        return [
            'revenue' => $revenue,
            'margin' => max(0, $margin),
            'orders' => $orders,
        ];
    }

    /**
     * Рассчитать один KPI-план: собрать данные, посчитать %, посчитать бонус
     */
    public function calculatePlan(KpiPlan $plan): KpiPlan
    {
        $plan->load(['salesSphere', 'bonusScale.tiers']);

        // Собрать фактические данные
        $actuals = $this->collectActuals($plan);
        $plan->actual_revenue = $actuals['revenue'];
        $plan->actual_margin = $actuals['margin'];
        $plan->actual_orders = $actuals['orders'];

        // Рассчитать % выполнения
        $plan->achievement_percent = $plan->calculateAchievement();

        // Рассчитать бонус
        $plan->bonus_amount = $plan->calculateBonus();

        $plan->status = KpiPlan::STATUS_CALCULATED;
        $plan->calculated_at = now();
        $plan->save();

        event(new \App\Events\Kpi\KpiPlanCalculated($plan));

        return $plan;
    }

    /**
     * Массовый расчёт всех активных планов за период
     */
    public function calculatePeriod(int $companyId, int $year, int $month): Collection
    {
        $plans = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->whereIn('status', [KpiPlan::STATUS_ACTIVE, KpiPlan::STATUS_CALCULATED])
            ->with(['salesSphere', 'bonusScale.tiers', 'employee'])
            ->get();

        $calculated = DB::transaction(fn () => $plans->map(fn (KpiPlan $plan) => $this->calculatePlan($plan)));

        event(new \App\Events\Kpi\KpiBatchCalculated($companyId, $year, $month, $calculated->count()));

        return $calculated;
    }

    /**
     * Получить суммарный бонус сотрудника за период (из всех сфер)
     */
    public function getEmployeeTotalBonus(int $employeeId, int $year, int $month): float
    {
        return (float) KpiPlan::forEmployee($employeeId)
            ->forPeriod($year, $month)
            ->whereIn('status', [KpiPlan::STATUS_CALCULATED, KpiPlan::STATUS_APPROVED])
            ->sum('bonus_amount');
    }

    /**
     * Утвердить план
     */
    public function approvePlan(KpiPlan $plan, int $userId): KpiPlan
    {
        $plan->update([
            'status' => KpiPlan::STATUS_APPROVED,
            'approved_by' => $userId,
            'approved_at' => now(),
        ]);

        $plan = $plan->fresh();

        event(new \App\Events\Kpi\KpiPlanApproved($plan, \App\Models\User::find($userId)));

        return $plan;
    }

    /**
     * Рейтинг сотрудников по проценту выполнения за период
     *
     * @return array<int, array{rank: int, employee_id: int, employee_name: string, avg_achievement: float, total_bonus: float, plans_count: int}>
     */
    public function getEmployeeRanking(int $companyId, int $year, int $month): array
    {
        // SQL агрегация вместо загрузки всех планов и группировки в PHP
        $results = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->where('status', '!=', KpiPlan::STATUS_CANCELLED)
            ->select([
                'employee_id',
                DB::raw('AVG(achievement_percent) as avg_achievement'),
                DB::raw('SUM(bonus_amount) as total_bonus'),
                DB::raw('COUNT(*) as plans_count'),
            ])
            ->groupBy('employee_id')
            ->orderByDesc('avg_achievement')
            ->get();

        // Подгрузить имена сотрудников одним запросом
        $employeeIds = $results->pluck('employee_id')->toArray();
        $employeeModels = Employee::whereIn('id', $employeeIds)->get(['id', 'first_name', 'last_name', 'middle_name'])->keyBy('id');

        $rank = 0;

        return $results->map(function ($row) use (&$rank, $employeeModels) {
            $rank++;

            $employee = $employeeModels[$row->employee_id] ?? null;
            $employeeName = $employee ? $employee->full_name : 'Сотрудник #' . $row->employee_id;

            return [
                'rank' => $rank,
                'employee_id' => (int) $row->employee_id,
                'employee_name' => $employeeName,
                'avg_achievement' => round((float) $row->avg_achievement, 2),
                'total_bonus' => round((float) $row->total_bonus, 2),
                'plans_count' => (int) $row->plans_count,
            ];
        })->toArray();
    }

    /**
     * Прогноз выполнения KPI на конец месяца
     *
     * @return array{days_elapsed: int, total_days: int, progress_percent: float, forecast_revenue: float, forecast_achievement: float, on_track_count: int, at_risk_count: int, plans: array}
     */
    public function getForecast(int $companyId, int $year, int $month): array
    {
        $periodDate = Carbon::create($year, $month, 1);
        $totalDays = $periodDate->daysInMonth;
        $now = now();

        // Определяем сколько дней прошло
        if ($now->year === $year && $now->month === $month) {
            $daysElapsed = min($now->day, $totalDays);
        } else {
            $daysElapsed = $totalDays;
        }

        $progressRatio = $totalDays > 0 ? $daysElapsed / $totalDays : 0;

        $plans = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->whereIn('status', [KpiPlan::STATUS_ACTIVE, KpiPlan::STATUS_CALCULATED])
            ->select([
                'id', 'company_id', 'employee_id', 'kpi_sales_sphere_id',
                'actual_revenue', 'actual_margin', 'actual_orders',
                'target_revenue', 'target_margin', 'target_orders',
                'weight_revenue', 'weight_margin', 'weight_orders',
                'status',
            ])
            ->with(['employee:id,first_name,last_name,middle_name,company_id', 'salesSphere:id,name'])
            ->get();

        $onTrackCount = 0;
        $atRiskCount = 0;
        $forecastRevenue = 0.0;
        $forecastAchievements = [];
        $planDetails = [];

        foreach ($plans as $plan) {
            $forecastAchievement = 0.0;
            $planForecastRevenue = 0.0;
            $planForecastOrders = 0;

            if ($progressRatio > 0.1) {
                $planForecastRevenue = $plan->actual_revenue / $progressRatio;
                $planForecastOrders = (int) round($plan->actual_orders / $progressRatio);

                // Пересчитываем achievement с прогнозными данными используя веса
                $revenuePct = $plan->target_revenue > 0
                    ? min($planForecastRevenue / $plan->target_revenue * 100, 200)
                    : 0;
                $marginPct = $plan->target_margin > 0
                    ? min(($plan->actual_margin / $progressRatio) / $plan->target_margin * 100, 200)
                    : 0;
                $ordersPct = $plan->target_orders > 0
                    ? min($planForecastOrders / $plan->target_orders * 100, 200)
                    : 0;

                $totalWeight = $plan->weight_revenue + $plan->weight_margin + $plan->weight_orders;
                $forecastAchievement = $totalWeight > 0
                    ? round(($revenuePct * $plan->weight_revenue + $marginPct * $plan->weight_margin + $ordersPct * $plan->weight_orders) / $totalWeight, 2)
                    : 0;
            }

            if ($forecastAchievement >= 100) {
                $onTrackCount++;
            } elseif ($forecastAchievement < 80) {
                $atRiskCount++;
            }

            $forecastRevenue += $planForecastRevenue;
            if ($forecastAchievement > 0) {
                $forecastAchievements[] = $forecastAchievement;
            }

            $planDetails[] = [
                'plan_id' => $plan->id,
                'employee_name' => $plan->employee?->name ?? '—',
                'sphere_name' => $plan->salesSphere?->name ?? '—',
                'actual_revenue' => $plan->actual_revenue,
                'forecast_revenue' => round($planForecastRevenue, 2),
                'forecast_orders' => $planForecastOrders,
                'forecast_achievement' => $forecastAchievement,
            ];
        }

        $avgForecastAchievement = count($forecastAchievements) > 0
            ? round(array_sum($forecastAchievements) / count($forecastAchievements), 2)
            : 0;

        return [
            'days_elapsed' => $daysElapsed,
            'total_days' => $totalDays,
            'progress_percent' => round($progressRatio * 100, 1),
            'forecast_revenue' => round($forecastRevenue, 2),
            'forecast_achievement' => $avgForecastAchievement,
            'on_track_count' => $onTrackCount,
            'at_risk_count' => $atRiskCount,
            'plans' => $planDetails,
        ];
    }

    /**
     * Сводка KPI по компании за период (для дашборда)
     */
    public function getDashboard(int $companyId, int $year, int $month): array
    {
        // Одна SQL агрегация вместо загрузки всех записей и подсчёта в PHP
        $stats = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->where('status', '!=', KpiPlan::STATUS_CANCELLED)
            ->select([
                DB::raw('COUNT(DISTINCT employee_id) as employees'),
                DB::raw('AVG(achievement_percent) as avg_achievement'),
                DB::raw('SUM(bonus_amount) as total_bonus'),
                DB::raw('SUM(actual_revenue) as total_revenue'),
                DB::raw('SUM(target_revenue) as target_revenue'),
            ])
            ->first();

        // Отдельный запрос для списка планов (нужен для отображения)
        $plans = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->where('status', '!=', KpiPlan::STATUS_CANCELLED)
            ->with(['employee:id,first_name,last_name,middle_name,company_id', 'salesSphere:id,name'])
            ->get();

        return [
            'employees' => (int) ($stats->employees ?? 0),
            'avg_achievement' => round((float) ($stats->avg_achievement ?? 0), 1),
            'total_bonus' => (float) ($stats->total_bonus ?? 0),
            'total_revenue' => (float) ($stats->total_revenue ?? 0),
            'target_revenue' => (float) ($stats->target_revenue ?? 0),
            'plans' => $plans,
        ];
    }
}
