<?php

namespace App\Services\Replenishment;

use App\Models\Purchase\PurchaseOrder;
use App\Models\Purchase\PurchaseOrderLine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class PurchaseDraftService
{
    public function createDraft(int $companyId, int $warehouseId, ?int $supplierId, array $items): PurchaseOrder
    {
        if (empty($items)) {
            throw new RuntimeException('Items required');
        }

        return DB::transaction(function () use ($companyId, $warehouseId, $supplierId, $items) {
            $po = PurchaseOrder::create([
                'company_id' => $companyId,
                'warehouse_id' => $warehouseId,
                'supplier_id' => $supplierId,
                'po_no' => $this->poNumber(),
                'status' => 'DRAFT',
                'expected_date' => now()->addDays(7)->toDateString(),
            ]);

            $total = 0;
            foreach ($items as $item) {
                $qty = (float) $item['qty'];
                $unitCost = (float) ($item['unit_cost'] ?? $this->latestCost($companyId, $item['sku_id']));
                $line = PurchaseOrderLine::create([
                    'purchase_order_id' => $po->id,
                    'sku_id' => $item['sku_id'],
                    'qty' => $qty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $qty * $unitCost,
                ]);
                $total += $line->total_cost;
            }

            $po->update(['total_amount' => $total]);

            return $po->load('lines');
        });
    }

    protected function poNumber(): string
    {
        return 'PO-'.now()->format('Ymd-His').'-'.Str::random(4);
    }

    protected function latestCost(int $companyId, int $skuId): float
    {
        $row = DB::table('goods_receipt_lines')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->orderByDesc('id')
            ->first();

        return $row?->unit_cost ?? 0;
    }
}
