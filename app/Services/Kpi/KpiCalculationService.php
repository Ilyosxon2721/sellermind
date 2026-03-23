<?php

declare(strict_types=1);

namespace App\Services\Kpi;

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
    /**
     * Статусы отменённых заказов (исключаются из расчёта)
     */
    private const CANCELLED_STATUSES = ['cancelled', 'canceled', 'CANCELED', 'PENDING_CANCELLATION'];

    /**
     * Собрать фактические данные по сфере за период
     *
     * @return array{revenue: float, margin: float, orders: int}
     */
    public function collectActuals(KpiPlan $plan): array
    {
        $sphere = $plan->salesSphere;

        // Если сфера привязана к типам ручных продаж — автосбор из OfflineSale
        if ($sphere->hasOfflineSaleLink()) {
            return $this->collectOfflineSaleActuals($plan, $sphere);
        }

        // Если сфера не привязана ни к МП, ни к ручным продажам — ручной ввод
        if (! $sphere->hasMarketplaceLink()) {
            return [
                'revenue' => $plan->actual_revenue,
                'margin' => $plan->actual_margin,
                'orders' => $plan->actual_orders,
            ];
        }

        // Автосбор из таблиц заказов маркетплейсов
        return $this->collectMarketplaceActuals($plan, $sphere);
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
        $totalOrders = 0;

        foreach ($accountsByMarketplace as $marketplace => $mpAccounts) {
            $mpAccountIds = $mpAccounts->pluck('id')->toArray();
            $stats = $this->getMarketplaceStats($marketplace, $mpAccountIds, $periodStart, $periodEnd);
            $totalRevenue += $stats['revenue'];
            $totalOrders += $stats['orders'];
        }

        return [
            'revenue' => $totalRevenue,
            'margin' => 0.0, // Маржа недоступна из заказов маркетплейсов (нет себестоимости)
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
                ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
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
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
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
            ->whereNotIn('status', self::CANCELLED_STATUSES)
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
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
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

        return $plan;
    }

    /**
     * Массовый расчёт всех активных планов за период
     */
    public function calculatePeriod(int $companyId, int $year, int $month): Collection
    {
        $plans = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->where('status', '!=', KpiPlan::STATUS_CANCELLED)
            ->with(['salesSphere', 'bonusScale.tiers', 'employee'])
            ->get();

        return $plans->map(fn (KpiPlan $plan) => $this->calculatePlan($plan));
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

        return $plan->fresh();
    }

    /**
     * Сводка KPI по компании за период (для дашборда)
     */
    public function getDashboard(int $companyId, int $year, int $month): array
    {
        $plans = KpiPlan::byCompany($companyId)
            ->forPeriod($year, $month)
            ->where('status', '!=', KpiPlan::STATUS_CANCELLED)
            ->with(['employee', 'salesSphere'])
            ->get();

        $employeeCount = $plans->pluck('employee_id')->unique()->count();
        $avgAchievement = $plans->count() > 0 ? round($plans->avg('achievement_percent'), 1) : 0;
        $totalBonus = $plans->sum('bonus_amount');
        $totalTargetRevenue = $plans->sum('target_revenue');
        $totalActualRevenue = $plans->sum('actual_revenue');

        return [
            'employees' => $employeeCount,
            'avg_achievement' => $avgAchievement,
            'total_bonus' => $totalBonus,
            'total_revenue' => $totalActualRevenue,
            'target_revenue' => $totalTargetRevenue,
            'plans' => $plans,
        ];
    }
}
