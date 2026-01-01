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
}
