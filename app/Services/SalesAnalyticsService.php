<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesAnalyticsService
{
    /**
     * Get sales overview for a period.
     */
    public function getOverview(int $companyId, string $period = '30days'): array
    {
        $dateRange = $this->getDateRange($period);

        // Total sales
        $totalSales = $this->getTotalSales($companyId, $dateRange);

        // Total revenue
        $totalRevenue = $this->getTotalRevenue($companyId, $dateRange);

        // Total orders
        $totalOrders = $this->getTotalOrders($companyId, $dateRange);

        // Average order value
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;

        // Previous period comparison
        $prevDateRange = $this->getPreviousDateRange($period);
        $prevRevenue = $this->getTotalRevenue($companyId, $prevDateRange);
        $revenueGrowth = $prevRevenue > 0 ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        return [
            'total_sales' => $totalSales,
            'total_revenue' => round($totalRevenue, 2),
            'total_orders' => $totalOrders,
            'average_order_value' => round($avgOrderValue, 2),
            'revenue_growth_percentage' => round($revenueGrowth, 2),
            'period' => $period,
            'date_from' => $dateRange['from']->toDateString(),
            'date_to' => $dateRange['to']->toDateString(),
        ];
    }

    /**
     * Get sales by day for chart.
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
            'quantities' => $sales->pluck('quantity')->map(fn($q) => (int)$q)->toArray(),
            'revenues' => $sales->pluck('revenue')->map(fn($r) => round((float)$r, 2))->toArray(),
        ];
    }

    /**
     * Get top selling products.
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
                    'total_quantity' => (int)$item->total_quantity,
                    'total_revenue' => round((float)$item->total_revenue, 2),
                    'order_count' => (int)$item->order_count,
                    'avg_price' => (int)$item->total_quantity > 0
                        ? round((float)$item->total_revenue / (int)$item->total_quantity, 2)
                        : 0,
                ];
            });
    }

    /**
     * Get worst selling products (flop).
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
                    'total_quantity' => (int)$item->total_quantity,
                    'total_revenue' => round((float)$item->total_revenue, 2),
                    'order_count' => (int)$item->order_count,
                    'avg_price' => (int)$item->total_quantity > 0
                        ? round((float)$item->total_revenue / (int)$item->total_quantity, 2)
                        : 0,
                    'status' => 'low_sales',
                ];
            });

        return $withSales;
    }

    /**
     * Get sales by category.
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
                    'total_quantity' => (int)$item->total_quantity,
                    'total_revenue' => round((float)$item->total_revenue, 2),
                ];
            });
    }

    /**
     * Get sales by marketplace.
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
                    'total_quantity' => (int)$item->total_quantity,
                    'total_revenue' => round((float)$item->total_revenue, 2),
                    'order_count' => (int)$item->order_count,
                ];
            });
    }

    /**
     * Get product performance metrics.
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

        if (!$stats) {
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
            'total_quantity' => (int)$stats->total_quantity,
            'total_revenue' => round((float)$stats->total_revenue, 2),
            'order_count' => (int)$stats->order_count,
            'avg_price' => round((float)$stats->avg_price, 2),
            'sales_by_day' => $salesByDay->map(function ($item) {
                return [
                    'date' => $item->date,
                    'quantity' => (int)$item->quantity,
                ];
            })->toArray(),
        ];
    }

    /**
     * Get date range based on period.
     */
    protected function getDateRange(string $period): array
    {
        $to = now();
        $from = match($period) {
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
     * Get previous period date range for comparison.
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
     * Get total sales count.
     */
    protected function getTotalSales(int $companyId, array $dateRange): int
    {
        return DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->sum('marketplace_order_items.quantity');
    }

    /**
     * Get total revenue.
     */
    protected function getTotalRevenue(int $companyId, array $dateRange): float
    {
        return DB::table('marketplace_order_items')
            ->join('marketplace_orders', 'marketplace_order_items.order_id', '=', 'marketplace_orders.id')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->sum(DB::raw('marketplace_order_items.price * marketplace_order_items.quantity'));
    }

    /**
     * Get total orders count.
     */
    protected function getTotalOrders(int $companyId, array $dateRange): int
    {
        return DB::table('marketplace_orders')
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->where('marketplace_accounts.company_id', $companyId)
            ->whereBetween('marketplace_orders.created_at', [$dateRange['from'], $dateRange['to']])
            ->count();
    }
}
