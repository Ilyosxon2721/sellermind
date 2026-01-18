<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AgentTask;
use App\Models\AgentTaskRun;
use App\Models\Company;
use App\Models\Inventory;
use App\Models\MarketplaceAccount;
use App\Models\Product;
use App\Models\Review;
use App\Models\Subscription;
use App\Models\UzumFinanceOrder;
use App\Models\UzumOrder;
use App\Models\WildberriesOrder;
use App\Models\WildberriesSupply;
use App\Models\Warehouse\ChannelOrder;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use App\Services\CurrencyConversionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    protected CurrencyConversionService $currencyService;

    public function __construct(CurrencyConversionService $currencyService)
    {
        $this->currencyService = $currencyService;
    }

    /**
     * Общий dashboard с информацией по всем модулям
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $companyId = $request->input('company_id');

            if (!$companyId) {
                return response()->json(['error' => 'company_id is required'], 400);
            }

            if (!$request->user()->hasCompanyAccess($companyId)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }

            // Setup currency service for company
            $company = Company::find($companyId);
            if ($company) {
                $this->currencyService->forCompany($company);
            }

            $today = Carbon::today();
            $weekAgo = Carbon::today()->subDays(7);
            $monthAgo = Carbon::today()->subDays(30);

            // === ПРОДАЖИ ===
            $salesData = $this->getSalesData($companyId, $today, $weekAgo, $monthAgo);

            // === МАРКЕТПЛЕЙСЫ ===
            $marketplaceData = $this->getMarketplaceData($companyId);

            // === СКЛАД ===
            $warehouseData = $this->getWarehouseData($companyId);

            // === ТОВАРЫ ===
            $productsData = $this->getProductsData($companyId);

            // Get currency info
            $displayCurrency = $this->currencyService->getDisplayCurrency();
            $currencySymbol = $this->currencyService->getCurrencySymbol();

            return response()->json([
                'success' => true,
                'currency' => [
                    'code' => $displayCurrency,
                    'symbol' => $currencySymbol,
                ],
                'summary' => [
                    'sales_today' => $salesData['today_amount'],
                    'sales_today_count' => $salesData['today_count'],
                    'sales_week' => $salesData['week_amount'],
                    'sales_week_count' => $salesData['week_count'],
                    'products_total' => $productsData['total'],
                    'products_active' => $productsData['active'],
                    'warehouse_value' => $warehouseData['total_value'],
                    'warehouse_items' => $warehouseData['total_items'],
                    'marketplaces_count' => $marketplaceData['accounts_count'],
                ],
                'sales' => $salesData,
                'marketplace' => $marketplaceData,
                'warehouse' => $warehouseData,
                'products' => $productsData,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Dashboard error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Статусы отменённых заказов (исключаются из расчёта выручки)
     */
    private const CANCELLED_STATUSES = ['cancelled', 'canceled', 'CANCELED', 'PENDING_CANCELLATION'];

    /**
     * Данные по продажам (Uzum + WB)
     *
     * Статусы заказов:
     * - COMPLETED (Uzum) / is_realization=true (WB) → Продажи (полноценный доход)
     * - PROCESSING (Uzum) / processing statuses (WB) → В транзите (ещё не доход)
     * - CANCELED (Uzum) / is_cancel/is_return (WB) → Отменённые (не считаем)
     *
     * Uzum использует UzumFinanceOrder (Finance API) для всех типов заказов (FBO/FBS/DBS/EDBS)
     * WB суммы конвертируются из RUB в валюту отображения
     */
    private function getSalesData(int $companyId, Carbon $today, Carbon $weekAgo, Carbon $monthAgo): array
    {
        // ===== UZUM: COMPLETED и TO_WITHDRAW считаем как продажи (доход) =====
        // TO_WITHDRAW = деньги выведены (завершённая продажа)
        $uzumCompleted = UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['COMPLETED', 'TO_WITHDRAW']);

        // Uzum: PROCESSING = в транзите (ещё не доход)
        $uzumTransit = UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'PROCESSING');

        // Uzum: CANCELED = отменённые
        $uzumCancelled = UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['CANCELED', 'cancelled']);

        // ===== WB: is_realization=true считаем как продажи (доход) =====
        $wbCompleted = WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->where('is_realization', true)
            ->where('is_cancel', false)
            ->where('is_return', false);

        // WB: В транзите - не реализовано, но и не отменено
        $wbTransit = WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->where('is_realization', false)
            ->where('is_cancel', false)
            ->where('is_return', false);

        // WB: Отменённые
        $wbCancelled = WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->where(fn($q) => $q->where('is_cancel', true)->orWhere('is_return', true));

        // ========== ПРОДАЖИ (ДОХОД) - только завершённые ==========

        // Today - ПРОДАЖИ
        $uzumTodaySalesTiyin = (float) (clone $uzumCompleted)->whereDate('order_date', $today)
            ->selectRaw('SUM(sell_price * amount) as total')->value('total');
        $uzumTodaySales = $uzumTodaySalesTiyin / 100;
        $wbTodaySalesRub = (float) (clone $wbCompleted)->whereDate('order_date', $today)->sum('for_pay');
        $wbTodaySales = $this->currencyService->convertFromRub($wbTodaySalesRub);
        $todaySalesAmount = $uzumTodaySales + $wbTodaySales;
        $todaySalesCount = (int) ((clone $uzumCompleted)->whereDate('order_date', $today)->sum('amount')
            + (clone $wbCompleted)->whereDate('order_date', $today)->count());

        // Week - ПРОДАЖИ
        $uzumWeekSalesTiyin = (float) (clone $uzumCompleted)->whereDate('order_date', '>=', $weekAgo)
            ->selectRaw('SUM(sell_price * amount) as total')->value('total');
        $uzumWeekSales = $uzumWeekSalesTiyin / 100;
        $wbWeekSalesRub = (float) (clone $wbCompleted)->whereDate('order_date', '>=', $weekAgo)->sum('for_pay');
        $wbWeekSales = $this->currencyService->convertFromRub($wbWeekSalesRub);
        $weekSalesAmount = $uzumWeekSales + $wbWeekSales;
        $weekSalesCount = (int) ((clone $uzumCompleted)->whereDate('order_date', '>=', $weekAgo)->sum('amount')
            + (clone $wbCompleted)->whereDate('order_date', '>=', $weekAgo)->count());

        // Month - ПРОДАЖИ
        $uzumMonthSalesTiyin = (float) (clone $uzumCompleted)->whereDate('order_date', '>=', $monthAgo)
            ->selectRaw('SUM(sell_price * amount) as total')->value('total');
        $uzumMonthSales = $uzumMonthSalesTiyin / 100;
        $wbMonthSalesRub = (float) (clone $wbCompleted)->whereDate('order_date', '>=', $monthAgo)->sum('for_pay');
        $wbMonthSales = $this->currencyService->convertFromRub($wbMonthSalesRub);
        $monthSalesAmount = $uzumMonthSales + $wbMonthSales;
        $monthSalesCount = (int) ((clone $uzumCompleted)->whereDate('order_date', '>=', $monthAgo)->sum('amount')
            + (clone $wbCompleted)->whereDate('order_date', '>=', $monthAgo)->count());

        // ========== В ТРАНЗИТЕ (ещё не доход) ==========

        // Week - В ТРАНЗИТЕ
        $uzumWeekTransitTiyin = (float) (clone $uzumTransit)->whereDate('order_date', '>=', $weekAgo)
            ->selectRaw('SUM(sell_price * amount) as total')->value('total');
        $uzumWeekTransit = $uzumWeekTransitTiyin / 100;
        $wbWeekTransitRub = (float) (clone $wbTransit)->whereDate('order_date', '>=', $weekAgo)->sum('for_pay');
        $wbWeekTransit = $this->currencyService->convertFromRub($wbWeekTransitRub);
        $weekTransitAmount = $uzumWeekTransit + $wbWeekTransit;
        $weekTransitCount = (int) ((clone $uzumTransit)->whereDate('order_date', '>=', $weekAgo)->sum('amount')
            + (clone $wbTransit)->whereDate('order_date', '>=', $weekAgo)->count());

        // Month - В ТРАНЗИТЕ
        $uzumMonthTransitTiyin = (float) (clone $uzumTransit)->whereDate('order_date', '>=', $monthAgo)
            ->selectRaw('SUM(sell_price * amount) as total')->value('total');
        $uzumMonthTransit = $uzumMonthTransitTiyin / 100;
        $wbMonthTransitRub = (float) (clone $wbTransit)->whereDate('order_date', '>=', $monthAgo)->sum('for_pay');
        $wbMonthTransit = $this->currencyService->convertFromRub($wbMonthTransitRub);
        $monthTransitAmount = $uzumMonthTransit + $wbMonthTransit;
        $monthTransitCount = (int) ((clone $uzumTransit)->whereDate('order_date', '>=', $monthAgo)->sum('amount')
            + (clone $wbTransit)->whereDate('order_date', '>=', $monthAgo)->count());

        // ========== ОТМЕНЁННЫЕ ==========

        // Week - ОТМЕНЁННЫЕ
        $uzumWeekCancelledTiyin = (float) (clone $uzumCancelled)->whereDate('order_date', '>=', $weekAgo)
            ->selectRaw('SUM(sell_price * amount) as total')->value('total');
        $uzumWeekCancelled = $uzumWeekCancelledTiyin / 100;
        $wbWeekCancelledRub = (float) (clone $wbCancelled)->whereDate('order_date', '>=', $weekAgo)->sum('for_pay');
        $wbWeekCancelled = $this->currencyService->convertFromRub($wbWeekCancelledRub);
        $weekCancelledAmount = $uzumWeekCancelled + $wbWeekCancelled;
        $weekCancelledCount = (int) ((clone $uzumCancelled)->whereDate('order_date', '>=', $weekAgo)->sum('amount')
            + (clone $wbCancelled)->whereDate('order_date', '>=', $weekAgo)->count());

        // ========== ВСЕГО ЗАКАЗОВ (для общей статистики) ==========
        $todayTotalCount = $todaySalesCount
            + (int) ((clone $uzumTransit)->whereDate('order_date', $today)->sum('amount')
            + (clone $wbTransit)->whereDate('order_date', $today)->count());

        $weekTotalCount = $weekSalesCount + $weekTransitCount;
        $monthTotalCount = $monthSalesCount + $monthTransitCount;

        // ========== ГРАФИК ПО ДНЯМ - только ПРОДАЖИ (завершённые) ==========
        $uzumDailySales = UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereDate('order_date', '>=', $weekAgo)
            ->whereIn('status', ['COMPLETED', 'TO_WITHDRAW'])
            ->select(DB::raw('DATE(order_date) as date'), DB::raw('SUM(sell_price * amount) as amount_tiyin'), DB::raw('SUM(amount) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $wbDailySales = WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereDate('order_date', '>=', $weekAgo)
            ->where('is_realization', true)
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->select(DB::raw('DATE(order_date) as date'), DB::raw('SUM(for_pay) as amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::parse($date)->format('d.m');
            // Только ПРОДАЖИ (завершённые заказы)
            $uzumAmount = (float) ($uzumDailySales[$date]->amount_tiyin ?? 0) / 100;
            $wbAmountRub = (float) ($wbDailySales[$date]->amount ?? 0);
            $wbAmount = $this->currencyService->convertFromRub($wbAmountRub);
            $chartData[] = $uzumAmount + $wbAmount;
        }

        // ========== ПОСЛЕДНИЕ ЗАКАЗЫ ==========
        $uzumRecent = UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->with('account')
            ->orderByDesc('order_date')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id' => 'uzum_' . $o->id,
                'order_number' => (string) $o->order_id,
                'amount' => ($o->sell_price * $o->amount) / 100,
                'original_currency' => 'UZS',
                'status' => $this->normalizeUzumFinanceStatus($o->status),
                'status_label' => $this->getStatusLabel($this->normalizeUzumFinanceStatus($o->status)),
                'is_revenue' => in_array($o->status, ['COMPLETED', 'TO_WITHDRAW']), // Это доход только если завершён
                'date' => $o->order_date?->format('d.m.Y H:i'),
                'ordered_at' => $o->order_date,
                'marketplace' => 'uzum',
                'account_name' => $o->account?->name ?? $o->account?->getDisplayName() ?? 'Uzum',
                'product_title' => $o->sku_title,
            ]);

        $wbRecent = WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->with('account')
            ->orderByDesc('order_date')
            ->limit(10)
            ->get()
            ->map(fn($o) => [
                'id' => 'wb_' . $o->id,
                'order_number' => $o->srid ?? $o->order_id,
                'amount' => $this->currencyService->convertFromRub((float) ($o->for_pay ?? $o->finished_price ?? $o->total_price ?? 0)),
                'amount_rub' => (float) ($o->for_pay ?? $o->finished_price ?? $o->total_price ?? 0),
                'original_currency' => 'RUB',
                'status' => $this->normalizeWbStatus($o),
                'status_label' => $this->getStatusLabel($this->normalizeWbStatus($o)),
                'is_revenue' => $o->is_realization && !$o->is_cancel && !$o->is_return, // Доход только если реализовано
                'date' => $o->order_date?->format('d.m.Y H:i'),
                'ordered_at' => $o->order_date,
                'marketplace' => 'wb',
                'account_name' => $o->account?->name ?? $o->account?->getDisplayName() ?? 'Wildberries',
            ]);

        $recentOrders = $uzumRecent->concat($wbRecent)
            ->sortByDesc('ordered_at')
            ->take(5)
            ->values()
            ->map(fn($o) => collect($o)->except('ordered_at')->toArray());

        // ========== ПО СТАТУСАМ ==========
        $uzumByStatusRaw = UzumFinanceOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereDate('order_date', '>=', $weekAgo)
            ->select('status', DB::raw('SUM(amount) as count'), DB::raw('SUM(sell_price * amount) as amount_tiyin'))
            ->groupBy('status')
            ->get();

        $byStatus = [
            'completed' => ['count' => 0, 'amount' => 0.0],
            'transit' => ['count' => 0, 'amount' => 0.0],
            'cancelled' => ['count' => 0, 'amount' => 0.0],
        ];

        foreach ($uzumByStatusRaw as $row) {
            $amount = ($row->amount_tiyin ?? 0) / 100;
            if (in_array($row->status, ['COMPLETED', 'TO_WITHDRAW'])) {
                $byStatus['completed']['count'] += (int) $row->count;
                $byStatus['completed']['amount'] += $amount;
            } elseif ($row->status === 'PROCESSING') {
                $byStatus['transit']['count'] += (int) $row->count;
                $byStatus['transit']['amount'] += $amount;
            } else {
                // CANCELED и другие статусы
                $byStatus['cancelled']['count'] += (int) $row->count;
                $byStatus['cancelled']['amount'] += $amount;
            }
        }

        // WB orders by status
        $wbByStatusRaw = WildberriesOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereDate('order_date', '>=', $weekAgo)
            ->get();

        foreach ($wbByStatusRaw as $order) {
            $amount = $this->currencyService->convertFromRub((float) ($order->for_pay ?? 0));
            if ($order->is_cancel || $order->is_return) {
                $byStatus['cancelled']['count']++;
                $byStatus['cancelled']['amount'] += $amount;
            } elseif ($order->is_realization) {
                $byStatus['completed']['count']++;
                $byStatus['completed']['amount'] += $amount;
            } else {
                $byStatus['transit']['count']++;
                $byStatus['transit']['amount'] += $amount;
            }
        }

        return [
            // Продажи (ДОХОД) - только завершённые заказы
            'today_amount' => (float) $todaySalesAmount,
            'today_count' => $todaySalesCount,
            'week_amount' => (float) $weekSalesAmount,
            'week_count' => $weekSalesCount,
            'month_amount' => (float) $monthSalesAmount,
            'month_count' => $monthSalesCount,

            // В транзите (ещё не доход)
            'transit_week_amount' => (float) $weekTransitAmount,
            'transit_week_count' => $weekTransitCount,
            'transit_month_amount' => (float) $monthTransitAmount,
            'transit_month_count' => $monthTransitCount,

            // Отменённые
            'cancelled_week_amount' => (float) $weekCancelledAmount,
            'cancelled_week_count' => $weekCancelledCount,

            // Всего заказов (для информации)
            'total_today_count' => $todayTotalCount,
            'total_week_count' => $weekTotalCount,
            'total_month_count' => $monthTotalCount,

            'chart_labels' => $chartLabels,
            'chart_data' => $chartData,
            'recent_orders' => $recentOrders,
            'by_status' => $byStatus,

            // Детали по маркетплейсам
            'by_marketplace' => [
                'uzum' => [
                    'week_sales' => $uzumWeekSales,
                    'week_transit' => $uzumWeekTransit,
                    'currency' => 'UZS',
                ],
                'wb' => [
                    'week_sales_rub' => $wbWeekSalesRub,
                    'week_sales_converted' => $wbWeekSales,
                    'week_transit_rub' => $wbWeekTransitRub,
                    'week_transit_converted' => $wbWeekTransit,
                    'currency' => 'RUB',
                    'converted_to' => $this->currencyService->getDisplayCurrency(),
                ],
            ],
        ];
    }

    /**
     * Normalize Uzum Finance Order status
     * TO_WITHDRAW = деньги выведены (завершённая продажа)
     */
    private function normalizeUzumFinanceStatus(?string $status): string
    {
        if (!$status) {
            return 'processing';
        }

        return match (strtoupper($status)) {
            'PROCESSING' => 'processing',
            'COMPLETED', 'TO_WITHDRAW' => 'delivered',
            'CANCELED' => 'cancelled',
            default => strtolower($status),
        };
    }

    /**
     * Данные маркетплейсов
     */
    private function getMarketplaceData(int $companyId): array
    {
        $accounts = MarketplaceAccount::where('company_id', $companyId)->get();
        
        $byMarketplace = $accounts->groupBy('marketplace')->map(fn($group) => [
            'count' => $group->count(),
            'active' => $group->where('is_active', true)->count(),
        ]);

        return [
            'accounts_count' => $accounts->count(),
            'active_count' => $accounts->where('is_active', true)->count(),
            'by_marketplace' => $byMarketplace,
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name ?? $a->getDisplayName(),
                'marketplace' => $a->marketplace,
                'is_active' => $a->is_active,
            ]),
        ];
    }

    /**
     * Данные склада
     */
    private function getWarehouseData(int $companyId): array
    {
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        $warehouseIds = $warehouses->pluck('id');

        // Получаем агрегированные данные по остаткам из stock_ledger
        $stockData = StockLedger::whereIn('warehouse_id', $warehouseIds)
            ->select('sku_id', DB::raw('SUM(qty_delta) as quantity'), DB::raw('SUM(cost_delta) as total_cost'))
            ->groupBy('sku_id')
            ->having('quantity', '>', 0)
            ->get();

        $totalItems = $stockData->sum('quantity');
        $totalValue = $stockData->sum('total_cost');

        // Критически низкие остатки (меньше 10 единиц)
        $lowStock = $stockData->where('quantity', '>', 0)->where('quantity', '<', 10)->count();

        // Последние инвентаризации
        $recentDocuments = Inventory::whereIn('warehouse_id', $warehouseIds)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'number' => $d->number,
                'type' => $d->type,
                'date' => $d->date?->format('d.m.Y'),
            ]);

        return [
            'warehouses_count' => $warehouses->count(),
            'total_items' => (float) $totalItems,
            'total_value' => (float) $totalValue,
            'low_stock_count' => $lowStock,
            'recent_documents' => $recentDocuments,
        ];
    }

    /**
     * Данные товаров
     */
    private function getProductsData(int $companyId): array
    {
        $products = Product::where('company_id', $companyId);

        $total = (clone $products)->count();
        $active = (clone $products)->where('is_active', true)->count();
        $archived = (clone $products)->where('is_active', false)->count();

        // Топ товары (если есть связь с продажами)
        $topProducts = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
            ]);

        return [
            'total' => $total,
            'active' => $active,
            'archived' => $archived,
            'top_products' => $topProducts,
        ];
    }

    /**
     * Полная информация для комплексного дашборда
     */
    public function full(Request $request): JsonResponse
    {
        try {
            $companyId = $request->input('company_id');

            if (!$companyId) {
                return response()->json(['error' => 'company_id is required'], 400);
            }

            if (!$request->user()->hasCompanyAccess($companyId)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }

            // Setup currency service for company
            $company = Company::find($companyId);
            if ($company) {
                $this->currencyService->forCompany($company);
            }

            $today = Carbon::today();
            $weekAgo = Carbon::today()->subDays(7);
            $monthAgo = Carbon::today()->subDays(30);

            // Базовые данные
            $salesData = $this->getSalesData($companyId, $today, $weekAgo, $monthAgo);
            $marketplaceData = $this->getMarketplaceData($companyId);
            $warehouseData = $this->getWarehouseData($companyId);
            $productsData = $this->getProductsData($companyId);

            // Дополнительные данные
            $alertsData = $this->getAlertsData($companyId);
            $aiData = $this->getAIData($companyId);
            $subscriptionData = $this->getSubscriptionData($companyId);
            $teamData = $this->getTeamData($companyId);
            $suppliesData = $this->getSuppliesData($companyId);
            $reviewsData = $this->getReviewsData($companyId);

            // Get currency info
            $displayCurrency = $this->currencyService->getDisplayCurrency();
            $currencySymbol = $this->currencyService->getCurrencySymbol();

            return response()->json([
                'success' => true,
                'currency' => [
                    'code' => $displayCurrency,
                    'symbol' => $currencySymbol,
                ],
                'summary' => [
                    'sales_today' => $salesData['today_amount'],
                    'sales_today_count' => $salesData['today_count'],
                    'sales_week' => $salesData['week_amount'],
                    'sales_week_count' => $salesData['week_count'],
                    'sales_month' => $salesData['month_amount'],
                    'sales_month_count' => $salesData['month_count'],
                    'products_total' => $productsData['total'],
                    'products_active' => $productsData['active'],
                    'warehouse_value' => $warehouseData['total_value'],
                    'warehouse_items' => $warehouseData['total_items'],
                    'marketplaces_count' => $marketplaceData['accounts_count'],
                    'alerts_count' => $alertsData['total_count'],
                ],
                'sales' => $salesData,
                'marketplace' => $marketplaceData,
                'warehouse' => $warehouseData,
                'products' => $productsData,
                'alerts' => $alertsData,
                'ai' => $aiData,
                'subscription' => $subscriptionData,
                'team' => $teamData,
                'supplies' => $suppliesData,
                'reviews' => $reviewsData,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Full Dashboard error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Оповещения и важные события
     */
    public function alerts(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');

        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 400);
        }

        if (!$request->user()->hasCompanyAccess($companyId)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->getAlertsData($companyId),
        ]);
    }

    /**
     * Данные AI агентов
     */
    public function aiStatus(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');

        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 400);
        }

        if (!$request->user()->hasCompanyAccess($companyId)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->getAIData($companyId),
        ]);
    }

    /**
     * Данные подписки
     */
    public function subscription(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');

        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 400);
        }

        if (!$request->user()->hasCompanyAccess($companyId)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->getSubscriptionData($companyId),
        ]);
    }

    /**
     * Данные команды
     */
    public function team(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id');

        if (!$companyId) {
            return response()->json(['error' => 'company_id is required'], 400);
        }

        if (!$request->user()->hasCompanyAccess($companyId)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $this->getTeamData($companyId),
        ]);
    }

    /**
     * Собрать данные об оповещениях
     */
    private function getAlertsData(int $companyId): array
    {
        $alerts = [];

        // 1. Критический остаток товаров
        $warehouseIds = Warehouse::where('company_id', $companyId)->pluck('id');
        $lowStockItems = StockLedger::whereIn('warehouse_id', $warehouseIds)
            ->select('sku_id', DB::raw('SUM(qty_delta) as quantity'))
            ->groupBy('sku_id')
            ->having('quantity', '>', 0)
            ->having('quantity', '<', 10)
            ->limit(10)
            ->get();

        foreach ($lowStockItems as $item) {
            $alerts[] = [
                'type' => 'low_stock',
                'severity' => 'warning',
                'title' => 'Низкий остаток',
                'message' => "SKU: {$item->sku_id} - осталось {$item->quantity} шт.",
                'sku_id' => $item->sku_id,
                'quantity' => (int) $item->quantity,
                'action_url' => '/inventory',
            ];
        }

        // 2. Новые отзывы без ответа
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');
        $pendingReviews = Review::where('company_id', $companyId)
            ->whereNull('response_text')
            ->where('rating', '<=', 3)
            ->orderByDesc('review_date')
            ->limit(5)
            ->get();

        foreach ($pendingReviews as $review) {
            $alerts[] = [
                'type' => 'review',
                'severity' => $review->rating <= 2 ? 'error' : 'warning',
                'title' => "Отзыв {$review->rating}★ без ответа",
                'message' => mb_substr($review->review_text ?? 'Без текста', 0, 100) . '...',
                'review_id' => $review->id,
                'rating' => $review->rating,
                'action_url' => '/reviews',
            ];
        }

        // 3. Активные поставки в пути
        $activeSupplies = WildberriesSupply::whereIn('marketplace_account_id', $accountIds)
            ->active()
            ->orderByDesc('created_at_wb')
            ->limit(5)
            ->get();

        foreach ($activeSupplies as $supply) {
            $alerts[] = [
                'type' => 'supply',
                'severity' => 'info',
                'title' => "Поставка {$supply->name}",
                'message' => "Статус: {$supply->status}, заказов: {$supply->orders_count}",
                'supply_id' => $supply->id,
                'status' => $supply->status,
                'action_url' => '/supplies',
            ];
        }

        // 4. Заказы требующие сборки (новые/в обработке)
        // Uzum Finance Orders: PROCESSING = требует обработки
        $newOrdersCount = UzumFinanceOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('status', 'PROCESSING')
            ->sum('amount')
            + WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->where('status', 'new')
            ->count();

        if ($newOrdersCount > 0) {
            $alerts[] = [
                'type' => 'orders',
                'severity' => 'info',
                'title' => 'Заказы ожидают сборки',
                'message' => "{$newOrdersCount} новых заказов",
                'count' => $newOrdersCount,
                'action_url' => '/sales?status=new',
            ];
        }

        // 5. Истекающая подписка
        $subscription = Subscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        if ($subscription && $subscription->ends_at) {
            $daysRemaining = $subscription->daysRemaining();
            if ($daysRemaining !== null && $daysRemaining <= 7) {
                $alerts[] = [
                    'type' => 'subscription',
                    'severity' => $daysRemaining <= 3 ? 'error' : 'warning',
                    'title' => 'Подписка истекает',
                    'message' => "Осталось {$daysRemaining} дней до окончания подписки",
                    'days_remaining' => $daysRemaining,
                    'action_url' => '/pricing',
                ];
            }
        }

        // Сортировка по severity
        $severityOrder = ['error' => 0, 'warning' => 1, 'info' => 2];
        usort($alerts, fn($a, $b) => $severityOrder[$a['severity']] <=> $severityOrder[$b['severity']]);

        return [
            'items' => $alerts,
            'total_count' => count($alerts),
            'by_type' => [
                'low_stock' => count(array_filter($alerts, fn($a) => $a['type'] === 'low_stock')),
                'review' => count(array_filter($alerts, fn($a) => $a['type'] === 'review')),
                'supply' => count(array_filter($alerts, fn($a) => $a['type'] === 'supply')),
                'orders' => count(array_filter($alerts, fn($a) => $a['type'] === 'orders')),
                'subscription' => count(array_filter($alerts, fn($a) => $a['type'] === 'subscription')),
            ],
        ];
    }

    /**
     * Данные AI агентов
     */
    private function getAIData(int $companyId): array
    {
        // Задачи AI агентов
        $tasks = AgentTask::where('company_id', $companyId)
            ->with(['latestRun', 'agent'])
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        $runningTasks = $tasks->filter(fn($t) => $t->latestRun?->isRunning())->count();
        $completedToday = AgentTaskRun::whereHas('task', fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'success')
            ->whereDate('finished_at', Carbon::today())
            ->count();
        $failedToday = AgentTaskRun::whereHas('task', fn($q) => $q->where('company_id', $companyId))
            ->where('status', 'failed')
            ->whereDate('finished_at', Carbon::today())
            ->count();

        $recentRuns = AgentTaskRun::whereHas('task', fn($q) => $q->where('company_id', $companyId))
            ->with('task.agent')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($run) => [
                'id' => $run->id,
                'task_title' => $run->task?->title,
                'agent_name' => $run->task?->agent?->name,
                'status' => $run->status,
                'started_at' => $run->started_at?->toIso8601String(),
                'finished_at' => $run->finished_at?->toIso8601String(),
                'result_summary' => $run->result_summary,
            ]);

        // Использование AI лимитов
        $subscription = Subscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->first();

        $aiUsage = [
            'used' => $subscription?->current_ai_requests ?? 0,
            'limit' => $subscription?->plan?->max_ai_requests ?? 0,
            'percentage' => 0,
        ];

        if ($aiUsage['limit'] > 0) {
            $aiUsage['percentage'] = round(($aiUsage['used'] / $aiUsage['limit']) * 100, 1);
        }

        return [
            'running_tasks' => $runningTasks,
            'completed_today' => $completedToday,
            'failed_today' => $failedToday,
            'recent_runs' => $recentRuns,
            'usage' => $aiUsage,
        ];
    }

    /**
     * Данные подписки
     */
    private function getSubscriptionData(int $companyId): array
    {
        $subscription = Subscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        if (!$subscription) {
            return [
                'has_subscription' => false,
                'status' => 'none',
                'message' => 'Нет активной подписки',
            ];
        }

        $plan = $subscription->plan;

        return [
            'has_subscription' => true,
            'status' => $subscription->status,
            'plan' => [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => (float) $plan->price,
                'currency' => $plan->currency,
                'billing_period' => $plan->billing_period,
            ],
            'starts_at' => $subscription->starts_at?->toIso8601String(),
            'ends_at' => $subscription->ends_at?->toIso8601String(),
            'days_remaining' => $subscription->daysRemaining(),
            'is_expiring_soon' => $subscription->ends_at && $subscription->daysRemaining() <= 7,
            'usage' => [
                'products' => [
                    'used' => $subscription->current_products_count,
                    'limit' => $plan->max_products,
                    'percentage' => $plan->max_products > 0
                        ? round(($subscription->current_products_count / $plan->max_products) * 100, 1)
                        : 0,
                ],
                'orders' => [
                    'used' => $subscription->current_orders_count,
                    'limit' => $plan->max_orders_per_month,
                    'percentage' => $plan->max_orders_per_month > 0
                        ? round(($subscription->current_orders_count / $plan->max_orders_per_month) * 100, 1)
                        : 0,
                ],
                'ai_requests' => [
                    'used' => $subscription->current_ai_requests,
                    'limit' => $plan->max_ai_requests,
                    'percentage' => $plan->max_ai_requests > 0
                        ? round(($subscription->current_ai_requests / $plan->max_ai_requests) * 100, 1)
                        : 0,
                ],
                'marketplace_accounts' => [
                    'used' => MarketplaceAccount::where('company_id', $companyId)->count(),
                    'limit' => $plan->max_marketplace_accounts,
                ],
                'warehouses' => [
                    'used' => Warehouse::where('company_id', $companyId)->count(),
                    'limit' => $plan->max_warehouses,
                ],
            ],
            'features' => [
                'has_api_access' => $plan->has_api_access,
                'has_priority_support' => $plan->has_priority_support,
                'has_telegram_notifications' => $plan->has_telegram_notifications,
                'has_auto_pricing' => $plan->has_auto_pricing,
                'has_analytics' => $plan->has_analytics,
            ],
        ];
    }

    /**
     * Данные команды
     */
    private function getTeamData(int $companyId): array
    {
        $company = Company::with('users')->find($companyId);

        if (!$company) {
            return [
                'members_count' => 0,
                'members' => [],
            ];
        }

        $members = $company->users->map(fn($user) => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->pivot->role ?? 'member',
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'is_online' => $user->last_login_at && $user->last_login_at->diffInMinutes(now()) < 15,
        ]);

        // Лимит пользователей из подписки
        $subscription = Subscription::where('company_id', $companyId)
            ->where('status', 'active')
            ->with('plan')
            ->first();

        $maxUsers = $subscription?->plan?->max_users ?? 1;

        return [
            'members_count' => $members->count(),
            'max_members' => $maxUsers,
            'can_invite' => $members->count() < $maxUsers,
            'members' => $members,
            'owner' => $members->firstWhere('role', 'owner'),
        ];
    }

    /**
     * Данные поставок
     */
    private function getSuppliesData(int $companyId): array
    {
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        $activeSupplies = WildberriesSupply::whereIn('marketplace_account_id', $accountIds)
            ->active()
            ->count();

        $deliveredThisMonth = WildberriesSupply::whereIn('marketplace_account_id', $accountIds)
            ->delivered()
            ->whereMonth('closed_at', now()->month)
            ->count();

        $recentSupplies = WildberriesSupply::whereIn('marketplace_account_id', $accountIds)
            ->with('marketplaceAccount')
            ->orderByDesc('created_at_wb')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'id' => $s->id,
                'supply_id' => $s->supply_id,
                'name' => $s->name,
                'status' => $s->status,
                'orders_count' => $s->orders_count,
                'marketplace' => $s->marketplaceAccount?->marketplace,
                'created_at' => $s->created_at_wb?->toIso8601String(),
                'closed_at' => $s->closed_at?->toIso8601String(),
            ]);

        return [
            'active_count' => $activeSupplies,
            'delivered_this_month' => $deliveredThisMonth,
            'recent' => $recentSupplies,
        ];
    }

    /**
     * Данные отзывов
     */
    private function getReviewsData(int $companyId): array
    {
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');
        $monthAgo = Carbon::today()->subDays(30);

        // Общая статистика
        $totalReviews = Review::where('company_id', $companyId)
            ->where('review_date', '>=', $monthAgo)
            ->count();

        $pendingReviews = Review::where('company_id', $companyId)
            ->whereNull('response_text')
            ->count();

        $avgRating = Review::where('company_id', $companyId)
            ->where('review_date', '>=', $monthAgo)
            ->avg('rating');

        // По рейтингам
        $byRating = Review::where('company_id', $companyId)
            ->where('review_date', '>=', $monthAgo)
            ->select('rating', DB::raw('COUNT(*) as count'))
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->toArray();

        // Последние негативные
        $recentNegative = Review::where('company_id', $companyId)
            ->where('rating', '<=', 2)
            ->whereNull('response_text')
            ->orderByDesc('review_date')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'text' => mb_substr($r->review_text ?? '', 0, 150),
                'date' => $r->review_date?->toIso8601String(),
                'marketplace' => $r->marketplace,
            ]);

        return [
            'total_this_month' => $totalReviews,
            'pending_response' => $pendingReviews,
            'average_rating' => $avgRating ? round($avgRating, 1) : null,
            'by_rating' => $byRating,
            'recent_negative' => $recentNegative,
            'sentiment' => [
                'positive' => ($byRating[5] ?? 0) + ($byRating[4] ?? 0),
                'neutral' => $byRating[3] ?? 0,
                'negative' => ($byRating[2] ?? 0) + ($byRating[1] ?? 0),
            ],
        ];
    }

    /**
     * Получить русский перевод статуса заказа
     */
    /**
     * Normalize WildberriesOrder status based on flags
     */
    private function normalizeWbStatus(WildberriesOrder $order): string
    {
        if ($order->is_cancel) {
            return 'cancelled';
        }
        if ($order->is_return) {
            return 'cancelled';
        }
        if ($order->is_realization) {
            return 'completed';
        }

        $wbStatus = $order->wb_status;
        if ($wbStatus) {
            $statusMap = [
                'waiting' => 'new',
                'sorted' => 'processing',
                'sold' => 'completed',
                'delivered' => 'completed',
                'canceled' => 'cancelled',
                'canceled_by_client' => 'cancelled',
                'defect' => 'cancelled',
                'ready_for_pickup' => 'processing',
                'on_way_to_client' => 'processing',
            ];

            if (isset($statusMap[$wbStatus])) {
                return $statusMap[$wbStatus];
            }
        }

        return 'new';
    }

    private function getStatusLabel(string $status): string
    {
        $labels = [
            'new' => 'Новый',
            'pending' => 'Ожидает',
            'processing' => 'В обработке',
            'transit' => 'В транзите',
            'in_assembly' => 'В сборке',
            'assembling' => 'В сборке',
            'assembled' => 'Собран',
            'in_delivery' => 'В доставке',
            'delivering' => 'В доставке',
            'delivered' => 'Доставлен',
            'completed' => 'Продан',
            'cancelled' => 'Отменён',
            'canceled' => 'Отменён',
            'returned' => 'Возврат',
            'archive' => 'Архив',
            'waiting' => 'Ожидание',
            'sorted' => 'Отсортирован',
            'sold' => 'Продан',
            'defect' => 'Брак',
            'ready_for_pickup' => 'Готов к выдаче',
        ];

        return $labels[$status] ?? $status;
    }
}
