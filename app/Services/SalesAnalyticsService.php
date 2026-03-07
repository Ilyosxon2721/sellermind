<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MarketplaceAccount;
use App\Models\WildberriesOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesAnalyticsService
{
    /**
     * Статусы отменённых заказов (исключаются из расчёта выручки)
     */
    private const CANCELLED_STATUSES = ['cancelled', 'canceled', 'CANCELED', 'PENDING_CANCELLATION'];

    /**
     * Получить общую сводку продаж за период.
     * $accountIds запрашивается один раз и передаётся во все вспомогательные методы.
     */
    public function getOverview(int $companyId, string $period = '30days'): array
    {
        $dateRange = $this->getDateRange($period);

        // Один запрос для получения ID аккаунтов (используется во всех helper-методах)
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        // Суммарные продажи (кол-во товаров, исключая отменённые)
        $totalSales = $this->getTotalSales($accountIds, $dateRange);

        // Выручка + кол-во заказов в одном запросе (текущий период)
        $revenueAndOrders = $this->getRevenueAndOrders($accountIds, $dateRange);
        $totalRevenue = $revenueAndOrders['revenue'];
        $totalOrders = $revenueAndOrders['orders'];

        // Отменённые заказы
        $cancelledStats = $this->getCancelledStats($accountIds, $dateRange);

        // Средний чек
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Сравнение с предыдущим периодом (только выручка)
        $prevDateRange = $this->getPreviousDateRange($period);
        $prevRevenue = $this->getRevenueAndOrders($accountIds, $prevDateRange)['revenue'];
        $revenueGrowth = $prevRevenue > 0 ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        return [
            'total_sales' => $totalSales,
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'average_order_value' => round($avgOrderValue, 2),
            'revenue_growth_percentage' => round($revenueGrowth, 2),
            'cancelled_orders' => $cancelledStats['count'],
            'cancelled_amount' => round($cancelledStats['amount'], 2),
            'period' => $period,
            'date_from' => $dateRange['from']->toDateString(),
            'date_to' => $dateRange['to']->toDateString(),
        ];
    }

    /**
     * Получить продажи по дням для графика.
     */
    public function getSalesByDay(int $companyId, string $period = '30days'): array
    {
        $dateRange = $this->getDateRange($period);

        $sales = DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->select(
                DB::raw('DATE(marketplace_orders.created_at) as date'),
                DB::raw('SUM(marketplace_order_items.quantity) as quantity'),
                DB::raw('SUM(marketplace_order_items.price * marketplace_order_items.quantity) as revenue')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'labels' => $sales->pluck('date')->toArray(),
            'quantities' => $sales->pluck('quantity')->map(fn ($q) => (int) $q)->toArray(),
            'revenues' => $sales->pluck('revenue')->map(fn ($r) => round((float) $r, 2))->toArray(),
        ];
    }

    /**
     * Получить топ продаваемых товаров.
     */
    public function getTopProducts(int $companyId, string $period = '30days', int $limit = 10): Collection
    {
        $dateRange = $this->getDateRange($period);

        return DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->join('product_variants', 'marketplace_order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'product_variants.sku',
                DB::raw('SUM(marketplace_order_items.quantity) as total_quantity'),
                DB::raw('SUM(marketplace_order_items.price * marketplace_order_items.quantity) as total_revenue'),
                DB::raw('COUNT(DISTINCT marketplace_orders.id) as order_count')
            )
            ->groupBy('products.id', 'products.name', 'product_variants.sku')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => round((float) $item->total_revenue, 2),
                    'order_count' => (int) $item->order_count,
                    'avg_price' => (int) $item->total_quantity > 0
                        ? round((float) $item->total_revenue / (int) $item->total_quantity, 2)
                        : 0,
                ];
            });
    }

    /**
     * Получить аутсайдеров продаж (flop).
     */
    public function getFlopProducts(int $companyId, string $period = '30days', int $limit = 10): Collection
    {
        $dateRange = $this->getDateRange($period);

        // Get products with sales in the period, ordered by least revenue
        $withSales = DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->join('product_variants', 'marketplace_order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->select(
                'products.id as product_id',
                'products.name as product_name',
                'product_variants.sku',
                DB::raw('SUM(marketplace_order_items.quantity) as total_quantity'),
                DB::raw('SUM(marketplace_order_items.price * marketplace_order_items.quantity) as total_revenue'),
                DB::raw('COUNT(DISTINCT marketplace_orders.id) as order_count')
            )
            ->groupBy('products.id', 'products.name', 'product_variants.sku')
            ->orderBy('total_revenue')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'product_id' => $item->product_id,
                    'product_name' => $item->product_name,
                    'sku' => $item->sku,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => round((float) $item->total_revenue, 2),
                    'order_count' => (int) $item->order_count,
                    'avg_price' => (int) $item->total_quantity > 0
                        ? round((float) $item->total_revenue / (int) $item->total_quantity, 2)
                        : 0,
                    'status' => 'low_sales',
                ];
            });

        return $withSales;
    }

    /**
     * Получить продажи по категориям.
     */
    public function getSalesByCategory(int $companyId, string $period = '30days'): Collection
    {
        $dateRange = $this->getDateRange($period);

        return DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->join('product_variants', 'marketplace_order_items.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->select(
                DB::raw('COALESCE(categories.name, "Без категории") as category_name'),
                DB::raw('SUM(marketplace_order_items.quantity) as total_quantity'),
                DB::raw('SUM(marketplace_order_items.price * marketplace_order_items.quantity) as total_revenue')
            )
            ->groupBy('category_name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($item) {
                return [
                    'category_name' => $item->category_name,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => round((float) $item->total_revenue, 2),
                ];
            });
    }

    /**
     * Получить продажи по маркетплейсам.
     */
    public function getSalesByMarketplace(int $companyId, string $period = '30days'): Collection
    {
        $dateRange = $this->getDateRange($period);

        return DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->select(
                'marketplace_accounts.marketplace',
                'marketplace_accounts.name as account_name',
                DB::raw('SUM(marketplace_order_items.quantity) as total_quantity'),
                DB::raw('SUM(marketplace_order_items.price * marketplace_order_items.quantity) as total_revenue'),
                DB::raw('COUNT(DISTINCT marketplace_orders.id) as order_count')
            )
            ->groupBy('marketplace_accounts.marketplace', 'marketplace_accounts.name')
            ->orderByDesc('total_revenue')
            ->get()
            ->map(function ($item) {
                return [
                    'marketplace' => $item->marketplace,
                    'account_name' => $item->account_name,
                    'total_quantity' => (int) $item->total_quantity,
                    'total_revenue' => round((float) $item->total_revenue, 2),
                    'order_count' => (int) $item->order_count,
                ];
            });
    }

    /**
     * Получить метрики производительности товара.
     */
    public function getProductPerformance(int $productId, string $period = '30days'): array
    {
        $dateRange = $this->getDateRange($period);

        $stats = DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('product_variants', 'marketplace_order_items.product_variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->select(
                DB::raw('SUM(marketplace_order_items.quantity) as total_quantity'),
                DB::raw('SUM(marketplace_order_items.price * marketplace_order_items.quantity) as total_revenue'),
                DB::raw('COUNT(DISTINCT marketplace_orders.id) as order_count'),
                DB::raw('AVG(marketplace_order_items.price) as avg_price')
            )
            ->first();

        if (! $stats) {
            return [
                'total_quantity' => 0,
                'total_revenue' => 0,
                'order_count' => 0,
                'avg_price' => 0,
                'sales_by_day' => [],
            ];
        }

        // Sales by day for this product
        $salesByDay = DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('product_variants', 'marketplace_order_items.product_variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->select(
                DB::raw('DATE(marketplace_orders.created_at) as date'),
                DB::raw('SUM(marketplace_order_items.quantity) as quantity')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'total_quantity' => (int) $stats->total_quantity,
            'total_revenue' => round((float) $stats->total_revenue, 2),
            'order_count' => (int) $stats->order_count,
            'avg_price' => round((float) $stats->avg_price, 2),
            'sales_by_day' => $salesByDay->map(function ($item) {
                return [
                    'date' => $item->date,
                    'quantity' => (int) $item->quantity,
                ];
            })->toArray(),
        ];
    }

    /**
     * Получить диапазон дат по периоду.
     */
    protected function getDateRange(string $period): array
    {
        $to = now();
        $from = match ($period) {
            'today' => now()->startOfDay(),
            '7days' => now()->subDays(7),
            '30days' => now()->subDays(30),
            '90days' => now()->subDays(90),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->subDays(30),
        };

        return ['from' => $from, 'to' => $to];
    }

    /**
     * Получить диапазон дат предыдущего периода для сравнения.
     */
    protected function getPreviousDateRange(string $period): array
    {
        $currentRange = $this->getDateRange($period);
        $diff = $currentRange['to']->diffInDays($currentRange['from']);

        return [
            'from' => $currentRange['from']->copy()->subDays($diff),
            'to' => $currentRange['from']->copy(),
        ];
    }

    /**
     * Получить суммарное количество проданных товаров (исключая отменённые).
     * Использует SQL-агрегат вместо загрузки объектов в память (устраняет N+1).
     *
     * @param  \Illuminate\Support\Collection<int, int>  $accountIds
     */
    protected function getTotalSales(Collection $accountIds, array $dateRange): int
    {
        if ($accountIds->isEmpty()) {
            return 0;
        }

        // Uzum: SQL JOIN вместо ->with(items)->get()->flatMap() — устраняет N+1
        $uzumSales = DB::table('uzum_order_items')
            ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->sum('uzum_order_items.quantity');

        // WB: каждая строка — один товар
        $wbSales = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->count();

        return (int) ($uzumSales + $wbSales);
    }

    /**
     * Получить выручку и количество заказов за период одним запросом.
     * Объединяет бывшие getTotalRevenue() и getTotalOrders() для минимизации запросов.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $accountIds
     * @return array{revenue: float, orders: int}
     */
    protected function getRevenueAndOrders(Collection $accountIds, array $dateRange): array
    {
        if ($accountIds->isEmpty()) {
            return ['revenue' => 0.0, 'orders' => 0];
        }

        // Uzum: выручка и количество заказов одним запросом
        $uzumRow = DB::table('uzum_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('SUM(total_amount) as revenue, COUNT(*) as orders')
            ->first();

        // WB: выручка и количество заказов одним запросом
        $wbRow = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->selectRaw('SUM(for_pay) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'revenue' => (float) (($uzumRow->revenue ?? 0) + ($wbRow->revenue ?? 0)),
            'orders' => (int) (($uzumRow->orders ?? 0) + ($wbRow->orders ?? 0)),
        ];
    }

    /**
     * Получить статистику отменённых заказов.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $accountIds
     * @return array{count: int, amount: float}
     */
    protected function getCancelledStats(Collection $accountIds, array $dateRange): array
    {
        if ($accountIds->isEmpty()) {
            return ['count' => 0, 'amount' => 0.0];
        }

        // Uzum: отменённые заказы — count + sum одним запросом
        $uzumRow = DB::table('uzum_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
            ->first();

        // WB: отменённые / возвраты — count + sum одним запросом
        $wbRow = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where(fn ($q) => $q->where('is_cancel', true)->orWhere('is_return', true))
            ->selectRaw('COUNT(*) as cnt, SUM(for_pay) as amount')
            ->first();

        return [
            'count' => (int) (($uzumRow->cnt ?? 0) + ($wbRow->cnt ?? 0)),
            'amount' => (float) (($uzumRow->amount ?? 0) + ($wbRow->amount ?? 0)),
        ];
    }
}
