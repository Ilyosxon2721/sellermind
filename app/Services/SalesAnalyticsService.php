<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Models\WildberriesOrder;
use App\Models\YandexMarketOrder;
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
     * Агрегирует данные из всех маркетплейсов (WB, Uzum, Ozon, YandexMarket).
     */
    public function getSalesByDay(int $companyId, string $period = '30days'): array
    {
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        if ($accountIds->isEmpty()) {
            return ['labels' => [], 'quantities' => [], 'revenues' => []];
        }

        // WB Statistics API: каждая строка = один товар
        $wbStats = DB::table('wildberries_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->select(
                DB::raw('DATE(order_date) as date'),
                DB::raw('COUNT(*) as quantity'),
                DB::raw('SUM(for_pay) as revenue')
            )
            ->groupBy('date');

        // Uzum: из uzum_orders (total_amount — сумма заказа целиком)
        $uzumStats = DB::table('uzum_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->select(
                DB::raw('DATE(ordered_at) as date'),
                DB::raw('COUNT(*) as quantity'),
                DB::raw('SUM(total_amount) as revenue')
            )
            ->groupBy('date');

        // Ozon
        $ozonStats = DB::table('ozon_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->select(
                DB::raw('DATE(created_at_ozon) as date'),
                DB::raw('COUNT(*) as quantity'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->groupBy('date');

        // Yandex Market
        $ymStats = DB::table('yandex_market_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->select(
                DB::raw('DATE(created_at_ym) as date'),
                DB::raw('COUNT(*) as quantity'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->groupBy('date');

        // Собираем все данные и группируем по дате
        $allData = collect()
            ->merge($wbStats->get())
            ->merge($uzumStats->get())
            ->merge($ozonStats->get())
            ->merge($ymStats->get())
            ->groupBy('date')
            ->map(fn ($rows) => [
                'date' => $rows->first()->date,
                'quantity' => $rows->sum('quantity'),
                'revenue' => $rows->sum('revenue'),
            ])
            ->sortBy('date')
            ->values();

        return [
            'labels' => $allData->pluck('date')->toArray(),
            'quantities' => $allData->pluck('quantity')->map(fn ($q) => (int) $q)->toArray(),
            'revenues' => $allData->pluck('revenue')->map(fn ($r) => round((float) $r, 2))->toArray(),
        ];
    }

    /**
     * Получить топ продаваемых товаров.
     * Агрегирует из order items всех маркетплейсов по названию товара.
     */
    public function getTopProducts(int $companyId, string $period = '30days', int $limit = 10): Collection
    {
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        $items = $this->getAggregatedOrderItems($accountIds, $dateRange);

        return $items
            ->sortByDesc('total_revenue')
            ->take($limit)
            ->values()
            ->map(fn ($item) => [
                'product_id' => null,
                'product_name' => $item['name'],
                'sku' => $item['external_offer_id'],
                'total_quantity' => $item['total_quantity'],
                'total_revenue' => round($item['total_revenue'], 2),
                'order_count' => $item['order_count'],
                'avg_price' => $item['total_quantity'] > 0
                    ? round($item['total_revenue'] / $item['total_quantity'], 2)
                    : 0,
            ]);
    }

    /**
     * Получить аутсайдеров продаж (flop).
     */
    public function getFlopProducts(int $companyId, string $period = '30days', int $limit = 10): Collection
    {
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        $items = $this->getAggregatedOrderItems($accountIds, $dateRange);

        return $items
            ->sortBy('total_revenue')
            ->take($limit)
            ->values()
            ->map(fn ($item) => [
                'product_id' => null,
                'product_name' => $item['name'],
                'sku' => $item['external_offer_id'],
                'total_quantity' => $item['total_quantity'],
                'total_revenue' => round($item['total_revenue'], 2),
                'order_count' => $item['order_count'],
                'avg_price' => $item['total_quantity'] > 0
                    ? round($item['total_revenue'] / $item['total_quantity'], 2)
                    : 0,
                'status' => 'low_sales',
            ]);
    }

    /**
     * Получить продажи по категориям.
     * Использует категории из WB Statistics API (subject/category) и названия товаров.
     */
    public function getSalesByCategory(int $companyId, string $period = '30days'): Collection
    {
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        if ($accountIds->isEmpty()) {
            return collect();
        }

        // WB Statistics API имеет поля category/subject
        $wbCategories = DB::table('wildberries_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->select(
                DB::raw('COALESCE(category, subject, "Без категории") as category_name'),
                DB::raw('COUNT(*) as total_quantity'),
                DB::raw('SUM(for_pay) as total_revenue')
            )
            ->groupBy('category_name')
            ->get();

        // Для остальных маркетплейсов группируем по маркетплейсу как «категория»
        $uzumRow = DB::table('uzum_order_items')
            ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('SUM(uzum_order_items.quantity) as total_quantity, SUM(uzum_order_items.total_price) as total_revenue')
            ->first();

        $ozonRow = DB::table('ozon_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as total_quantity, SUM(total_price) as total_revenue')
            ->first();

        $ymRow = DB::table('yandex_market_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as total_quantity, SUM(total_price) as total_revenue')
            ->first();

        $result = $wbCategories->map(fn ($item) => [
            'category_name' => $item->category_name,
            'total_quantity' => (int) $item->total_quantity,
            'total_revenue' => round((float) $item->total_revenue, 2),
        ])->toArray();

        // Добавляем другие маркетплейсы как отдельные категории
        if (($uzumRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Uzum Market',
                'total_quantity' => (int) $uzumRow->total_quantity,
                'total_revenue' => round((float) ($uzumRow->total_revenue ?? 0), 2),
            ];
        }

        if (($ozonRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Ozon',
                'total_quantity' => (int) $ozonRow->total_quantity,
                'total_revenue' => round((float) ($ozonRow->total_revenue ?? 0), 2),
            ];
        }

        if (($ymRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Yandex Market',
                'total_quantity' => (int) $ymRow->total_quantity,
                'total_revenue' => round((float) ($ymRow->total_revenue ?? 0), 2),
            ];
        }

        return collect($result)->sortByDesc('total_revenue')->values();
    }

    /**
     * Получить продажи по маркетплейсам.
     * Агрегирует из таблиц заказов каждого маркетплейса.
     */
    public function getSalesByMarketplace(int $companyId, string $period = '30days'): Collection
    {
        $dateRange = $this->getDateRange($period);
        $accounts = MarketplaceAccount::where('company_id', $companyId)->get();

        if ($accounts->isEmpty()) {
            return collect();
        }

        $result = [];

        foreach ($accounts as $account) {
            $stats = $this->getAccountStats($account, $dateRange);

            if ($stats['orders'] > 0) {
                $result[] = [
                    'marketplace' => $account->marketplace,
                    'account_name' => $account->name,
                    'total_quantity' => $stats['quantity'],
                    'total_revenue' => round($stats['revenue'], 2),
                    'order_count' => $stats['orders'],
                ];
            }
        }

        return collect($result)->sortByDesc('total_revenue')->values();
    }

    /**
     * Получить метрики производительности товара.
     * Ищет товар по ID через WB Statistics API (nm_id) и order items.
     */
    public function getProductPerformance(int $productId, string $period = '30days'): array
    {
        $dateRange = $this->getDateRange($period);

        // Ищем nm_id связанных вариантов через wildberries_products
        $nmIds = DB::table('wildberries_products')
            ->join('marketplace_product_links', function ($join) {
                $join->on('wildberries_products.nm_id', '=', 'marketplace_product_links.external_product_id')
                    ->where('marketplace_product_links.marketplace', '=', 'wb');
            })
            ->join('product_variants', 'marketplace_product_links.product_variant_id', '=', 'product_variants.id')
            ->where('product_variants.product_id', $productId)
            ->pluck('wildberries_products.nm_id');

        // WB Statistics — по nm_id
        $wbStats = null;
        $wbByDay = collect();

        if ($nmIds->isNotEmpty()) {
            $wbStats = DB::table('wildberries_orders')
                ->whereIn('nm_id', $nmIds)
                ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
                ->where('is_cancel', false)
                ->where('is_return', false)
                ->selectRaw('COUNT(*) as total_quantity, SUM(for_pay) as total_revenue, COUNT(DISTINCT order_id) as order_count, AVG(for_pay) as avg_price')
                ->first();

            $wbByDay = DB::table('wildberries_orders')
                ->whereIn('nm_id', $nmIds)
                ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
                ->where('is_cancel', false)
                ->where('is_return', false)
                ->select(
                    DB::raw('DATE(order_date) as date'),
                    DB::raw('COUNT(*) as quantity')
                )
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        $totalQuantity = (int) ($wbStats->total_quantity ?? 0);
        $totalRevenue = (float) ($wbStats->total_revenue ?? 0);
        $orderCount = (int) ($wbStats->order_count ?? 0);
        $avgPrice = (float) ($wbStats->avg_price ?? 0);

        return [
            'total_quantity' => $totalQuantity,
            'total_revenue' => round($totalRevenue, 2),
            'order_count' => $orderCount,
            'avg_price' => round($avgPrice, 2),
            'sales_by_day' => $wbByDay->map(fn ($item) => [
                'date' => $item->date,
                'quantity' => (int) $item->quantity,
            ])->toArray(),
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
            '365days' => now()->subDays(365),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            'all' => now()->subYears(10),
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
     *
     * @param  \Illuminate\Support\Collection<int, int>  $accountIds
     */
    protected function getTotalSales(Collection $accountIds, array $dateRange): int
    {
        if ($accountIds->isEmpty()) {
            return 0;
        }

        // Uzum: SQL JOIN — устраняет N+1
        $uzumSales = DB::table('uzum_order_items')
            ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->sum('uzum_order_items.quantity');

        // WB Statistics API: каждая строка — один товар
        $wbSales = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->count();

        // Ozon
        $ozonSales = OzonOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->count();

        // Yandex Market
        $ymSales = YandexMarketOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->count();

        return (int) ($uzumSales + $wbSales + $ozonSales + $ymSales);
    }

    /**
     * Получить выручку и количество заказов за период одним запросом.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $accountIds
     * @return array{revenue: float, orders: int}
     */
    protected function getRevenueAndOrders(Collection $accountIds, array $dateRange): array
    {
        if ($accountIds->isEmpty()) {
            return ['revenue' => 0.0, 'orders' => 0];
        }

        // Uzum
        $uzumRow = DB::table('uzum_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('SUM(total_amount) as revenue, COUNT(*) as orders')
            ->first();

        // WB Statistics API
        $wbRow = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->selectRaw('SUM(for_pay) as revenue, COUNT(*) as orders')
            ->first();

        // Ozon
        $ozonRow = OzonOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->selectRaw('SUM(total_price) as revenue, COUNT(*) as orders')
            ->first();

        // Yandex Market
        $ymRow = YandexMarketOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('SUM(total_price) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'revenue' => (float) (
                ($uzumRow->revenue ?? 0) +
                ($wbRow->revenue ?? 0) +
                ($ozonRow->revenue ?? 0) +
                ($ymRow->revenue ?? 0)
            ),
            'orders' => (int) (
                ($uzumRow->orders ?? 0) +
                ($wbRow->orders ?? 0) +
                ($ozonRow->orders ?? 0) +
                ($ymRow->orders ?? 0)
            ),
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

        // Uzum
        $uzumRow = DB::table('uzum_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
            ->first();

        // WB Statistics API
        $wbRow = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where(fn ($q) => $q->where('is_cancel', true)->orWhere('is_return', true))
            ->selectRaw('COUNT(*) as cnt, SUM(for_pay) as amount')
            ->first();

        // Ozon
        $ozonRow = OzonOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereIn('status', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as cnt, SUM(total_price) as amount')
            ->first();

        // Yandex Market
        $ymRow = YandexMarketOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as cnt, SUM(total_price) as amount')
            ->first();

        return [
            'count' => (int) (
                ($uzumRow->cnt ?? 0) +
                ($wbRow->cnt ?? 0) +
                ($ozonRow->cnt ?? 0) +
                ($ymRow->cnt ?? 0)
            ),
            'amount' => (float) (
                ($uzumRow->amount ?? 0) +
                ($wbRow->amount ?? 0) +
                ($ozonRow->amount ?? 0) +
                ($ymRow->amount ?? 0)
            ),
        ];
    }

    /**
     * Агрегировать данные по товарам из order items всех маркетплейсов.
     * Группирует по external_offer_id (артикул продавца).
     */
    protected function getAggregatedOrderItems(Collection $accountIds, array $dateRange): Collection
    {
        // WB Statistics API: supplier_article как идентификатор товара
        $wbItems = DB::table('wildberries_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->select(
                DB::raw('COALESCE(supplier_article, CAST(nm_id AS CHAR)) as external_offer_id'),
                DB::raw('COALESCE(subject, supplier_article, CAST(nm_id AS CHAR)) as name'),
                DB::raw('1 as quantity'),
                DB::raw('for_pay as total_price'),
                DB::raw('order_id')
            )
            ->get()
            ->groupBy('external_offer_id')
            ->map(fn ($rows) => [
                'external_offer_id' => $rows->first()->external_offer_id,
                'name' => $rows->first()->name,
                'total_quantity' => $rows->count(),
                'total_revenue' => (float) $rows->sum('total_price'),
                'order_count' => $rows->unique('order_id')->count(),
            ]);

        // Uzum order items
        $uzumItems = DB::table('uzum_order_items')
            ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->select(
                'uzum_order_items.external_offer_id',
                'uzum_order_items.name',
                'uzum_order_items.quantity',
                'uzum_order_items.total_price',
                'uzum_orders.id as order_id'
            )
            ->get()
            ->groupBy('external_offer_id')
            ->map(fn ($rows) => [
                'external_offer_id' => $rows->first()->external_offer_id,
                'name' => $rows->first()->name ?? $rows->first()->external_offer_id,
                'total_quantity' => (int) $rows->sum('quantity'),
                'total_revenue' => (float) $rows->sum('total_price'),
                'order_count' => $rows->unique('order_id')->count(),
            ]);

        // WB FBS order items (wb_orders / wb_order_items)
        $wbFbsItems = DB::table('wb_order_items')
            ->join('wb_orders', 'wb_order_items.wb_order_id', '=', 'wb_orders.id')
            ->whereIn('wb_orders.marketplace_account_id', $accountIds)
            ->whereBetween('wb_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('wb_orders.status_normalized', self::CANCELLED_STATUSES)
            ->select(
                'wb_order_items.external_offer_id',
                'wb_order_items.name',
                'wb_order_items.quantity',
                'wb_order_items.total_price',
                'wb_orders.id as order_id'
            )
            ->get()
            ->groupBy('external_offer_id')
            ->map(fn ($rows) => [
                'external_offer_id' => $rows->first()->external_offer_id,
                'name' => $rows->first()->name ?? $rows->first()->external_offer_id,
                'total_quantity' => (int) $rows->sum('quantity'),
                'total_revenue' => (float) $rows->sum('total_price'),
                'order_count' => $rows->unique('order_id')->count(),
            ]);

        // Объединяем все данные, суммируя по external_offer_id
        $merged = collect();

        foreach ([$wbItems, $uzumItems, $wbFbsItems] as $items) {
            foreach ($items as $key => $item) {
                if ($merged->has($key)) {
                    $existing = $merged->get($key);
                    $merged->put($key, [
                        'external_offer_id' => $item['external_offer_id'],
                        'name' => $existing['name'] ?: $item['name'],
                        'total_quantity' => $existing['total_quantity'] + $item['total_quantity'],
                        'total_revenue' => $existing['total_revenue'] + $item['total_revenue'],
                        'order_count' => $existing['order_count'] + $item['order_count'],
                    ]);
                } else {
                    $merged->put($key, $item);
                }
            }
        }

        return $merged;
    }

    /**
     * Получить статистику по конкретному аккаунту маркетплейса.
     *
     * @return array{quantity: int, revenue: float, orders: int}
     */
    protected function getAccountStats(MarketplaceAccount $account, array $dateRange): array
    {
        $accountId = $account->id;

        return match ($account->marketplace) {
            'wb' => $this->getWbAccountStats($accountId, $dateRange),
            'uzum' => $this->getUzumAccountStats($accountId, $dateRange),
            'ozon' => $this->getOzonAccountStats($accountId, $dateRange),
            'ym', 'yandex_market' => $this->getYmAccountStats($accountId, $dateRange),
            default => ['quantity' => 0, 'revenue' => 0.0, 'orders' => 0],
        };
    }

    /**
     * @return array{quantity: int, revenue: float, orders: int}
     */
    protected function getWbAccountStats(int $accountId, array $dateRange): array
    {
        $row = WildberriesOrder::where('marketplace_account_id', $accountId)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->selectRaw('COUNT(*) as quantity, SUM(for_pay) as revenue, COUNT(DISTINCT order_id) as orders')
            ->first();

        return [
            'quantity' => (int) ($row->quantity ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * @return array{quantity: int, revenue: float, orders: int}
     */
    protected function getUzumAccountStats(int $accountId, array $dateRange): array
    {
        $row = DB::table('uzum_orders')
            ->where('marketplace_account_id', $accountId)
            ->whereBetween('ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as orders, SUM(total_amount) as revenue')
            ->first();

        $quantity = DB::table('uzum_order_items')
            ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->where('uzum_orders.marketplace_account_id', $accountId)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->sum('uzum_order_items.quantity');

        return [
            'quantity' => (int) $quantity,
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * @return array{quantity: int, revenue: float, orders: int}
     */
    protected function getOzonAccountStats(int $accountId, array $dateRange): array
    {
        $row = OzonOrder::where('marketplace_account_id', $accountId)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as orders, SUM(total_price) as revenue')
            ->first();

        return [
            'quantity' => (int) ($row->orders ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }

    /**
     * @return array{quantity: int, revenue: float, orders: int}
     */
    protected function getYmAccountStats(int $accountId, array $dateRange): array
    {
        $row = YandexMarketOrder::where('marketplace_account_id', $accountId)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('COUNT(*) as orders, SUM(total_price) as revenue')
            ->first();

        return [
            'quantity' => (int) ($row->orders ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }
}
