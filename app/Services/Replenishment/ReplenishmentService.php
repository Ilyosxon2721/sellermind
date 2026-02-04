<?php

namespace App\Services\Replenishment;

use App\Models\Replenishment\ReplenishmentSetting;
use App\Models\Replenishment\ReplenishmentSnapshot;
use App\Services\Warehouse\StockBalanceService;
use Illuminate\Support\Collection;

class ReplenishmentService
{
    public function __construct(
        protected DemandService $demandService,
        protected StockBalanceService $balanceService
    ) {}

    public function calculate(int $companyId, int $warehouseId, int $skuId): ?array
    {
        $setting = ReplenishmentSetting::byCompany($companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('sku_id', $skuId)
            ->first();

        if (! $setting || ! $setting->is_enabled) {
            return null;
        }

        $balances = $this->balanceService->balance($companyId, $warehouseId, $skuId);
        $avgDaily = $this->demandService->avgDailyDemand(
            $companyId,
            $warehouseId,
            $skuId,
            $setting->demand_window_days
        );

        return $this->calculateFromSetting($setting, $balances, $avgDaily);
    }

    public function calculateFromSetting(ReplenishmentSetting $setting, array $balances, float $avgDaily): array
    {
        $available = (float) $balances['available'];
        $leadTimeDays = (int) $setting->lead_time_days;
        $reviewPeriod = (int) $setting->review_period_days;
        $safety = (float) $setting->safety_stock;
        $roundingStep = max((float) $setting->rounding_step, 0.0001);
        $minOrder = max((float) $setting->min_order_qty, 0);

        $reorderQty = 0;
        $target = 0;

        if ($setting->policy === 'MIN_MAX' && $setting->min_qty !== null && $setting->max_qty !== null) {
            $target = (float) $setting->max_qty;
            if ($available < (float) $setting->min_qty) {
                $reorderQty = $target - $available;
            }
        } else {
            // ROP by default
            $leadDemand = $avgDaily * $leadTimeDays;
            $target = $leadDemand + $safety;
            if ($available <= $target) {
                $reorderQty = $target - $available;
            }
        }

        // Rounding and minimums
        if ($reorderQty > 0) {
            $reorderQty = ceil($reorderQty / $roundingStep) * $roundingStep;
            if ($reorderQty < $minOrder) {
                $reorderQty = $minOrder;
            }
        } else {
            $reorderQty = 0;
        }

        // Risk / stockout
        $nextStockout = null;
        $risk = 'LOW';
        if ($avgDaily > 0) {
            $daysCover = $available / $avgDaily;
            $nextStockout = now()->addDays((int) floor($daysCover))->toDateString();
            if ($daysCover < $leadTimeDays) {
                $risk = 'HIGH';
            } elseif ($daysCover < ($leadTimeDays + $reviewPeriod)) {
                $risk = 'MEDIUM';
            }
        }

        return [
            'company_id' => $setting->company_id,
            'warehouse_id' => $setting->warehouse_id,
            'sku_id' => $setting->sku_id,
            'available' => $available,
            'on_hand' => $balances['on_hand'],
            'reserved' => $balances['reserved'],
            'avg_daily_demand' => $avgDaily,
            'lead_time_days' => $leadTimeDays,
            'safety_stock' => $safety,
            'reorder_qty' => max(0, $reorderQty),
            'risk_level' => $risk,
            'next_stockout_date' => $nextStockout,
            'setting' => $setting,
        ];
    }

    public function calculateAll(int $companyId, int $warehouseId, array $filters = []): Collection
    {
        $settings = ReplenishmentSetting::byCompany($companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('is_enabled', true)
            ->get();

        $skuIds = $settings->pluck('sku_id')->all();
        if (empty($skuIds)) {
            return collect();
        }

        $avgMap = $this->demandService->bulkAvgDailyDemand(
            $companyId,
            $warehouseId,
            $skuIds,
            $filters['demand_window_days'] ?? $settings->first()->demand_window_days ?? 30
        );

        // Fetch balances in bulk
        $balances = app(StockBalanceService::class)->bulkBalance($companyId, $warehouseId, $skuIds);

        return $settings->map(function (ReplenishmentSetting $setting) use ($balances, $avgMap) {
            $balance = $balances[$setting->sku_id] ?? ['on_hand' => 0, 'reserved' => 0, 'available' => 0];
            $avg = $avgMap[$setting->sku_id] ?? 0;

            return $this->calculateFromSetting($setting, $balance, $avg);
        })->filter();
    }

    public function persistSnapshot(array $result): ReplenishmentSnapshot
    {
        return ReplenishmentSnapshot::create([
            'company_id' => $result['company_id'],
            'warehouse_id' => $result['warehouse_id'],
            'sku_id' => $result['sku_id'],
            'calculated_at' => now(),
            'on_hand' => $result['on_hand'],
            'reserved' => $result['reserved'],
            'available' => $result['available'],
            'avg_daily_demand' => $result['avg_daily_demand'],
            'lead_time_days' => $result['lead_time_days'],
            'safety_stock' => $result['safety_stock'],
            'reorder_qty' => $result['reorder_qty'],
            'risk_level' => $result['risk_level'],
            'next_stockout_date' => $result['next_stockout_date'],
            'meta_json' => [
                'policy' => $result['setting']->policy ?? null,
            ],
        ]);
    }
}
