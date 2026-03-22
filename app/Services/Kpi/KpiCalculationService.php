<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Models\Finance\MarketplacePayout;
use App\Models\Kpi\KpiPlan;
use App\Models\OfflineSale;
use App\Models\OfflineSaleItem;
use App\Models\Sale;
use App\Models\SaleItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Сервис расчёта KPI и бонусов сотрудников
 */
final class KpiCalculationService
{
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

        $accountIds = $sphere->getLinkedAccountIds();
        $periodStart = Carbon::create($plan->period_year, $plan->period_month, 1)->startOfMonth();
        $periodEnd = $periodStart->copy()->endOfMonth();

        // Оборот и заказы из таблицы sales через polymorphic marketplace_order
        $salesData = Sale::where('company_id', $plan->company_id)
            ->where('type', 'marketplace')
            ->whereIn('status', ['confirmed', 'completed'])
            ->whereBetween('created_at', [$periodStart, $periodEnd])
            ->whereHasMorph('marketplaceOrder', ['*'], function ($query) use ($accountIds) {
                $query->whereIn('marketplace_account_id', $accountIds);
            })
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_revenue, COUNT(*) as total_orders')
            ->first();

        $revenue = (float) ($salesData->total_revenue ?? 0);
        $orders = (int) ($salesData->total_orders ?? 0);

        // Если нет данных в Sales, пробуем MarketplacePayout
        if ($revenue == 0) {
            $payoutRevenue = MarketplacePayout::where('company_id', $plan->company_id)
                ->whereIn('marketplace_account_id', $accountIds)
                ->where(function ($q) use ($periodStart, $periodEnd) {
                    $q->whereBetween('payout_date', [$periodStart, $periodEnd])
                        ->orWhere(function ($q2) use ($periodStart, $periodEnd) {
                            $q2->where('period_from', '<=', $periodEnd)
                                ->where('period_to', '>=', $periodStart);
                        });
                })
                ->sum('gross_amount');

            $revenue = (float) $payoutRevenue;
        }

        // Маржа: выручка - себестоимость из SaleItem
        $marginData = SaleItem::whereHas('sale', function ($q) use ($plan, $accountIds, $periodStart, $periodEnd) {
            $q->where('company_id', $plan->company_id)
                ->where('type', 'marketplace')
                ->whereIn('status', ['confirmed', 'completed'])
                ->whereBetween('created_at', [$periodStart, $periodEnd])
                ->whereHasMorph('marketplaceOrder', ['*'], function ($query) use ($accountIds) {
                    $query->whereIn('marketplace_account_id', $accountIds);
                });
        })
            ->selectRaw('COALESCE(SUM(total), 0) as total_sales, COALESCE(SUM(cost_price * quantity), 0) as total_cost')
            ->first();

        $margin = (float) (($marginData->total_sales ?? 0) - ($marginData->total_cost ?? 0));

        return [
            'revenue' => $revenue,
            'margin' => max(0, $margin),
            'orders' => $orders,
        ];
    }

    /**
     * Собрать фактические данные из ручных продаж (OfflineSale) по типам
     *
     * @return array{revenue: float, margin: float, orders: int}
     */
    private function collectOfflineSaleActuals(KpiPlan $plan, \App\Models\Kpi\SalesSphere $sphere): array
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
            'employee_count' => $employeeCount,
            'avg_achievement' => $avgAchievement,
            'total_bonus' => $totalBonus,
            'target_revenue' => $totalTargetRevenue,
            'actual_revenue' => $totalActualRevenue,
            'plans' => $plans,
        ];
    }
}
