<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Company;
use App\Models\MarketplaceAccount;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class BusinessAnalyticsService
{
    /**
     * Статусы отменённых заказов
     */
    private const CANCELLED_STATUSES = [
        'cancelled', 'canceled', 'returned',
        'CANCELLED', 'CANCELED', 'RETURNED',
        'PENDING_CANCELLATION',
    ];

    private int $companyId = 0;

    public function __construct(
        private readonly ?CurrencyConversionService $currencyService = null,
    ) {}

    /**
     * Установить контекст компании
     */
    public function forCompany(int $companyId): self
    {
        $this->companyId = $companyId;
        $company = Company::find($companyId);
        if ($company && $this->currencyService !== null) {
            $this->currencyService->forCompany($company);
        }

        return $this;
    }

    /**
     * ABC-анализ товаров по выручке.
     * A = 20% ассортимента → ~80% продаж
     * B = 30% ассортимента → ~15% продаж
     * C = 50% ассортимента → ~5% продаж
     */
    public function getAbcAnalysis(int $companyId, string $period = '30days'): array
    {
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        $items = $this->getAggregatedProductRevenue($accountIds, $dateRange);

        if ($items->isEmpty()) {
            return [
                'summary' => [
                    'total_products' => 0,
                    'total_revenue' => 0,
                    'categories' => [
                        'A' => ['count' => 0, 'revenue' => 0, 'percentage' => 0, 'assortment_percentage' => 20],
                        'B' => ['count' => 0, 'revenue' => 0, 'percentage' => 0, 'assortment_percentage' => 30],
                        'C' => ['count' => 0, 'revenue' => 0, 'percentage' => 0, 'assortment_percentage' => 50],
                    ],
                ],
                'products' => [],
                'period' => $period,
            ];
        }

        // Сортируем по выручке (убывание) и считаем кумулятивный процент
        $sorted = $items->sortByDesc('total_revenue')->values();
        $totalRevenue = $sorted->sum('total_revenue');
        $totalProducts = $sorted->count();

        $cumulativeRevenue = 0;
        $products = [];
        $categories = ['A' => ['count' => 0, 'revenue' => 0], 'B' => ['count' => 0, 'revenue' => 0], 'C' => ['count' => 0, 'revenue' => 0]];

        foreach ($sorted as $index => $item) {
            $cumulativeRevenue += $item['total_revenue'];
            $cumulativePercentage = $totalRevenue > 0 ? ($cumulativeRevenue / $totalRevenue) * 100 : 0;

            // Определяем категорию по кумулятивному проценту выручки
            $productPosition = ($index + 1) / $totalProducts * 100;
            if ($productPosition <= 20) {
                $category = 'A';
            } elseif ($productPosition <= 50) {
                $category = 'B';
            } else {
                $category = 'C';
            }

            $categories[$category]['count']++;
            $categories[$category]['revenue'] += $item['total_revenue'];

            $products[] = [
                'product_name' => $item['name'],
                'sku' => $item['external_offer_id'],
                'revenue' => round($item['total_revenue'], 2),
                'quantity' => $item['total_quantity'],
                'category' => $category,
                'cumulative_percentage' => round($cumulativePercentage, 2),
            ];
        }

        // Подсчитываем процент выручки для каждой категории
        foreach ($categories as $key => &$cat) {
            $cat['percentage'] = $totalRevenue > 0 ? round(($cat['revenue'] / $totalRevenue) * 100, 1) : 0;
            $cat['revenue'] = round($cat['revenue'], 2);
            $cat['assortment_percentage'] = match ($key) {
                'A' => round(($cat['count'] / max($totalProducts, 1)) * 100, 1),
                'B' => round(($cat['count'] / max($totalProducts, 1)) * 100, 1),
                'C' => round(($cat['count'] / max($totalProducts, 1)) * 100, 1),
            };
        }

        return [
            'summary' => [
                'total_products' => $totalProducts,
                'total_revenue' => round($totalRevenue, 2),
                'categories' => $categories,
            ],
            'products' => $products,
            'period' => $period,
        ];
    }

    /**
     * ABCXYZ-анализ клиентов.
     * ABC — по сумме покупок: A >= $10 000, B >= $5 000, C < $5 000
     * XYZ — по частоте: X = ежедневно (>=5/нед), Y = еженедельно (1-4/нед), Z = ежемесячно (<1/нед)
     */
    public function getAbcxyzAnalysis(int $companyId, string $period = '90days'): array
    {
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');

        $customers = $this->getCustomerData($accountIds, $dateRange);

        $totalWeeks = max(1, $dateRange['from']->diffInWeeks($dateRange['to']));

        // Инициализация матрицы
        $matrix = [];
        $segments = ['AX', 'AY', 'AZ', 'BX', 'BY', 'BZ', 'CX', 'CY', 'CZ'];
        foreach ($segments as $seg) {
            $matrix[$seg] = ['count' => 0, 'revenue' => 0, 'customers' => []];
        }

        $thresholds = [
            'A' => 10000,
            'B' => 5000,
            'C' => 0,
        ];

        foreach ($customers as $customer) {
            $totalAmount = (float) $customer['total_amount'];
            $orderCount = (int) $customer['order_count'];
            $ordersPerWeek = $orderCount / $totalWeeks;

            // ABC классификация по сумме
            if ($totalAmount >= $thresholds['A']) {
                $abc = 'A';
            } elseif ($totalAmount >= $thresholds['B']) {
                $abc = 'B';
            } else {
                $abc = 'C';
            }

            // XYZ классификация по частоте
            if ($ordersPerWeek >= 5) {
                $xyz = 'X';
            } elseif ($ordersPerWeek >= 1) {
                $xyz = 'Y';
            } else {
                $xyz = 'Z';
            }

            $segment = $abc . $xyz;
            $matrix[$segment]['count']++;
            $matrix[$segment]['revenue'] += $totalAmount;
            if (count($matrix[$segment]['customers']) < 5) {
                $matrix[$segment]['customers'][] = [
                    'name' => $customer['customer_name'],
                    'total_amount' => round($totalAmount, 2),
                    'order_count' => $orderCount,
                ];
            }
        }

        // Округляем выручку
        foreach ($matrix as &$seg) {
            $seg['revenue'] = round($seg['revenue'], 2);
        }

        return [
            'matrix' => $matrix,
            'summary' => [
                'total_customers' => $customers->count(),
                'total_revenue' => round($customers->sum('total_amount'), 2),
                'period_weeks' => $totalWeeks,
            ],
            'thresholds' => $thresholds,
            'period' => $period,
        ];
    }

    /**
     * Получить SWOT-анализ компании
     */
    public function getSwotAnalysis(int $companyId): array
    {
        $cacheKey = "swot_analysis_{$companyId}";

        $data = Cache::get($cacheKey);

        if (!$data) {
            // Попробовать загрузить из файла
            $filePath = "swot/company_{$companyId}.json";
            if (Storage::disk('local')->exists($filePath)) {
                $data = json_decode(Storage::disk('local')->get($filePath), true);
            }
        }

        return $data ?? [
            'strengths' => [],
            'weaknesses' => [],
            'opportunities' => [],
            'threats' => [],
            'updated_at' => null,
        ];
    }

    /**
     * Сохранить SWOT-анализ компании
     */
    public function saveSwotAnalysis(int $companyId, array $data): array
    {
        $swot = [
            'strengths' => $data['strengths'] ?? [],
            'weaknesses' => $data['weaknesses'] ?? [],
            'opportunities' => $data['opportunities'] ?? [],
            'threats' => $data['threats'] ?? [],
            'updated_at' => now()->toIso8601String(),
        ];

        $cacheKey = "swot_analysis_{$companyId}";
        Cache::forever($cacheKey, $swot);

        // Сохранить в файл для надёжности
        $filePath = "swot/company_{$companyId}.json";
        Storage::disk('local')->put($filePath, json_encode($swot, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

        return $swot;
    }

    /**
     * Получить агрегированные данные по выручке товаров из всех источников
     */
    protected function getAggregatedProductRevenue(Collection $accountIds, array $dateRange): Collection
    {
        $allItems = collect();

        if ($accountIds->isNotEmpty()) {
            // WB Statistics API
            $wbItems = DB::table('wildberries_orders')
                ->whereIn('marketplace_account_id', $accountIds)
                ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
                ->where('is_cancel', false)
                ->where('is_return', false)
                ->select(
                    DB::raw('COALESCE(supplier_article, CAST(nm_id AS CHAR)) as external_offer_id'),
                    DB::raw('COALESCE(subject, supplier_article, CAST(nm_id AS CHAR)) as name'),
                    DB::raw('1 as quantity'),
                    DB::raw('for_pay as total_price')
                )
                ->get()
                ->groupBy('external_offer_id')
                ->map(fn ($rows) => [
                    'external_offer_id' => $rows->first()->external_offer_id,
                    'name' => $rows->first()->name,
                    'total_quantity' => $rows->count(),
                    'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
                ]);

            // Uzum
            $uzumItems = DB::table('uzum_order_items')
                ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
                ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
                ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
                ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
                ->select(
                    'uzum_order_items.external_offer_id',
                    'uzum_order_items.name',
                    'uzum_order_items.quantity',
                    'uzum_order_items.total_price'
                )
                ->get()
                ->groupBy('external_offer_id')
                ->map(fn ($rows) => [
                    'external_offer_id' => $rows->first()->external_offer_id,
                    'name' => $rows->first()->name ?? $rows->first()->external_offer_id,
                    'total_quantity' => (int) $rows->sum('quantity'),
                    'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'UZS'),
                ]);

            // Ozon
            $ozonItems = DB::table('ozon_orders')
                ->whereIn('marketplace_account_id', $accountIds)
                ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
                ->whereNotIn('status', self::CANCELLED_STATUSES)
                ->select(
                    DB::raw('COALESCE(offer_id, CAST(ozon_order_id AS CHAR)) as external_offer_id'),
                    DB::raw('COALESCE(product_name, offer_id) as name'),
                    DB::raw('COALESCE(items_count, 1) as quantity'),
                    DB::raw('total_price')
                )
                ->get()
                ->groupBy('external_offer_id')
                ->map(fn ($rows) => [
                    'external_offer_id' => $rows->first()->external_offer_id,
                    'name' => $rows->first()->name ?? $rows->first()->external_offer_id,
                    'total_quantity' => (int) $rows->sum('quantity'),
                    'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
                ]);

            // Yandex Market
            $ymItems = DB::table('yandex_market_orders')
                ->whereIn('marketplace_account_id', $accountIds)
                ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
                ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
                ->select(
                    DB::raw('COALESCE(offer_id, CAST(ym_order_id AS CHAR)) as external_offer_id'),
                    DB::raw('COALESCE(product_name, offer_id) as name'),
                    DB::raw('COALESCE(items_count, 1) as quantity'),
                    DB::raw('total_price')
                )
                ->get()
                ->groupBy('external_offer_id')
                ->map(fn ($rows) => [
                    'external_offer_id' => $rows->first()->external_offer_id,
                    'name' => $rows->first()->name ?? $rows->first()->external_offer_id,
                    'total_quantity' => (int) $rows->sum('quantity'),
                    'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
                ]);

            $allItems = $allItems->merge($wbItems)->merge($uzumItems)->merge($ozonItems)->merge($ymItems);
        }

        // Ручные продажи
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
                'sale_items.total as total_price'
            )
            ->get()
            ->groupBy('external_offer_id')
            ->map(fn ($rows) => [
                'external_offer_id' => $rows->first()->external_offer_id,
                'name' => $rows->first()->name ?? $rows->first()->external_offer_id,
                'total_quantity' => (int) $rows->sum('quantity'),
                'total_revenue' => (float) $rows->sum('total_price'),
            ]);

        // Оффлайн продажи
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
                'offline_sale_items.line_total as total_price'
            )
            ->get()
            ->groupBy('external_offer_id')
            ->map(fn ($rows) => [
                'external_offer_id' => $rows->first()->external_offer_id,
                'name' => $rows->first()->name ?? $rows->first()->external_offer_id,
                'total_quantity' => (int) $rows->sum('quantity'),
                'total_revenue' => (float) $rows->sum('total_price'),
            ]);

        $allItems = $allItems->merge($manualItems)->merge($offlineItems);

        // Объединяем дубликаты по external_offer_id
        return $allItems->groupBy('external_offer_id')->map(function ($group) {
            $first = $group->first();
            return [
                'external_offer_id' => $first['external_offer_id'],
                'name' => $first['name'],
                'total_quantity' => $group->sum('total_quantity'),
                'total_revenue' => $group->sum('total_revenue'),
            ];
        })->values();
    }

    /**
     * Получить данные о клиентах для ABCXYZ-анализа
     */
    protected function getCustomerData(Collection $accountIds, array $dateRange): Collection
    {
        $customers = collect();

        // Ручные продажи — основной источник данных по клиентам
        $manualCustomers = DB::table('sales')
            ->where('company_id', $this->companyId)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->where('status', '!=', 'cancelled')
            ->whereNull('deleted_at')
            ->whereNotNull('customer_name')
            ->select(
                DB::raw('COALESCE(customer_name, customer_phone, "Неизвестный") as customer_name'),
                DB::raw('SUM(total) as total_amount'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy('customer_name')
            ->get()
            ->map(fn ($row) => [
                'customer_name' => $row->customer_name,
                'total_amount' => (float) $row->total_amount,
                'order_count' => (int) $row->order_count,
            ]);

        $customers = $customers->merge($manualCustomers);

        // Оффлайн продажи
        $offlineCustomers = DB::table('offline_sales')
            ->where('company_id', $this->companyId)
            ->whereBetween('sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->whereNotNull('customer_name')
            ->select(
                DB::raw('COALESCE(customer_name, customer_phone, "Неизвестный") as customer_name'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy('customer_name')
            ->get()
            ->map(fn ($row) => [
                'customer_name' => $row->customer_name,
                'total_amount' => (float) $row->total_amount,
                'order_count' => (int) $row->order_count,
            ]);

        $customers = $customers->merge($offlineCustomers);

        // Объединяем одинаковых клиентов
        return $customers->groupBy('customer_name')->map(function ($group) {
            return [
                'customer_name' => $group->first()['customer_name'],
                'total_amount' => $group->sum('total_amount'),
                'order_count' => $group->sum('order_count'),
            ];
        })->values();
    }

    /**
     * Конвертировать сумму в валюту отображения
     */
    protected function convertToDisplay(float $amount, string $currency): float
    {
        if ($this->currencyService === null) {
            return $amount;
        }

        return $this->currencyService->convertToDisplay($amount, $currency);
    }

    /**
     * Получить диапазон дат по периоду
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
}
