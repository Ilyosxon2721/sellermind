<?php
// file: app/Http/Controllers/Api/MarketplaceDashboardController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\UzumOrder;
use App\Models\WbOrder;
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
     * Статусы отменённых заказов (исключаются из расчёта выручки)
     */
    private const CANCELLED_STATUSES = ['cancelled', 'canceled', 'CANCELED', 'PENDING_CANCELLATION'];
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
     * Get orders statistics (excluding cancelled from main counts)
     */
    protected function getOrdersStats($accountIds): array
    {
        $today = now()->startOfDay();
        $weekAgo = now()->subDays(7)->startOfDay();
        $monthAgo = now()->subDays(30)->startOfDay();

        // Uzum orders
        $uzumTodayAll = UzumOrder::whereIn('marketplace_account_id', $accountIds)->where('ordered_at', '>=', $today);
        $uzumWeekAll = UzumOrder::whereIn('marketplace_account_id', $accountIds)->where('ordered_at', '>=', $weekAgo);
        $uzumMonthAll = UzumOrder::whereIn('marketplace_account_id', $accountIds)->where('ordered_at', '>=', $monthAgo);

        // WB orders
        $wbTodayAll = WbOrder::whereIn('marketplace_account_id', $accountIds)->where('ordered_at', '>=', $today);
        $wbWeekAll = WbOrder::whereIn('marketplace_account_id', $accountIds)->where('ordered_at', '>=', $weekAgo);
        $wbMonthAll = WbOrder::whereIn('marketplace_account_id', $accountIds)->where('ordered_at', '>=', $monthAgo);

        // Not cancelled counts
        $todayCount = (clone $uzumTodayAll)->whereNotIn('status_normalized', self::CANCELLED_STATUSES)->count()
            + (clone $wbTodayAll)->whereNotIn('status', self::CANCELLED_STATUSES)->count();

        $weekCount = (clone $uzumWeekAll)->whereNotIn('status_normalized', self::CANCELLED_STATUSES)->count()
            + (clone $wbWeekAll)->whereNotIn('status', self::CANCELLED_STATUSES)->count();

        $monthCount = (clone $uzumMonthAll)->whereNotIn('status_normalized', self::CANCELLED_STATUSES)->count()
            + (clone $wbMonthAll)->whereNotIn('status', self::CANCELLED_STATUSES)->count();

        // Cancelled counts
        $cancelledToday = (clone $uzumTodayAll)->whereIn('status_normalized', self::CANCELLED_STATUSES)->count()
            + (clone $wbTodayAll)->whereIn('status', self::CANCELLED_STATUSES)->count();

        $cancelledWeek = (clone $uzumWeekAll)->whereIn('status_normalized', self::CANCELLED_STATUSES)->count()
            + (clone $wbWeekAll)->whereIn('status', self::CANCELLED_STATUSES)->count();

        $cancelledMonth = (clone $uzumMonthAll)->whereIn('status_normalized', self::CANCELLED_STATUSES)->count()
            + (clone $wbMonthAll)->whereIn('status', self::CANCELLED_STATUSES)->count();

        // By status (for month)
        $uzumByStatus = UzumOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->select('status_normalized', DB::raw('count(*) as count'))
            ->groupBy('status_normalized')
            ->pluck('count', 'status_normalized')
            ->toArray();

        $wbByStatus = WbOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Merge status counts
        $byStatus = [];
        foreach ($uzumByStatus as $status => $count) {
            $byStatus[$status] = ($byStatus[$status] ?? 0) + $count;
        }
        foreach ($wbByStatus as $status => $count) {
            $byStatus[$status] = ($byStatus[$status] ?? 0) + $count;
        }

        return [
            'today' => $todayCount,
            'week' => $weekCount,
            'month' => $monthCount,
            'cancelled_today' => $cancelledToday,
            'cancelled_week' => $cancelledWeek,
            'cancelled_month' => $cancelledMonth,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Get revenue statistics (excluding cancelled orders)
     */
    protected function getRevenueStats($accountIds): array
    {
        $monthAgo = now()->subDays(30)->startOfDay();

        // Current month revenue (excluding cancelled)
        $uzumMonthRevenue = UzumOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->sum('total_amount');

        $wbMonthRevenue = WbOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->sum('total_amount');

        $monthRevenue = (float) ($uzumMonthRevenue + $wbMonthRevenue);

        // Cancelled amount for current month
        $uzumCancelledMonth = UzumOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->whereIn('status_normalized', self::CANCELLED_STATUSES)
            ->sum('total_amount');

        $wbCancelledMonth = WbOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->whereIn('status', self::CANCELLED_STATUSES)
            ->sum('total_amount');

        $cancelledMonthAmount = (float) ($uzumCancelledMonth + $wbCancelledMonth);

        // Previous month revenue (excluding cancelled)
        $prevMonthStart = now()->subDays(60)->startOfDay();
        $prevMonthEnd = now()->subDays(30)->endOfDay();

        $uzumPrevRevenue = UzumOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$prevMonthStart, $prevMonthEnd])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->sum('total_amount');

        $wbPrevRevenue = WbOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$prevMonthStart, $prevMonthEnd])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->sum('total_amount');

        $prevMonthRevenue = (float) ($uzumPrevRevenue + $wbPrevRevenue);

        $growth = $prevMonthRevenue > 0
            ? round((($monthRevenue - $prevMonthRevenue) / $prevMonthRevenue) * 100, 2)
            : 0;

        return [
            'month' => $monthRevenue,
            'prev_month' => $prevMonthRevenue,
            'growth_percent' => $growth,
            'cancelled_month' => $cancelledMonthAmount,
        ];
    }

    /**
     * Get returns statistics (based on non-cancelled orders)
     */
    protected function getReturnsStats($accountIds): array
    {
        $monthAgo = now()->subDays(30)->startOfDay();

        // Total orders count (excluding cancelled)
        $uzumOrdersCount = UzumOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->count();

        $wbOrdersCount = WbOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('ordered_at', '>=', $monthAgo)
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->count();

        $ordersCount = $uzumOrdersCount + $wbOrdersCount;

        $returnsCount = MarketplaceReturn::whereIn('marketplace_account_id', $accountIds)
            ->where('created_at', '>=', $monthAgo)
            ->count();

        $returnRate = $ordersCount > 0 ? round(($returnsCount / $ordersCount) * 100, 2) : 0;

        $topReasons = MarketplaceReturn::whereIn('marketplace_account_id', $accountIds)
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
