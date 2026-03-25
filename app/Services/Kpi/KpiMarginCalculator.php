<?php

declare(strict_types=1);

namespace App\Services\Kpi;

use App\Models\Kpi\KpiPlan;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Расчёт маржи из заказов маркетплейсов через себестоимость товаров
 */
final class KpiMarginCalculator
{
    /**
     * Рассчитать маржу по маркетплейсу за период
     *
     * @param  string  $marketplace  Идентификатор маркетплейса
     * @param  array<int>  $accountIds  ID аккаунтов маркетплейса
     * @param  Carbon  $periodStart  Начало периода
     * @param  Carbon  $periodEnd  Конец периода
     * @param  int  $companyId  ID компании (для связи с product_variants)
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
            'uzum' => $this->getUzumMargin($accountIds, $periodStart, $periodEnd),
            'ozon' => $this->getOzonMargin($accountIds, $periodStart, $periodEnd),
            'ym', 'yandex_market' => $this->getYmMargin($accountIds, $periodStart, $periodEnd),
            default => 0.0,
        };
    }

    /**
     * Маржа Wildberries: выручка (for_pay) минус себестоимость через barcode
     *
     * Связь: wildberries_orders.barcode → product_variants.barcode → purchase_price
     * Fallback: wb_orders + wb_order_items через sku → product_variants.sku
     */
    private function getWbMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd, int $companyId): float
    {
        // Основной источник — Statistics API (wildberries_orders)
        $row = DB::table('wildberries_orders as wo')
            ->leftJoin('product_variants as pv', function ($join) use ($companyId) {
                $join->on('pv.barcode', '=', 'wo.barcode')
                    ->where('pv.company_id', '=', $companyId)
                    ->whereNotNull('pv.barcode')
                    ->where('pv.barcode', '!=', '');
            })
            ->whereIn('wo.marketplace_account_id', $accountIds)
            ->whereBetween('wo.order_date', [$periodStart, $periodEnd])
            ->where('wo.is_cancel', false)
            ->where('wo.is_return', false)
            ->selectRaw('COALESCE(SUM(wo.for_pay), 0) as revenue, COALESCE(SUM(pv.purchase_price), 0) as total_cost')
            ->first();

        $revenue = (float) ($row->revenue ?? 0);
        $totalCost = (float) ($row->total_cost ?? 0);

        // Если Statistics API не синхронизировался — fallback на FBS заказы (wb_orders + wb_order_items)
        if ($revenue == 0) {
            return $this->getWbFbsMargin($accountIds, $periodStart, $periodEnd, $companyId);
        }

        return max(0.0, $revenue - $totalCost);
    }

    /**
     * Маржа WB FBS: wb_orders + wb_order_items через sku → product_variants.sku
     */
    private function getWbFbsMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd, int $companyId): float
    {
        $row = DB::table('wb_orders as wo')
            ->join('wb_order_items as woi', 'woi.wb_order_id', '=', 'wo.id')
            ->leftJoin('product_variants as pv', function ($join) use ($companyId) {
                $join->on('pv.sku', '=', 'wo.sku')
                    ->where('pv.company_id', '=', $companyId)
                    ->whereNotNull('pv.sku')
                    ->where('pv.sku', '!=', '');
            })
            ->whereIn('wo.marketplace_account_id', $accountIds)
            ->whereBetween('wo.ordered_at', [$periodStart, $periodEnd])
            ->whereNotIn('wo.status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(woi.total_price), 0) as revenue, COALESCE(SUM(pv.purchase_price * woi.quantity), 0) as total_cost')
            ->first();

        $revenue = (float) ($row->revenue ?? 0);
        $totalCost = (float) ($row->total_cost ?? 0);

        return max(0.0, $revenue - $totalCost);
    }

    /**
     * Маржа Uzum: uzum_order_items через external_offer_id → product_variants.sku
     */
    private function getUzumMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd): float
    {
        $row = DB::table('uzum_order_items as uoi')
            ->join('uzum_orders as uo', 'uo.id', '=', 'uoi.uzum_order_id')
            ->leftJoin('product_variants as pv', function ($join) {
                $join->on('pv.sku', '=', 'uoi.external_offer_id')
                    ->whereNotNull('pv.sku')
                    ->where('pv.sku', '!=', '');
            })
            ->whereIn('uo.marketplace_account_id', $accountIds)
            ->whereBetween('uo.ordered_at', [$periodStart, $periodEnd])
            ->whereNotIn('uo.status_normalized', KpiPlan::CANCELLED_ORDER_STATUSES)
            ->selectRaw('COALESCE(SUM(uoi.total_price), 0) as revenue, COALESCE(SUM(pv.purchase_price * uoi.quantity), 0) as total_cost')
            ->first();

        $revenue = (float) ($row->revenue ?? 0);
        $totalCost = (float) ($row->total_cost ?? 0);

        return max(0.0, $revenue - $totalCost);
    }

    /**
     * Маржа Ozon: упрощённый подход через среднюю себестоимость из marketplace_products
     *
     * Точный расчёт невозможен т.к. items хранятся в JSON.
     * Используем: margin = revenue - (avg_purchase_price * total_orders)
     */
    private function getOzonMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Выручка и количество заказов
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

        // Средняя себестоимость товаров аккаунтов
        $avgCost = $this->getAvgPurchasePrice($accountIds);

        if ($avgCost <= 0) {
            return 0.0;
        }

        return max(0.0, $revenue - ($avgCost * $totalOrders));
    }

    /**
     * Маржа Yandex Market: упрощённый подход через среднюю себестоимость из marketplace_products
     *
     * Аналогично Ozon — items в JSON, используем средневзвешенную оценку.
     */
    private function getYmMargin(array $accountIds, Carbon $periodStart, Carbon $periodEnd): float
    {
        // Выручка и количество заказов
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

        // Средняя себестоимость товаров аккаунтов
        $avgCost = $this->getAvgPurchasePrice($accountIds);

        if ($avgCost <= 0) {
            return 0.0;
        }

        return max(0.0, $revenue - ($avgCost * $totalOrders));
    }

    /**
     * Получить среднюю себестоимость товаров из marketplace_products для указанных аккаунтов
     */
    private function getAvgPurchasePrice(array $accountIds): float
    {
        $row = DB::table('marketplace_products')
            ->whereIn('marketplace_account_id', $accountIds)
            ->where('purchase_price', '>', 0)
            ->selectRaw('AVG(purchase_price) as avg_cost')
            ->first();

        return (float) ($row->avg_cost ?? 0);
    }
}
