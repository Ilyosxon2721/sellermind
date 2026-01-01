<?php

namespace App\Services\Replenishment;

use App\Models\Warehouse\StockLedger;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DemandService
{
    public function avgDailyDemand(int $companyId, int $warehouseId, int $skuId, int $windowDays): float
    {
        $now = now();
        $from = $now->copy()->subDays($windowDays);

        $demand = $this->baseQuery($companyId, [$warehouseId], [$skuId], $from, $now)
            ->sum(\DB::raw('ABS(stock_ledger.qty_delta)'));

        return $windowDays > 0 ? $demand / $windowDays : 0;
    }

    public function bulkAvgDailyDemand(int $companyId, int $warehouseId, array $skuIds, int $windowDays): Collection
    {
        $now = now();
        $from = $now->copy()->subDays($windowDays);

        $rows = $this->baseQuery($companyId, [$warehouseId], $skuIds, $from, $now)
            ->select('stock_ledger.sku_id', DB::raw('SUM(ABS(stock_ledger.qty_delta)) as demand'))
            ->groupBy('stock_ledger.sku_id')
            ->pluck('demand', 'sku_id');

        return collect($skuIds)->mapWithKeys(function ($skuId) use ($rows, $windowDays) {
            $demand = (float) ($rows[$skuId] ?? 0);
            $avg = $windowDays > 0 ? $demand / $windowDays : 0;
            return [$skuId => $avg];
        });
    }

    protected function baseQuery(int $companyId, array $warehouseIds, array $skuIds, $from, $to)
    {
        return StockLedger::query()
            ->join('inventory_documents', 'inventory_documents.id', '=', 'stock_ledger.document_id')
            ->where('stock_ledger.company_id', $companyId)
            ->whereIn('stock_ledger.warehouse_id', $warehouseIds)
            ->whereIn('stock_ledger.sku_id', $skuIds)
            ->whereBetween('stock_ledger.occurred_at', [$from, $to])
            ->whereIn('inventory_documents.type', [
                \App\Models\Warehouse\InventoryDocument::TYPE_OUT,
                \App\Models\Warehouse\InventoryDocument::TYPE_WRITE_OFF,
            ])
            ->select(DB::raw('ABS(stock_ledger.qty_delta) as qty'));
    }
}
