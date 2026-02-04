<?php

namespace App\Services\Pricing;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PriceCostService
{
    /**
     * Determine unit cost with confidence.
     */
    public function unitCost(int $companyId, int $skuId, ?string $date = null): array
    {
        // 1) SKU override
        $override = DB::table('pricing_sku_overrides')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->whereNotNull('cost_override')
            ->orderByDesc('id')
            ->value('cost_override');
        if ($override !== null) {
            return ['cost' => (float) $override, 'confidence' => 'HIGH', 'source' => 'override'];
        }

        // 2) Last GRN unit cost
        if (Schema::hasTable('goods_receipt_lines')) {
            $last = DB::table('goods_receipt_lines')
                ->where('company_id', $companyId)
                ->where('sku_id', $skuId)
                ->orderByDesc('id')
                ->value('unit_cost');
            if ($last !== null) {
                return ['cost' => (float) $last, 'confidence' => 'MEDIUM', 'source' => 'last_grn'];
            }
        }

        // 3) Avg cost (if stock_ledger has cost)
        $avg = DB::table('stock_ledger')
            ->where('company_id', $companyId)
            ->where('sku_id', $skuId)
            ->avg('cost_delta');
        if ($avg) {
            return ['cost' => (float) $avg, 'confidence' => 'LOW', 'source' => 'avg_cost_delta'];
        }

        return ['cost' => 0.0, 'confidence' => 'LOW', 'source' => 'missing'];
    }
}
