<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Models\Kpi\KpiPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Расчёт маржи из заказов маркетплейсов через себестоимость внутренних товаров
 *
 * Цепочка: order_item → variant_marketplace_links → product_variants.purchase_price
 */
final class KpiMarginCalculator
{
    /**
     * Рассчитать маржу по маркетплейсу за период
     */
    public function calculateMargin(
        string $marketplace,
        array $accountIds,
        Carbon $periodStart,
        Carbon $periodEnd,
        int $companyId,
    ): float {
        if (empty($accountIds)) {
            return 0.0;
        }

        return match ($marketplace) {
            'wb', 'wildberries' => $this->getWbMargin($accountIds, $periodStart, $periodEnd, $companyId),
            'uzum' => $this->getUzumMargin($accountIds, $periodStart, $periodEnd, $companyId),
            'ozon' => $this->getOzonMargin($accountIds, $periodStart, $periodEnd, $companyId),
            'ym', 'yandex_market' => $this->getYmMargin($accountIds, $periodStart, $periodEnd, $companyId),
            default => 0.0,
        };
    }

    /**
     * Маржа Wildberries Statistics API (wildberries_orders)
     *
     * Связь: wildberries_orders.barcode → variant_marketplace_links.marketplace_barcode
     *        → product_variants.purchase_price
     */
    private function getWbMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd, int $companyId): float
    {
        $row = DB::table('wildberries_orders as wo')
            ->leftJoin('variant_marketplace_links as vml', function ($join) use ($accountIds) {
                $join->on('vml.marketplace_barcode', '=', 'wo.barcode')
                    ->whereIn('vml.marketplace_account_id', $accountIds)
                    ->where('vml.is_active', true);
            })
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('wo.marketplace_account_id', $accountIds)
            ->whereBetween('wo.order_date', [$periodStart, $periodEnd])
            ->where('wo.is_cancel', false)
            ->where('wo.is_return', false)
            ->selectRaw('COALESCE(SUM(wo.for_pay), 0) as revenue, COALESCE(SUM(pv.purchase_price), 0) as total_cost')
            ->first();

        $revenue = (float) ($row->revenue ?? 0);
        $totalCost = (float) ($row->total_cost ?? 0);

        // Если Statistics API не синхронизировался — fallback на FBS заказы
        if ($revenue == 0) {
            return $this->getWbFbsMargin($accountIds, $periodStart, $periodEnd, $companyId);
        }

        if ($totalCost == 0) {
            return 0.0;
        }

        return max(0.0, $revenue - $totalCost);
    }

    /**
     * Маржа WB FBS (wb_orders + wb_order_items)
     *
     * Связь: wb_order_items.external_offer_id → variant_marketplace_links.external_offer_id
     *        → product_variants.purchase_price
     */
    private function getWbFbsMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd, int $companyId): float
    {
        $row = DB::table('wb_orders as wo')
            ->join('wb_order_items as woi', 'woi.wb_order_id', '=', 'wo.id')
            ->leftJoin('variant_marketplace_links as vml', function ($join) use ($accountIds) {
                $join->on('vml.external_offer_id', '=', 'woi.external_offer_id')
                    ->whereIn('vml.marketplace_account_id', $accountIds)
                    ->where('vml.is_active', true);
            })
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('wo.marketplace_account_id', $accountIds)
            ->whereBetween('wo.ordered_at', [$periodStart, $periodEnd])
            ->whereNotIn('wo.status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(woi.total_price), 0) as revenue, COALESCE(SUM(pv.purchase_price * woi.quantity), 0) as total_cost')
            ->first();

        $revenue = (float) ($row->revenue ?? 0);
        $totalCost = (float) ($row->total_cost ?? 0);

        if ($totalCost == 0) {
            return 0.0;
        }

        return max(0.0, $revenue - $totalCost);
    }

    /**
     * Маржа Uzum (uzum_order_items)
     *
     * Связь: uzum_order_items.external_offer_id → variant_marketplace_links.external_offer_id
     *        (fallback на external_sku_id) → product_variants.purchase_price
     */
    private function getUzumMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd, int $companyId): float
    {
        $row = DB::table('uzum_order_items as uoi')
            ->join('uzum_orders as uo', 'uo.id', '=', 'uoi.uzum_order_id')
            ->leftJoin('variant_marketplace_links as vml', function ($join) use ($accountIds) {
                $join->where(function ($q) use ($accountIds) {
                    $q->whereColumn('vml.external_sku_id', '=', 'uoi.external_offer_id')
                        ->orWhereColumn('vml.external_offer_id', '=', 'uoi.external_offer_id');
                })
                    ->whereIn('vml.marketplace_account_id', $accountIds)
                    ->where('vml.is_active', true);
            })
            ->leftJoin('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('uo.marketplace_account_id', $accountIds)
            ->whereBetween('uo.ordered_at', [$periodStart, $periodEnd])
            ->whereNotIn('uo.status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(uoi.total_price), 0) as revenue, COALESCE(SUM(pv.purchase_price * uoi.quantity), 0) as total_cost')
            ->first();

        $revenue = (float) ($row->revenue ?? 0);
        $totalCost = (float) ($row->total_cost ?? 0);

        if ($totalCost == 0) {
            return 0.0;
        }

        return max(0.0, $revenue - $totalCost);
    }

    /**
     * Маржа Ozon
     *
     * Ozon хранит items в JSON, поэтому используем среднюю себестоимость
     * из привязанных через variant_marketplace_links внутренних товаров
     */
    private function getOzonMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd, int $companyId): float
    {
        $ordersRow = DB::table('ozon_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$periodStart, $periodEnd])
            ->whereNotIn('status', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue, COUNT(*) as total_orders')
            ->first();

        $revenue = (float) ($ordersRow->revenue ?? 0);
        $totalOrders = (int) ($ordersRow->total_orders ?? 0);

        if ($revenue == 0 || $totalOrders == 0) {
            return 0.0;
        }

        $avgCost = $this->getAvgCostViaLinks($accountIds);

        if ($avgCost <= 0) {
            return 0.0;
        }

        return max(0.0, $revenue - ($avgCost * $totalOrders));
    }

    /**
     * Маржа Yandex Market
     *
     * Аналогично Ozon — items в JSON, используем среднюю себестоимость
     * из привязанных через variant_marketplace_links внутренних товаров
     */
    private function getYmMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd, int $companyId): float
    {
        $ordersRow = DB::table('yandex_market_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$periodStart, $periodEnd])
            ->whereNotIn('status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(total_price), 0) as revenue, COUNT(*) as total_orders')
            ->first();

        $revenue = (float) ($ordersRow->revenue ?? 0);
        $totalOrders = (int) ($ordersRow->total_orders ?? 0);

        if ($revenue == 0 || $totalOrders == 0) {
            return 0.0;
        }

        $avgCost = $this->getAvgCostViaLinks($accountIds);

        if ($avgCost <= 0) {
            return 0.0;
        }

        return max(0.0, $revenue - ($avgCost * $totalOrders));
    }

    /**
     * Средняя себестоимость через variant_marketplace_links → product_variants
     *
     * Берёт purchase_price из внутренних товаров, привязанных к МП-аккаунтам
     */
    private function getAvgCostViaLinks(array $accountIds): float
    {
        $row = DB::table('variant_marketplace_links as vml')
            ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('vml.marketplace_account_id', $accountIds)
            ->where('vml.is_active', true)
            ->where('pv.purchase_price', '>', 0)
            ->selectRaw('AVG(pv.purchase_price) as avg_cost')
            ->first();

        return (float) ($row->avg_cost ?? 0);
    }
}
