<?php

namespace App\Services\Warehouse;

use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockBalanceService
{
    public function balance(int $companyId, int $warehouseId, int $skuId, ?int $locationId = null): array
    {
        $onHand = $this->sumLedger($companyId, [$warehouseId], [$skuId], $locationId);
        $reserved = $this->sumReservations($companyId, [$warehouseId], [$skuId], $locationId);

        return [
            'on_hand' => $onHand,
            'reserved' => $reserved,
            'available' => $onHand - $reserved,
        ];
    }

    public function bulkBalance(int $companyId, int $warehouseId, array $skuIds): Collection
    {
        $ledger = StockLedger::query()
            ->select('sku_id', DB::raw('SUM(qty_delta) as on_hand'))
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('sku_id', $skuIds)
            ->groupBy('sku_id')
            ->pluck('on_hand', 'sku_id');

        $reservations = StockReservation::query()
            ->select('sku_id', DB::raw('SUM(qty) as reserved'))
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('status', StockReservation::STATUS_ACTIVE)
            ->whereIn('sku_id', $skuIds)
            ->groupBy('sku_id')
            ->pluck('reserved', 'sku_id');

        return collect($skuIds)->mapWithKeys(function ($skuId) use ($ledger, $reservations) {
            $onHand = (float) ($ledger[$skuId] ?? 0);
            $reserved = (float) ($reservations[$skuId] ?? 0);
            return [
                $skuId => [
                    'on_hand' => $onHand,
                    'reserved' => $reserved,
                    'available' => $onHand - $reserved,
                ],
            ];
        });
    }

    protected function sumLedger(int $companyId, array $warehouseIds, array $skuIds, ?int $locationId = null): float
    {
        $query = StockLedger::query()
            ->where('company_id', $companyId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('sku_id', $skuIds);
        if ($locationId) {
            $query->where('location_id', $locationId);
        }
        return (float) $query->sum('qty_delta');
    }

    protected function sumReservations(int $companyId, array $warehouseIds, array $skuIds, ?int $locationId = null): float
    {
        $query = StockReservation::query()
            ->where('company_id', $companyId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('sku_id', $skuIds)
            ->where('status', StockReservation::STATUS_ACTIVE);
        // location is optional; if enabled, extend model/table later
        return (float) $query->sum('qty');
    }

    /**
     * Get cost data for SKUs
     * Returns total cost and average unit cost for each SKU
     */
    public function bulkCost(int $companyId, int $warehouseId, array $skuIds): Collection
    {
        // Get total cost and qty for each SKU
        $costs = StockLedger::query()
            ->select('sku_id', DB::raw('SUM(cost_delta) as total_cost'), DB::raw('SUM(qty_delta) as total_qty'))
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->whereIn('sku_id', $skuIds)
            ->groupBy('sku_id')
            ->get()
            ->keyBy('sku_id');

        return collect($skuIds)->mapWithKeys(function ($skuId) use ($costs) {
            $data = $costs[$skuId] ?? null;
            $totalCost = (float) ($data?->total_cost ?? 0);
            $totalQty = (float) ($data?->total_qty ?? 0);

            // Calculate average unit cost
            $unitCost = $totalQty > 0 ? $totalCost / $totalQty : 0;

            return [
                $skuId => [
                    'total_cost' => max(0, $totalCost),
                    'unit_cost' => max(0, $unitCost),
                    'qty' => $totalQty,
                ],
            ];
        });
    }

    /**
     * Update cost for SKU by creating an adjustment ledger entry
     *
     * @param int $companyId
     * @param int $warehouseId
     * @param int $skuId
     * @param float $newUnitCost New unit cost in UZS
     * @param int|null $userId
     * @return array
     */
    public function updateCost(int $companyId, int $warehouseId, int $skuId, float $newUnitCost, ?int $userId = null): array
    {
        // Get current balance and cost
        $currentData = StockLedger::query()
            ->select(DB::raw('SUM(cost_delta) as total_cost'), DB::raw('SUM(qty_delta) as total_qty'))
            ->where('company_id', $companyId)
            ->where('warehouse_id', $warehouseId)
            ->where('sku_id', $skuId)
            ->first();

        $currentQty = (float) ($currentData?->total_qty ?? 0);
        $currentTotalCost = (float) ($currentData?->total_cost ?? 0);

        if ($currentQty <= 0) {
            throw new \RuntimeException('No stock available to update cost');
        }

        // Calculate new total cost and adjustment
        $newTotalCost = $currentQty * $newUnitCost;
        $costAdjustment = $newTotalCost - $currentTotalCost;

        if (abs($costAdjustment) < 0.01) {
            return [
                'adjusted' => false,
                'message' => 'No adjustment needed',
                'old_unit_cost' => $currentTotalCost / $currentQty,
                'new_unit_cost' => $newUnitCost,
            ];
        }

        // Create adjustment ledger entry
        $entry = StockLedger::create([
            'company_id' => $companyId,
            'warehouse_id' => $warehouseId,
            'sku_id' => $skuId,
            'occurred_at' => now(),
            'qty_delta' => 0, // No quantity change
            'cost_delta' => $costAdjustment,
            'currency_code' => 'UZS',
            'source_type' => 'COST_ADJUSTMENT',
            'source_id' => null,
            'created_by' => $userId,
        ]);

        return [
            'adjusted' => true,
            'ledger_entry_id' => $entry->id,
            'old_total_cost' => $currentTotalCost,
            'new_total_cost' => $newTotalCost,
            'adjustment' => $costAdjustment,
            'old_unit_cost' => $currentTotalCost / $currentQty,
            'new_unit_cost' => $newUnitCost,
            'qty' => $currentQty,
        ];
    }
}
