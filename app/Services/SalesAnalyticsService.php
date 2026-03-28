<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use App\Models\OzonOrder;
use App\Models\WildberriesOrder;
use App\Models\YandexMarketOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class SalesAnalyticsService
{
    /**
     * Статусы отменённых заказов (исключаются из расчёта выручки).
     * Yandex Market использует status_normalized: 'cancelled' (CANCELLED) и 'returned' (RETURNED).
     * Ozon использует поле status: 'cancelled'/'canceled'.
     * Uzum использует status_normalized.
     */
    private const CANCELLED_STATUSES = [
        // Lowercase (status_normalized Uzum/YM, status Ozon)
        'cancelled',
        'canceled',
        'returned',
        // Uppercase (статусы Yandex Market в поле status, на случай прямых запросов)
        'CANCELLED',
        'CANCELED',
        'RETURNED',
        // Ozon специфичные
        'PENDING_CANCELLATION',
    ];

    /**
     * Валюты маркетплейсов: WB, Ozon, YandexMarket — RUB; Uzum — UZS
     */
    private const MARKETPLACE_CURRENCIES = [
        'wb' => 'RUB',
        'ozon' => 'RUB',
        'ym' => 'RUB',
        'yandex_market' => 'RUB',
        'uzum' => 'UZS',
    ];

    /**
     * ID компании для текущего запроса (используется в protected методах для ручных/оффлайн продаж)
     */
    private int $companyId = 0;

    public function __construct(
        private readonly ?CurrencyConversionService $currencyService = null,
    ) {}

    /**
     * Установить контекст компании для конвертации валют.
     * Определяет валюту отображения и пользовательские курсы обмена.
     */
    public function forCompany(int $companyId): self
    {
        $company = Company::find($companyId);
        if ($company && $this->currencyService !== null) {
            $this->currencyService->forCompany($company);
        }

        return $this;
    }

    /**
     * Получить общую сводку продаж за период.
     * $accountIds запрашивается один раз и передаётся во все вспомогательные методы.
     */
    public function getOverview(int $companyId, string $period = '30days'): array
    {
        $this->companyId = $companyId;
        $this->forCompany($companyId);
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
        $revenueGrowth = $prevRevenue > 0
            ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100
            : ($totalRevenue > 0 ? 100.0 : 0);

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
        $this->companyId = $companyId;
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

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

        // Uzum: JOIN с uzum_order_items для подсчёта реального количества товаров
        $uzumStats = DB::table('uzum_orders')
            ->join('uzum_order_items', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->select(
                DB::raw('DATE(uzum_orders.ordered_at) as date'),
                DB::raw('SUM(uzum_order_items.quantity) as quantity'),
                DB::raw('SUM(uzum_order_items.total_price) as revenue')
            )
            ->groupBy('date');

        // Ozon
        $ozonStats = DB::table('ozon_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->select(
                DB::raw('DATE(created_at_ozon) as date'),
                DB::raw('SUM(COALESCE(items_count, 1)) as quantity'),
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
                DB::raw('SUM(COALESCE(items_count, 1)) as quantity'),
                DB::raw('SUM(total_price) as revenue')
            )
            ->groupBy('date');

        // Собираем данные маркетплейсов (только если есть аккаунты)
        // Конвертируем выручку каждого маркетплейса в единую валюту отображения
        $allData = collect();

        if ($accountIds->isNotEmpty()) {
            $convertRevenue = function (Collection $rows, string $currency): Collection {
                return $rows->map(function ($row) use ($currency) {
                    $row->revenue = $this->convertToDisplay((float) $row->revenue, $currency);

                    return $row;
                });
            };

            $allData = $allData
                ->merge($convertRevenue($wbStats->get(), 'RUB'))
                ->merge($convertRevenue($uzumStats->get(), 'UZS'))
                ->merge($convertRevenue($ozonStats->get(), 'RUB'))
                ->merge($convertRevenue($ymStats->get(), 'RUB'));
        }

        // Ручные продажи: GROUP BY DATE(created_at), сумма количеств и выручки
        $manualStats = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.company_id', $companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->select(
                DB::raw('DATE(sales.created_at) as date'),
                DB::raw('SUM(sale_items.quantity) as quantity'),
                DB::raw('SUM(sale_items.total) as revenue')
            )
            ->groupBy('date')
            ->get();

        // Оффлайн продажи: GROUP BY DATE(sale_date), сумма количеств и выручки
        $offlineStats = DB::table('offline_sale_items')
            ->join('offline_sales', 'offline_sale_items.offline_sale_id', '=', 'offline_sales.id')
            ->where('offline_sales.company_id', $companyId)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('offline_sales.status', ['confirmed', 'delivered'])
            ->whereNull('offline_sales.deleted_at')
            ->select(
                DB::raw('DATE(offline_sales.sale_date) as date'),
                DB::raw('SUM(offline_sale_items.quantity) as quantity'),
                DB::raw('SUM(offline_sale_items.line_total) as revenue')
            )
            ->groupBy('date')
            ->get();

        // Ручные и оффлайн продажи уже в валюте компании — конвертация не требуется
        $allData = $allData
            ->merge($manualStats)
            ->merge($offlineStats)
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
        $this->companyId = $companyId;
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

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
        $this->companyId = $companyId;
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

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
        $this->companyId = $companyId;
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        $result = [];

        if ($accountIds->isNotEmpty()) {
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
            ->selectRaw('SUM(COALESCE(items_count, 1)) as total_quantity, SUM(total_price) as total_revenue')
            ->first();

        $ymRow = DB::table('yandex_market_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('SUM(COALESCE(items_count, 1)) as total_quantity, SUM(total_price) as total_revenue')
            ->first();

        // Конвертируем выручку WB категорий из RUB в валюту отображения
        $result = $wbCategories->map(fn ($item) => [
            'category_name' => $item->category_name,
            'total_quantity' => (int) $item->total_quantity,
            'total_revenue' => round($this->convertToDisplay((float) $item->total_revenue, 'RUB'), 2),
        ])->toArray();

        // Добавляем другие маркетплейсы как отдельные категории (с конвертацией валют)
        if (($uzumRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Uzum Market',
                'total_quantity' => (int) $uzumRow->total_quantity,
                'total_revenue' => round($this->convertToDisplay((float) ($uzumRow->total_revenue ?? 0), 'UZS'), 2),
            ];
        }

        if (($ozonRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Ozon',
                'total_quantity' => (int) $ozonRow->total_quantity,
                'total_revenue' => round($this->convertToDisplay((float) ($ozonRow->total_revenue ?? 0), 'RUB'), 2),
            ];
        }

        if (($ymRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Yandex Market',
                'total_quantity' => (int) $ymRow->total_quantity,
                'total_revenue' => round($this->convertToDisplay((float) ($ymRow->total_revenue ?? 0), 'RUB'), 2),
            ];
        }
        } // end if ($accountIds->isNotEmpty())

        // Ручные продажи: агрегируем из sale_items
        $manualRow = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.company_id', $companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity, SUM(sale_items.total) as total_revenue')
            ->first();

        if (($manualRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Ручные продажи',
                'total_quantity' => (int) $manualRow->total_quantity,
                'total_revenue' => round((float) ($manualRow->total_revenue ?? 0), 2),
            ];
        }

        // Оффлайн продажи: агрегируем из offline_sale_items
        $offlineRow = DB::table('offline_sale_items')
            ->join('offline_sales', 'offline_sale_items.offline_sale_id', '=', 'offline_sales.id')
            ->where('offline_sales.company_id', $companyId)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('offline_sales.status', ['cancelled', 'returned'])
            ->whereNull('offline_sales.deleted_at')
            ->selectRaw('SUM(offline_sale_items.quantity) as total_quantity, SUM(offline_sale_items.line_total) as total_revenue')
            ->first();

        if (($offlineRow->total_quantity ?? 0) > 0) {
            $result[] = [
                'category_name' => 'Оффлайн продажи',
                'total_quantity' => (int) $offlineRow->total_quantity,
                'total_revenue' => round((float) ($offlineRow->total_revenue ?? 0), 2),
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
        $this->companyId = $companyId;
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accounts = MarketplaceAccount::where('company_id', $companyId)->get();

        $result = [];

        foreach ($accounts as $account) {
            $stats = $this->getAccountStats($account, $dateRange);

            if ($stats['orders'] > 0) {
                // Конвертируем выручку из валюты маркетплейса в валюту отображения
                $currency = self::MARKETPLACE_CURRENCIES[$account->marketplace] ?? 'RUB';
                $convertedRevenue = $this->convertToDisplay($stats['revenue'], $currency);

                $result[] = [
                    'marketplace' => $account->marketplace,
                    'account_name' => $account->name,
                    'total_quantity' => $stats['quantity'],
                    'total_revenue' => round($convertedRevenue, 2),
                    'order_count' => $stats['orders'],
                ];
            }
        }

        // Ручные продажи (из таблицы sales, фильтр по company_id)
        $manualRow = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.company_id', $companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->selectRaw('SUM(sale_items.quantity) as total_quantity, SUM(sale_items.total) as total_revenue, COUNT(DISTINCT sales.id) as order_count')
            ->first();

        if (($manualRow->order_count ?? 0) > 0) {
            $result[] = [
                'marketplace' => 'manual',
                'account_name' => 'Ручные продажи',
                'total_quantity' => (int) ($manualRow->total_quantity ?? 0),
                'total_revenue' => round((float) ($manualRow->total_revenue ?? 0), 2),
                'order_count' => (int) $manualRow->order_count,
            ];
        }

        // Оффлайн продажи (из таблицы offline_sales, фильтр по company_id)
        $offlineRow = DB::table('offline_sale_items')
            ->join('offline_sales', 'offline_sale_items.offline_sale_id', '=', 'offline_sales.id')
            ->where('offline_sales.company_id', $companyId)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('offline_sales.status', ['confirmed', 'delivered'])
            ->whereNull('offline_sales.deleted_at')
            ->selectRaw('SUM(offline_sale_items.quantity) as total_quantity, SUM(offline_sale_items.line_total) as total_revenue, COUNT(DISTINCT offline_sales.id) as order_count')
            ->first();

        if (($offlineRow->order_count ?? 0) > 0) {
            $result[] = [
                'marketplace' => 'offline',
                'account_name' => 'Оффлайн продажи',
                'total_quantity' => (int) ($offlineRow->total_quantity ?? 0),
                'total_revenue' => round((float) ($offlineRow->total_revenue ?? 0), 2),
                'order_count' => (int) $offlineRow->order_count,
            ];
        }

        return collect($result)->sortByDesc('total_revenue')->values();
    }

    /**
     * Получить метрики производительности товара.
     * Агрегирует данные по всем маркетплейсам: WB, Uzum, Ozon, Yandex Market.
     */
    public function getProductPerformance(int $productId, string $period = '30days'): array
    {
        $dateRange = $this->getDateRange($period);

        // Получаем связи вариантов товара со всеми маркетплейсами
        $variantIds = DB::table('product_variants')
            ->where('product_id', $productId)
            ->pluck('id');

        if ($variantIds->isEmpty()) {
            return $this->buildEmptyPerformanceResult();
        }

        $links = DB::table('variant_marketplace_links')
            ->whereIn('product_variant_id', $variantIds)
            ->where('is_active', true)
            ->get();

        // Собираем данные по каждому маркетплейсу
        $wbResult = $this->getWbProductPerformance($links, $dateRange);
        $uzumResult = $this->getUzumProductPerformance($links, $dateRange);
        $ozonResult = $this->getOzonProductPerformance($links, $dateRange);
        $ymResult = $this->getYmProductPerformance($links, $dateRange);

        // Конвертируем выручку каждого маркетплейса в единую валюту отображения
        $wbRevenueConverted = $this->convertToDisplay($wbResult['revenue'], 'RUB');
        $uzumRevenueConverted = $this->convertToDisplay($uzumResult['revenue'], 'UZS');
        $ozonRevenueConverted = $this->convertToDisplay($ozonResult['revenue'], 'RUB');
        $ymRevenueConverted = $this->convertToDisplay($ymResult['revenue'], 'RUB');

        // Суммируем итоги со всех маркетплейсов
        $totalQuantity = $wbResult['quantity'] + $uzumResult['quantity'] + $ozonResult['quantity'] + $ymResult['quantity'];
        $totalRevenue = $wbRevenueConverted + $uzumRevenueConverted + $ozonRevenueConverted + $ymRevenueConverted;
        $orderCount = $wbResult['orders'] + $uzumResult['orders'] + $ozonResult['orders'] + $ymResult['orders'];

        // Средняя цена — средневзвешенная по количеству
        $avgPrice = $totalQuantity > 0
            ? $totalRevenue / $totalQuantity
            : 0.0;

        // Объединяем продажи по дням со всех маркетплейсов
        $salesByDay = $this->mergeSalesByDay(
            $wbResult['by_day'],
            $uzumResult['by_day'],
            $ozonResult['by_day'],
            $ymResult['by_day'],
        );

        return [
            'total_quantity' => $totalQuantity,
            'total_revenue' => round($totalRevenue, 2),
            'order_count' => $orderCount,
            'avg_price' => round($avgPrice, 2),
            'sales_by_day' => $salesByDay,
            'breakdown' => [
                'wb' => [
                    'quantity' => $wbResult['quantity'],
                    'revenue' => round($wbRevenueConverted, 2),
                    'orders' => $wbResult['orders'],
                ],
                'uzum' => [
                    'quantity' => $uzumResult['quantity'],
                    'revenue' => round($uzumRevenueConverted, 2),
                    'orders' => $uzumResult['orders'],
                ],
                'ozon' => [
                    'quantity' => $ozonResult['quantity'],
                    'revenue' => round($ozonRevenueConverted, 2),
                    'orders' => $ozonResult['orders'],
                ],
                'yandex_market' => [
                    'quantity' => $ymResult['quantity'],
                    'revenue' => round($ymRevenueConverted, 2),
                    'orders' => $ymResult['orders'],
                ],
            ],
        ];
    }

    /**
     * Пустой результат производительности товара.
     *
     * @return array<string, mixed>
     */
    private function buildEmptyPerformanceResult(): array
    {
        $emptyBreakdown = ['quantity' => 0, 'revenue' => 0.0, 'orders' => 0];

        return [
            'total_quantity' => 0,
            'total_revenue' => 0.0,
            'order_count' => 0,
            'avg_price' => 0.0,
            'sales_by_day' => [],
            'breakdown' => [
                'wb' => $emptyBreakdown,
                'uzum' => $emptyBreakdown,
                'ozon' => $emptyBreakdown,
                'yandex_market' => $emptyBreakdown,
            ],
        ];
    }

    /**
     * Производительность товара на Wildberries.
     * Ищет nm_id через variant_marketplace_links.external_offer_id и считает по wildberries_orders.
     *
     * @return array{quantity: int, revenue: float, orders: int, by_day: Collection}
     */
    private function getWbProductPerformance(Collection $links, array $dateRange): array
    {
        $empty = ['quantity' => 0, 'revenue' => 0.0, 'orders' => 0, 'by_day' => collect()];

        // Берём external_offer_id для WB-связей (это nm_id)
        $wbLinks = $links->filter(fn ($link) => in_array($link->marketplace_code, ['wb', 'wildberries']));

        if ($wbLinks->isEmpty()) {
            return $empty;
        }

        // external_offer_id при автолинковке WB = nm_id
        $nmIds = $wbLinks->pluck('external_offer_id')->filter()->unique()->values();

        if ($nmIds->isEmpty()) {
            return $empty;
        }

        $stats = DB::table('wildberries_orders')
            ->whereIn('nm_id', $nmIds)
            ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->selectRaw('COUNT(*) as total_quantity, SUM(for_pay) as total_revenue, COUNT(DISTINCT order_id) as order_count')
            ->first();

        $byDay = DB::table('wildberries_orders')
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

        return [
            'quantity' => (int) ($stats->total_quantity ?? 0),
            'revenue' => (float) ($stats->total_revenue ?? 0),
            'orders' => (int) ($stats->order_count ?? 0),
            'by_day' => $byDay,
        ];
    }

    /**
     * Производительность товара на Uzum.
     * Ищет по external_offer_id через uzum_order_items.
     *
     * @return array{quantity: int, revenue: float, orders: int, by_day: Collection}
     */
    private function getUzumProductPerformance(Collection $links, array $dateRange): array
    {
        $empty = ['quantity' => 0, 'revenue' => 0.0, 'orders' => 0, 'by_day' => collect()];

        $uzumLinks = $links->filter(fn ($link) => $link->marketplace_code === 'uzum');

        if ($uzumLinks->isEmpty()) {
            return $empty;
        }

        // Собираем external_offer_id и external_sku для сопоставления
        $externalOfferIds = $uzumLinks->pluck('external_offer_id')->filter()->unique()->values();
        $externalSkus = $uzumLinks->pluck('external_sku')->filter()->unique()->values();
        $matchIds = $externalOfferIds->merge($externalSkus)->unique()->values();

        if ($matchIds->isEmpty()) {
            return $empty;
        }

        // Получаем account_id для фильтрации
        $accountIds = $uzumLinks->pluck('marketplace_account_id')->unique();

        $stats = DB::table('uzum_order_items')
            ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
            ->whereIn('uzum_order_items.external_offer_id', $matchIds)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->selectRaw('SUM(uzum_order_items.quantity) as total_quantity, SUM(uzum_order_items.total_price) as total_revenue, COUNT(DISTINCT uzum_orders.id) as order_count')
            ->first();

        $byDay = DB::table('uzum_order_items')
            ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
            ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
            ->whereIn('uzum_order_items.external_offer_id', $matchIds)
            ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
            ->select(
                DB::raw('DATE(uzum_orders.ordered_at) as date'),
                DB::raw('SUM(uzum_order_items.quantity) as quantity')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return [
            'quantity' => (int) ($stats->total_quantity ?? 0),
            'revenue' => (float) ($stats->total_revenue ?? 0),
            'orders' => (int) ($stats->order_count ?? 0),
            'by_day' => $byDay,
        ];
    }

    /**
     * Производительность товара на Ozon.
     * Ozon хранит товары в JSON-поле products внутри ozon_orders.
     * Фильтрует по SKU/offer_id из связей.
     *
     * @return array{quantity: int, revenue: float, orders: int, by_day: Collection}
     */
    private function getOzonProductPerformance(Collection $links, array $dateRange): array
    {
        $empty = ['quantity' => 0, 'revenue' => 0.0, 'orders' => 0, 'by_day' => collect()];

        $ozonLinks = $links->filter(fn ($link) => $link->marketplace_code === 'ozon');

        if ($ozonLinks->isEmpty()) {
            return $empty;
        }

        // Собираем все возможные идентификаторы товара
        $externalSkus = $ozonLinks->pluck('external_sku')->filter()->unique()->values();
        $externalOfferIds = $ozonLinks->pluck('external_offer_id')->filter()->unique()->values();
        $accountIds = $ozonLinks->pluck('marketplace_account_id')->unique();

        if ($externalSkus->isEmpty() && $externalOfferIds->isEmpty()) {
            return $empty;
        }

        // Ozon не имеет таблицы order_items — товары хранятся в JSON поле products.
        // Загружаем заказы и фильтруем товары в PHP.
        $orders = DB::table('ozon_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', self::CANCELLED_STATUSES)
            ->select('id', 'products', 'order_data', 'created_at_ozon')
            ->get();

        $totalQuantity = 0;
        $totalRevenue = 0.0;
        $orderIds = [];
        /** @var array<string, int> $dailyQuantities */
        $dailyQuantities = [];

        foreach ($orders as $order) {
            $products = json_decode($order->products ?? '[]', true);

            // Если products пуст, пробуем order_data.products
            if (empty($products)) {
                $orderData = json_decode($order->order_data ?? '{}', true);
                $products = $orderData['products'] ?? [];
            }

            $orderQty = 0;
            $orderRevenue = 0.0;

            foreach ($products as $product) {
                $sku = (string) ($product['sku'] ?? '');
                $offerId = (string) ($product['offer_id'] ?? '');

                $matched = $externalSkus->contains($sku)
                    || $externalSkus->contains($offerId)
                    || $externalOfferIds->contains($sku)
                    || $externalOfferIds->contains($offerId);

                if ($matched) {
                    $qty = (int) ($product['quantity'] ?? 1);
                    $price = (float) ($product['price'] ?? 0);
                    $orderQty += $qty;
                    $orderRevenue += $price * $qty;
                }
            }

            if ($orderQty > 0) {
                $totalQuantity += $orderQty;
                $totalRevenue += $orderRevenue;
                $orderIds[] = $order->id;
                $date = date('Y-m-d', strtotime($order->created_at_ozon));
                $dailyQuantities[$date] = ($dailyQuantities[$date] ?? 0) + $orderQty;
            }
        }

        ksort($dailyQuantities);
        $byDay = collect($dailyQuantities)->map(fn ($qty, $date) => (object) [
            'date' => $date,
            'quantity' => $qty,
        ])->values();

        return [
            'quantity' => $totalQuantity,
            'revenue' => $totalRevenue,
            'orders' => count(array_unique($orderIds)),
            'by_day' => $byDay,
        ];
    }

    /**
     * Производительность товара на Yandex Market.
     * YM хранит товары в JSON-поле order_data (items).
     * Фильтрует по offerId/shopSku из связей.
     *
     * @return array{quantity: int, revenue: float, orders: int, by_day: Collection}
     */
    private function getYmProductPerformance(Collection $links, array $dateRange): array
    {
        $empty = ['quantity' => 0, 'revenue' => 0.0, 'orders' => 0, 'by_day' => collect()];

        $ymLinks = $links->filter(fn ($link) => in_array($link->marketplace_code, ['ym', 'yandex_market']));

        if ($ymLinks->isEmpty()) {
            return $empty;
        }

        $externalOfferIds = $ymLinks->pluck('external_offer_id')->filter()->unique()->values();
        $externalSkus = $ymLinks->pluck('external_sku')->filter()->unique()->values();
        $accountIds = $ymLinks->pluck('marketplace_account_id')->unique();

        if ($externalOfferIds->isEmpty() && $externalSkus->isEmpty()) {
            return $empty;
        }

        // YM не имеет отдельной таблицы items — товары в JSON поле order_data
        $orders = DB::table('yandex_market_orders')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->select('id', 'order_data', 'created_at_ym')
            ->get();

        $totalQuantity = 0;
        $totalRevenue = 0.0;
        $orderIds = [];
        /** @var array<string, int> $dailyQuantities */
        $dailyQuantities = [];

        foreach ($orders as $order) {
            $orderData = json_decode($order->order_data ?? '{}', true);
            $items = $orderData['items'] ?? [];
            $orderQty = 0;
            $orderRevenue = 0.0;

            foreach ($items as $item) {
                $offerId = (string) ($item['offerId'] ?? $item['offer_id'] ?? '');
                $shopSku = (string) ($item['shopSku'] ?? $item['shop_sku'] ?? '');

                $matched = $externalOfferIds->contains($offerId)
                    || $externalOfferIds->contains($shopSku)
                    || $externalSkus->contains($offerId)
                    || $externalSkus->contains($shopSku);

                if ($matched) {
                    $qty = (int) ($item['count'] ?? $item['quantity'] ?? 1);
                    $price = (float) ($item['price'] ?? $item['buyerPrice'] ?? 0);
                    $orderQty += $qty;
                    $orderRevenue += $price * $qty;
                }
            }

            if ($orderQty > 0) {
                $totalQuantity += $orderQty;
                $totalRevenue += $orderRevenue;
                $orderIds[] = $order->id;
                $date = date('Y-m-d', strtotime($order->created_at_ym));
                $dailyQuantities[$date] = ($dailyQuantities[$date] ?? 0) + $orderQty;
            }
        }

        ksort($dailyQuantities);
        $byDay = collect($dailyQuantities)->map(fn ($qty, $date) => (object) [
            'date' => $date,
            'quantity' => $qty,
        ])->values();

        return [
            'quantity' => $totalQuantity,
            'revenue' => $totalRevenue,
            'orders' => count(array_unique($orderIds)),
            'by_day' => $byDay,
        ];
    }

    /**
     * Объединить данные продаж по дням со всех маркетплейсов.
     * Суммирует количество за одинаковые даты, сортирует по дате.
     *
     * @return array<int, array{date: string, quantity: int}>
     */
    private function mergeSalesByDay(Collection ...$sources): array
    {
        /** @var array<string, int> $merged */
        $merged = [];

        foreach ($sources as $source) {
            foreach ($source as $item) {
                $date = $item->date;
                $merged[$date] = ($merged[$date] ?? 0) + (int) $item->quantity;
            }
        }

        ksort($merged);

        return collect($merged)->map(fn (int $quantity, string $date) => [
            'date' => $date,
            'quantity' => $quantity,
        ])->values()->toArray();
    }

    /**
     * Конвертировать сумму из валюты маркетплейса в отображаемую валюту.
     * Если сервис конвертации не настроен — возвращает исходную сумму (graceful fallback).
     */
    protected function convertToDisplay(float $amount, string $fromCurrency): float
    {
        if ($this->currencyService === null || $amount == 0.0) {
            return $amount;
        }

        return $this->currencyService->convertToDisplay($amount, $fromCurrency);
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
        $marketplaceSales = 0;

        if ($accountIds->isNotEmpty()) {
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

            // Ozon: SUM(items_count) для учёта нескольких позиций в одном заказе
            $ozonSales = (int) OzonOrder::whereIn('marketplace_account_id', $accountIds)
                ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
                ->whereNotIn('status', self::CANCELLED_STATUSES)
                ->sum(DB::raw('COALESCE(items_count, 1)'));

            // Yandex Market: SUM(items_count) для учёта нескольких позиций в одном заказе
            $ymSales = (int) YandexMarketOrder::whereIn('marketplace_account_id', $accountIds)
                ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
                ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
                ->sum(DB::raw('COALESCE(items_count, 1)'));

            $marketplaceSales = (int) ($uzumSales + $wbSales + $ozonSales + $ymSales);
        }

        // Ручные продажи: SUM(sale_items.quantity) через JOIN (количество товаров, не заказов)
        $manualSales = (int) DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.company_id', $this->companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->sum('sale_items.quantity');

        // Оффлайн продажи: SUM(offline_sale_items.quantity) через JOIN, исключая отменённые и возвращённые
        $offlineSales = (int) DB::table('offline_sale_items')
            ->join('offline_sales', 'offline_sale_items.offline_sale_id', '=', 'offline_sales.id')
            ->where('offline_sales.company_id', $this->companyId)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('offline_sales.status', ['cancelled', 'returned'])
            ->whereNull('offline_sales.deleted_at')
            ->sum('offline_sale_items.quantity');

        return $marketplaceSales + $manualSales + $offlineSales;
    }

    /**
     * Получить выручку и количество заказов за период одним запросом.
     *
     * @param  \Illuminate\Support\Collection<int, int>  $accountIds
     * @return array{revenue: float, orders: int}
     */
    protected function getRevenueAndOrders(Collection $accountIds, array $dateRange): array
    {
        $marketplaceRevenue = 0.0;
        $marketplaceOrders = 0;

        if ($accountIds->isNotEmpty()) {
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

            $marketplaceRevenue =
                $this->convertToDisplay((float) ($wbRow->revenue ?? 0), 'RUB') +
                $this->convertToDisplay((float) ($ozonRow->revenue ?? 0), 'RUB') +
                $this->convertToDisplay((float) ($ymRow->revenue ?? 0), 'RUB') +
                $this->convertToDisplay((float) ($uzumRow->revenue ?? 0), 'UZS');
            $marketplaceOrders = (int) (
                ($uzumRow->orders ?? 0) +
                ($wbRow->orders ?? 0) +
                ($ozonRow->orders ?? 0) +
                ($ymRow->orders ?? 0)
            );
        }

        // Ручные продажи: выручка и количество заказов (все кроме отменённых)
        $manualRow = DB::table('sales')
            ->where('company_id', $this->companyId)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->selectRaw('SUM(total_amount) as revenue, COUNT(*) as orders')
            ->first();

        // Оффлайн продажи: выручка и количество заказов (исключая отменённые и возвращённые)
        $offlineRow = DB::table('offline_sales')
            ->where('company_id', $this->companyId)
            ->whereBetween('sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereNotIn('status', ['cancelled', 'returned'])
            ->whereNull('deleted_at')
            ->selectRaw('SUM(total_amount) as revenue, COUNT(*) as orders')
            ->first();

        return [
            'revenue' => $marketplaceRevenue +
                (float) ($manualRow->revenue ?? 0) +
                (float) ($offlineRow->revenue ?? 0),
            'orders' => $marketplaceOrders +
                (int) ($manualRow->orders ?? 0) +
                (int) ($offlineRow->orders ?? 0),
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
        $marketplaceCount = 0;
        $marketplaceAmount = 0.0;

        if ($accountIds->isNotEmpty()) {
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

            $marketplaceCount = (int) (
                ($uzumRow->cnt ?? 0) +
                ($wbRow->cnt ?? 0) +
                ($ozonRow->cnt ?? 0) +
                ($ymRow->cnt ?? 0)
            );
            $marketplaceAmount =
                $this->convertToDisplay((float) ($wbRow->amount ?? 0), 'RUB') +
                $this->convertToDisplay((float) ($ozonRow->amount ?? 0), 'RUB') +
                $this->convertToDisplay((float) ($ymRow->amount ?? 0), 'RUB') +
                $this->convertToDisplay((float) ($uzumRow->amount ?? 0), 'UZS');
        }

        // Отменённые ручные продажи
        $manualRow = DB::table('sales')
            ->where('company_id', $this->companyId)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->where('status', 'cancelled')
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
            ->first();

        // Отменённые и возвращённые оффлайн продажи
        $offlineRow = DB::table('offline_sales')
            ->where('company_id', $this->companyId)
            ->whereBetween('sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('status', ['cancelled', 'returned'])
            ->whereNull('deleted_at')
            ->selectRaw('COUNT(*) as cnt, SUM(total_amount) as amount')
            ->first();

        return [
            'count' => $marketplaceCount +
                (int) ($manualRow->cnt ?? 0) +
                (int) ($offlineRow->cnt ?? 0),
            'amount' => $marketplaceAmount +
                (float) ($manualRow->amount ?? 0) +
                (float) ($offlineRow->amount ?? 0),
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
                'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
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
                'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'UZS'),
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
                'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
                'order_count' => $rows->unique('order_id')->count(),
            ]);

        // Ручные продажи: группировка по SKU или названию товара
        $manualItems = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.company_id', $this->companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->select(
                DB::raw('COALESCE(sale_items.sku, sale_items.product_name) as external_offer_id'),
                'sale_items.product_name as name',
                'sale_items.quantity',
                'sale_items.total as total_price',
                'sales.id as order_id'
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

        // Оффлайн продажи: группировка по SKU или названию товара
        $offlineItems = DB::table('offline_sale_items')
            ->join('offline_sales', 'offline_sale_items.offline_sale_id', '=', 'offline_sales.id')
            ->where('offline_sales.company_id', $this->companyId)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('offline_sales.status', ['confirmed', 'delivered'])
            ->whereNull('offline_sales.deleted_at')
            ->select(
                DB::raw('COALESCE(offline_sale_items.sku_code, offline_sale_items.product_name) as external_offer_id'),
                'offline_sale_items.product_name as name',
                'offline_sale_items.quantity',
                'offline_sale_items.line_total as total_price',
                'offline_sales.id as order_id'
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

        foreach ([$wbItems, $uzumItems, $wbFbsItems, $manualItems, $offlineItems] as $items) {
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
            ->selectRaw('COUNT(*) as orders, SUM(total_price) as revenue, SUM(COALESCE(items_count, 1)) as quantity')
            ->first();

        // TODO: Добавить таблицу ozon_order_items для точного подсчёта quantity.
        // Сейчас используем количество заказов как приближение — у Ozon в поле products
        // хранится JSON-массив товаров, но агрегация по JSON в SQL нетривиальна.
        // Когда появится таблица ozon_order_items — заменить на:
        // DB::table('ozon_orders')
        //     ->leftJoin('ozon_order_items', 'ozon_orders.id', '=', 'ozon_order_items.ozon_order_id')
        //     ->selectRaw('COUNT(DISTINCT ozon_orders.id) as orders, COALESCE(SUM(ozon_order_items.quantity), COUNT(DISTINCT ozon_orders.id)) as total_quantity, SUM(ozon_orders.total_price) as revenue')
        return [
            'quantity' => (int) ($row->quantity ?? 0),
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
            ->selectRaw('COUNT(*) as orders, SUM(total_price) as revenue, SUM(COALESCE(items_count, 1)) as quantity')
            ->first();

        // TODO: Добавить таблицу yandex_market_order_items для точного подсчёта quantity.
        // Сейчас используем поле items_count из yandex_market_orders (сумма товаров в заказе).
        // Когда появится таблица yandex_market_order_items — заменить на:
        // DB::table('yandex_market_orders')
        //     ->leftJoin('yandex_market_order_items', 'yandex_market_orders.id', '=', 'yandex_market_order_items.yandex_market_order_id')
        //     ->selectRaw('COUNT(DISTINCT yandex_market_orders.id) as orders, COALESCE(SUM(yandex_market_order_items.quantity), SUM(yandex_market_orders.items_count)) as total_quantity, SUM(yandex_market_orders.total_price) as revenue')
        return [
            'quantity' => (int) ($row->quantity ?? 0),
            'revenue' => (float) ($row->revenue ?? 0),
            'orders' => (int) ($row->orders ?? 0),
        ];
    }
}
