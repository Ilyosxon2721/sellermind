<?php
// file: app/Http/Controllers/Api/MarketplaceDashboardController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\MarketplacePayout;
use App\Models\MarketplaceReturn;
use App\Services\Marketplaces\MarketplaceDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MarketplaceDashboardController extends Controller
{
    /**
     * Get dashboard overview for company marketplaces
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $accounts = MarketplaceAccount::where('company_id', $request->company_id)
            ->withCount(['products', 'orders'])
            ->get();

        $accountIds = $accounts->pluck('id');

        // Get aggregated metrics
        $ordersStats = $this->getOrdersStats($accountIds);
        $revenueStats = $this->getRevenueStats($accountIds);
        $returnsStats = $this->getReturnsStats($accountIds);
        $productsStats = $this->getProductsStats($accountIds);

        // Recent payouts
        $recentPayouts = MarketplacePayout::whereIn('marketplace_account_id', $accountIds)
            ->orderByDesc('period_to')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'period' => $p->getPeriodLabel(),
                'amount' => $p->getFormattedAmount(),
                'net_profit' => $p->getNetProfit(),
            ]);

        return response()->json([
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'marketplace' => $a->marketplace,
                'name' => $a->getDisplayName(),
                'is_active' => $a->is_active,
                'products_count' => $a->products_count,
                'orders_count' => $a->orders_count,
            ]),
            'orders' => $ordersStats,
            'revenue' => $revenueStats,
            'returns' => $returnsStats,
            'products' => $productsStats,
            'recent_payouts' => $recentPayouts,
        ]);
    }

    /**
     * Get orders statistics
     */
    protected function getOrdersStats($accountIds): array
    {
        $today = now()->startOfDay();
        $weekAgo = now()->subDays(7)->startOfDay();
        $monthAgo = now()->subDays(30)->startOfDay();

        return [
            'today' => MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
                ->where('created_at', '>=', $today)
                ->count(),
            'week' => MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
                ->where('created_at', '>=', $weekAgo)
                ->count(),
            'month' => MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
                ->where('created_at', '>=', $monthAgo)
                ->count(),
            'by_status' => MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
                ->where('created_at', '>=', $monthAgo)
                ->select('internal_status', DB::raw('count(*) as count'))
                ->groupBy('internal_status')
                ->pluck('count', 'internal_status')
                ->toArray(),
        ];
    }

    /**
     * Get revenue statistics
     */
    protected function getRevenueStats($accountIds): array
    {
        $monthAgo = now()->subDays(30)->startOfDay();

        $monthRevenue = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('created_at', '>=', $monthAgo)
            ->where('internal_status', MarketplaceOrder::INTERNAL_STATUS_DELIVERED)
            ->sum('total_amount');

        $prevMonthStart = now()->subDays(60)->startOfDay();
        $prevMonthEnd = now()->subDays(30)->endOfDay();

        $prevMonthRevenue = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at', [$prevMonthStart, $prevMonthEnd])
            ->where('internal_status', MarketplaceOrder::INTERNAL_STATUS_DELIVERED)
            ->sum('total_amount');

        $growth = $prevMonthRevenue > 0
            ? round((($monthRevenue - $prevMonthRevenue) / $prevMonthRevenue) * 100, 2)
            : 0;

        return [
            'month' => $monthRevenue,
            'prev_month' => $prevMonthRevenue,
            'growth_percent' => $growth,
        ];
    }

    /**
     * Get returns statistics
     */
    protected function getReturnsStats($accountIds): array
    {
        $monthAgo = now()->subDays(30)->startOfDay();

        $ordersCount = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('created_at', '>=', $monthAgo)
            ->count();

        $returnsCount = MarketplaceReturn::whereHas('order', function ($q) use ($accountIds) {
            $q->whereIn('marketplace_account_id', $accountIds);
        })
            ->where('created_at', '>=', $monthAgo)
            ->count();

        $returnRate = $ordersCount > 0 ? round(($returnsCount / $ordersCount) * 100, 2) : 0;

        $topReasons = MarketplaceReturn::whereHas('order', function ($q) use ($accountIds) {
            $q->whereIn('marketplace_account_id', $accountIds);
        })
            ->where('created_at', '>=', $monthAgo)
            ->select('reason_code', DB::raw('count(*) as count'))
            ->groupBy('reason_code')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'reason_code')
            ->toArray();

        return [
            'count' => $returnsCount,
            'rate_percent' => $returnRate,
            'top_reasons' => $topReasons,
        ];
    }

    /**
     * Get products statistics
     */
    protected function getProductsStats($accountIds): array
    {
        $statuses = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total' => array_sum($statuses),
            'active' => $statuses[MarketplaceProduct::STATUS_ACTIVE] ?? 0,
            'pending' => $statuses[MarketplaceProduct::STATUS_PENDING] ?? 0,
            'error' => $statuses[MarketplaceProduct::STATUS_ERROR] ?? 0,
            'archived' => $statuses[MarketplaceProduct::STATUS_ARCHIVED] ?? 0,
        ];
    }

    /**
     * Get full dashboard stats with filters (for new dashboard page)
     *
     * Supports filters:
     *  - period: today / 7d / 30d / custom
     *  - date_from, date_to: for custom period
     *  - marketplace: all / wb / ozon / uzum / ym
     */
    public function stats(Request $request, MarketplaceDashboardService $dashboardService): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'period' => ['sometimes', 'in:today,7d,30d,custom'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'marketplace' => ['sometimes', 'string'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $filters = [
            'period' => $request->input('period', '7d'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'marketplace' => $request->input('marketplace', 'all'),
        ];

        // Аккаунты пользователя по компании
        $accountsQuery = MarketplaceAccount::query()
            ->where('company_id', $request->company_id);

        if ($filters['marketplace'] !== 'all') {
            $accountsQuery->where('marketplace', $filters['marketplace']);
        }

        $accounts = $accountsQuery->get();

        // Сервис собирает все агрегаты
        $data = $dashboardService->buildDashboardData($accounts, $filters);

        return response()->json([
            'filters' => $filters,
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'marketplace' => $a->marketplace,
                'marketplace_label' => $a->getDisplayName(),
                'name' => $a->name,
                'is_active' => $a->is_active,
            ]),
            'period' => [
                'from' => $data['period']['from']->toIso8601String(),
                'to' => $data['period']['to']->toIso8601String(),
            ],
            'overall_kpi' => $data['overall_kpi'],
            'by_account' => $data['by_account'],
            'problem_skus' => $data['problem_skus'],
            'recent_events' => array_map(function ($event) {
                return [
                    ...$event,
                    'started_at' => $event['started_at']?->toIso8601String(),
                    'finished_at' => $event['finished_at']?->toIso8601String(),
                    'created_at' => $event['created_at']?->toIso8601String(),
                ];
            }, $data['recent_events']),
        ]);
    }

    /**
     * Get charts data (daily time series for revenue and orders)
     *
     * Supports same filters as stats:
     *  - period: today / 7d / 30d / custom
     *  - date_from, date_to: for custom period
     *  - marketplace: all / wb / ozon / uzum / ym
     */
    public function chartsData(Request $request, MarketplaceDashboardService $dashboardService): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'period' => ['sometimes', 'in:today,7d,30d,custom'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date'],
            'marketplace' => ['sometimes', 'string'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $filters = [
            'period' => $request->input('period', '7d'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'marketplace' => $request->input('marketplace', 'all'),
        ];

        // Get accounts
        $accountsQuery = MarketplaceAccount::query()
            ->where('company_id', $request->company_id);

        if ($filters['marketplace'] !== 'all') {
            $accountsQuery->where('marketplace', $filters['marketplace']);
        }

        $accounts = $accountsQuery->get();

        // Use buildDashboardData to get the resolved period
        $dashboardData = $dashboardService->buildDashboardData($accounts, $filters);

        $from = $dashboardData['period']['from'];
        $to = $dashboardData['period']['to'];

        // Get daily series
        $series = $dashboardService->getDailySeries($accounts, $from, $to);

        return response()->json([
            'success' => true,
            'data' => $series,
        ]);
    }
}
