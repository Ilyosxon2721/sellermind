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
    public function getAbcAnalysis(int $companyId, string $period = '30days', string $source = 'all'): array
    {
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = $this->getFilteredAccountIds($companyId, $source);

        $items = $this->getAggregatedProductRevenue($accountIds, $dateRange, $source);

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

            // Определяем категорию по кумулятивному проценту выручки (стандартный ABC)
            // A = товары, дающие первые 80% выручки
            // B = товары, дающие следующие 15% (80-95%)
            // C = товары, дающие оставшиеся 5% (95-100%)
            if ($cumulativePercentage <= 80) {
                $category = 'A';
            } elseif ($cumulativePercentage <= 95) {
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
    public function getAbcxyzAnalysis(int $companyId, string $period = '90days', string $source = 'all'): array
    {
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = $this->getFilteredAccountIds($companyId, $source);

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
     * Получить отфильтрованные аккаунты маркетплейсов
     */
    protected function getFilteredAccountIds(int $companyId, string $source): Collection
    {
        $query = MarketplaceAccount::where('company_id', $companyId);

        // Фильтр по конкретному маркетплейсу
        if (in_array($source, ['wb', 'ozon', 'uzum', 'ym'])) {
            $query->where('marketplace', $source);
        }

        return $query->pluck('id');
    }

    /**
     * Проверить нужно ли включать данный источник
     */
    protected function shouldIncludeSource(string $source, string $type): bool
    {
        if ($source === 'all') {
            return true;
        }

        return $source === $type;
    }

    /**
     * Получить агрегированные данные по выручке товаров из всех источников
     */
    protected function getAggregatedProductRevenue(Collection $accountIds, array $dateRange, string $source = 'all'): Collection
    {
        $allItems = collect();

        if ($accountIds->isNotEmpty()) {
            // WB
            if ($this->shouldIncludeSource($source, 'wb')) {
                $wbItems = DB::table('wildberries_orders')
                    ->whereIn('marketplace_account_id', $accountIds)
                    ->whereBetween('order_date', [$dateRange['from'], $dateRange['to']])
                    ->where('is_cancel', false)
                    ->where('is_return', false)
                    ->select(
                        DB::raw('COALESCE(supplier_article, CAST(nm_id AS CHAR)) as external_offer_id'),
                        DB::raw('COALESCE(subject, supplier_article, CAST(nm_id AS CHAR)) as name'),
                        DB::raw('1 as quantity'),
                        DB::raw('COALESCE(for_pay, finished_price, total_price) as total_price')
                    )
                    ->get()
                    ->groupBy('external_offer_id')
                    ->map(fn ($rows) => [
                        'external_offer_id' => $rows->first()->external_offer_id,
                        'name' => $rows->first()->name,
                        'total_quantity' => $rows->count(),
                        'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
                    ]);
                $allItems = $allItems->merge($wbItems);
            }

            // Uzum
            if ($this->shouldIncludeSource($source, 'uzum')) {
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
                $allItems = $allItems->merge($uzumItems);
            }

            // Ozon — извлекаем товары из JSON поля products
            if ($this->shouldIncludeSource($source, 'ozon')) {
                $ozonOrders = DB::table('ozon_orders')
                    ->whereIn('marketplace_account_id', $accountIds)
                    ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
                    ->whereNotIn('status', self::CANCELLED_STATUSES)
                    ->select('products', 'order_data')
                    ->get();

                $ozonProductItems = collect();
                foreach ($ozonOrders as $order) {
                    $products = !empty($order->products) ? json_decode($order->products, true) : [];
                    if (empty($products) && !empty($order->order_data)) {
                        $orderData = json_decode($order->order_data, true);
                        $products = $orderData['products'] ?? [];
                    }
                    foreach ($products as $product) {
                        $sku = $product['sku'] ?? $product['offer_id'] ?? null;
                        if (!$sku) {
                            continue;
                        }
                        $ozonProductItems->push([
                            'external_offer_id' => (string) $sku,
                            'name' => $product['name'] ?? $sku,
                            'quantity' => (int) ($product['quantity'] ?? 1),
                            'total_price' => (float) ($product['total'] ?? (($product['price'] ?? 0) * ($product['quantity'] ?? 1))),
                        ]);
                    }
                }

                $ozonItems = $ozonProductItems
                    ->groupBy('external_offer_id')
                    ->map(fn ($rows) => [
                        'external_offer_id' => $rows->first()['external_offer_id'],
                        'name' => $rows->first()['name'],
                        'total_quantity' => (int) $rows->sum('quantity'),
                        'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
                    ]);
                $allItems = $allItems->merge($ozonItems);
            }

            // Yandex Market — извлекаем товары из JSON поля order_data.items
            if ($this->shouldIncludeSource($source, 'ym')) {
                $ymOrders = DB::table('yandex_market_orders')
                    ->whereIn('marketplace_account_id', $accountIds)
                    ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
                    ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
                    ->select('order_data')
                    ->get();

                $ymProductItems = collect();
                foreach ($ymOrders as $order) {
                    $orderData = !empty($order->order_data) ? json_decode($order->order_data, true) : [];
                    $items = $orderData['items'] ?? [];
                    foreach ($items as $item) {
                        $offerId = (string) ($item['offerId'] ?? $item['external_offer_id'] ?? '');
                        if (empty($offerId)) {
                            continue;
                        }
                        $price = (float) ($item['buyerPrice'] ?? $item['price'] ?? 0);
                        $count = (int) ($item['count'] ?? $item['quantity'] ?? 1);
                        $ymProductItems->push([
                            'external_offer_id' => $offerId,
                            'name' => $item['offerName'] ?? $item['name'] ?? $offerId,
                            'quantity' => $count,
                            'total_price' => $price * $count,
                        ]);
                    }
                }

                $ymItems = $ymProductItems
                    ->groupBy('external_offer_id')
                    ->map(fn ($rows) => [
                        'external_offer_id' => $rows->first()['external_offer_id'],
                        'name' => $rows->first()['name'],
                        'total_quantity' => (int) $rows->sum('quantity'),
                        'total_revenue' => $this->convertToDisplay((float) $rows->sum('total_price'), 'RUB'),
                    ]);
                $allItems = $allItems->merge($ymItems);
            }
        }

        // Ручные продажи
        if ($this->shouldIncludeSource($source, 'manual')) {
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
            $allItems = $allItems->merge($manualItems);
        }

        // Оффлайн продажи
        if ($this->shouldIncludeSource($source, 'offline')) {
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
            $allItems = $allItems->merge($offlineItems);
        }

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

        // Ручные продажи — через контрагентов (counterparty_id → counterparties.name)
        $manualCustomers = DB::table('sales')
            ->join('counterparties', 'sales.counterparty_id', '=', 'counterparties.id')
            ->where('sales.company_id', $this->companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->select(
                'counterparties.name as customer_name',
                DB::raw('SUM(sales.total_amount) as total_amount'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy('counterparties.name')
            ->get()
            ->map(fn ($row) => [
                'customer_name' => $row->customer_name,
                'total_amount' => (float) $row->total_amount,
                'order_count' => (int) $row->order_count,
            ]);

        $customers = $customers->merge($manualCustomers);

        // Оффлайн продажи — через контрагентов или customer_name
        $offlineByCounterparty = DB::table('offline_sales')
            ->join('counterparties', 'offline_sales.counterparty_id', '=', 'counterparties.id')
            ->where('offline_sales.company_id', $this->companyId)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('offline_sales.status', ['confirmed', 'delivered'])
            ->whereNull('offline_sales.deleted_at')
            ->select(
                'counterparties.name as customer_name',
                DB::raw('SUM(offline_sales.total_amount) as total_amount'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy('counterparties.name')
            ->get()
            ->map(fn ($row) => [
                'customer_name' => $row->customer_name,
                'total_amount' => (float) $row->total_amount,
                'order_count' => (int) $row->order_count,
            ]);

        $customers = $customers->merge($offlineByCounterparty);

        // Оффлайн продажи без контрагента — по customer_name
        $offlineByName = DB::table('offline_sales')
            ->where('company_id', $this->companyId)
            ->whereBetween('sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('status', ['confirmed', 'delivered'])
            ->whereNull('deleted_at')
            ->whereNull('counterparty_id')
            ->whereNotNull('customer_name')
            ->select(
                DB::raw('COALESCE(customer_name, customer_phone, "Неизвестный") as customer_name'),
                DB::raw('SUM(total_amount) as total_amount'),
                DB::raw('COUNT(*) as order_count')
            )
            ->groupBy(DB::raw('COALESCE(customer_name, customer_phone, "Неизвестный")'))
            ->get()
            ->map(fn ($row) => [
                'customer_name' => $row->customer_name,
                'total_amount' => (float) $row->total_amount,
                'order_count' => (int) $row->order_count,
            ]);

        $customers = $customers->merge($offlineByName);

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
     * Рейтинг товаров по количеству продаж
     */
    public function getProductSalesRanking(int $companyId, string $period = '30days', string $source = 'all'): array
    {
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = $this->getFilteredAccountIds($companyId, $source);

        $items = $this->getAggregatedProductRevenue($accountIds, $dateRange, $source);

        if ($items->isEmpty()) {
            return [
                'products' => [],
                'summary' => [
                    'total_products' => 0,
                    'total_quantity' => 0,
                    'total_revenue' => 0,
                    'avg_items_per_product' => 0,
                ],
                'period' => $period,
            ];
        }

        $sorted = $items->sortByDesc('total_quantity')->values();
        $totalQuantity = $sorted->sum('total_quantity');
        $totalRevenue = $sorted->sum('total_revenue');

        $products = [];
        foreach ($sorted as $index => $item) {
            $qty = (int) $item['total_quantity'];
            $rev = (float) $item['total_revenue'];
            $products[] = [
                'rank' => $index + 1,
                'product_name' => $item['name'],
                'sku' => $item['external_offer_id'],
                'quantity' => $qty,
                'revenue' => round($rev, 2),
                'avg_price' => $qty > 0 ? round($rev / $qty, 2) : 0,
                'share_percent' => $totalQuantity > 0 ? round(($qty / $totalQuantity) * 100, 2) : 0,
            ];
        }

        return [
            'products' => $products,
            'summary' => [
                'total_products' => count($products),
                'total_quantity' => $totalQuantity,
                'total_revenue' => round($totalRevenue, 2),
                'avg_items_per_product' => count($products) > 0 ? round($totalQuantity / count($products), 1) : 0,
            ],
            'period' => $period,
        ];
    }

    /**
     * Рейтинг товаров по маржинальности
     */
    public function getProductMarginRanking(int $companyId, string $period = '30days', string $source = 'all'): array
    {
        $this->forCompany($companyId);
        $dateRange = $this->getDateRange($period);
        $accountIds = $this->getFilteredAccountIds($companyId, $source);

        $allItems = collect();

        if ($accountIds->isNotEmpty()) {
            // WB
            if ($this->shouldIncludeSource($source, 'wb')) {
                $wbItems = DB::table('wildberries_orders')
                    ->leftJoin('marketplace_products', function ($join) {
                        $join->on('wildberries_orders.marketplace_account_id', '=', 'marketplace_products.marketplace_account_id')
                            ->on(DB::raw('COALESCE(wildberries_orders.supplier_article, CAST(wildberries_orders.nm_id AS CHAR))'), '=', 'marketplace_products.external_offer_id');
                    })
                    ->whereIn('wildberries_orders.marketplace_account_id', $accountIds)
                    ->whereBetween('wildberries_orders.order_date', [$dateRange['from'], $dateRange['to']])
                    ->where('wildberries_orders.is_cancel', false)
                    ->where('wildberries_orders.is_return', false)
                    ->select(
                        DB::raw('COALESCE(wildberries_orders.supplier_article, CAST(wildberries_orders.nm_id AS CHAR)) as sku'),
                        DB::raw('COALESCE(wildberries_orders.subject, wildberries_orders.supplier_article, CAST(wildberries_orders.nm_id AS CHAR)) as name'),
                        DB::raw('1 as quantity'),
                        DB::raw('COALESCE(wildberries_orders.for_pay, wildberries_orders.finished_price, wildberries_orders.total_price) as revenue'),
                        DB::raw('marketplace_products.purchase_price as cost_price'),
                        DB::raw("'RUB' as currency")
                    )
                    ->get();

                foreach ($wbItems->groupBy('sku') as $sku => $rows) {
                    $costPrice = $rows->first()->cost_price;
                    $allItems->push([
                        'sku' => $sku,
                        'name' => $rows->first()->name,
                        'quantity' => $rows->count(),
                        'revenue' => $this->convertToDisplay((float) $rows->sum('revenue'), 'RUB'),
                        'cost' => $costPrice ? $this->convertToDisplay((float) $costPrice * $rows->count(), 'RUB') : null,
                        'has_cost' => $costPrice !== null,
                    ]);
                }
            }

            // Uzum
            if ($this->shouldIncludeSource($source, 'uzum')) {
                $uzumItems = DB::table('uzum_order_items')
                    ->join('uzum_orders', 'uzum_order_items.uzum_order_id', '=', 'uzum_orders.id')
                    ->leftJoin('marketplace_products', function ($join) {
                        $join->on('uzum_orders.marketplace_account_id', '=', 'marketplace_products.marketplace_account_id')
                            ->on('uzum_order_items.external_offer_id', '=', 'marketplace_products.external_offer_id');
                    })
                    ->whereIn('uzum_orders.marketplace_account_id', $accountIds)
                    ->whereBetween('uzum_orders.ordered_at', [$dateRange['from'], $dateRange['to']])
                    ->whereNotIn('uzum_orders.status_normalized', self::CANCELLED_STATUSES)
                    ->select(
                        'uzum_order_items.external_offer_id as sku',
                        'uzum_order_items.name',
                        'uzum_order_items.quantity',
                        'uzum_order_items.total_price as revenue',
                        'marketplace_products.purchase_price as cost_price'
                    )
                    ->get();

                foreach ($uzumItems->groupBy('sku') as $sku => $rows) {
                    $costPrice = $rows->first()->cost_price;
                    $totalQty = (int) $rows->sum('quantity');
                    $allItems->push([
                        'sku' => $sku,
                        'name' => $rows->first()->name ?? $sku,
                        'quantity' => $totalQty,
                        'revenue' => $this->convertToDisplay((float) $rows->sum('revenue'), 'UZS'),
                        'cost' => $costPrice ? $this->convertToDisplay((float) $costPrice * $totalQty, 'UZS') : null,
                        'has_cost' => $costPrice !== null,
                    ]);
                }
            }

            // Ozon — извлекаем товары из JSON поля products
            if ($this->shouldIncludeSource($source, 'ozon')) {
                $ozonOrders = DB::table('ozon_orders')
                    ->whereIn('marketplace_account_id', $accountIds)
                    ->whereBetween('created_at_ozon', [$dateRange['from'], $dateRange['to']])
                    ->whereNotIn('status', self::CANCELLED_STATUSES)
                    ->select('products', 'order_data')
                    ->get();

                $ozonProductItems = collect();
                foreach ($ozonOrders as $order) {
                    $products = !empty($order->products) ? json_decode($order->products, true) : [];
                    if (empty($products) && !empty($order->order_data)) {
                        $orderData = json_decode($order->order_data, true);
                        $products = $orderData['products'] ?? [];
                    }
                    foreach ($products as $product) {
                        $sku = $product['sku'] ?? $product['offer_id'] ?? null;
                        if (!$sku) {
                            continue;
                        }
                        $ozonProductItems->push([
                            'sku' => (string) $sku,
                            'name' => $product['name'] ?? $sku,
                            'quantity' => (int) ($product['quantity'] ?? 1),
                            'revenue' => (float) ($product['total'] ?? (($product['price'] ?? 0) * ($product['quantity'] ?? 1))),
                        ]);
                    }
                }

                foreach ($ozonProductItems->groupBy('sku') as $sku => $rows) {
                    $allItems->push([
                        'sku' => $sku,
                        'name' => $rows->first()['name'],
                        'quantity' => (int) $rows->sum('quantity'),
                        'revenue' => $this->convertToDisplay((float) $rows->sum('revenue'), 'RUB'),
                        'cost' => null,
                        'has_cost' => false,
                    ]);
                }
            }

            // YM — извлекаем товары из JSON поля order_data.items
            if ($this->shouldIncludeSource($source, 'ym')) {
                $ymOrders = DB::table('yandex_market_orders')
                    ->whereIn('marketplace_account_id', $accountIds)
                    ->whereBetween('created_at_ym', [$dateRange['from'], $dateRange['to']])
                    ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
                    ->select('order_data')
                    ->get();

                $ymProductItems = collect();
                foreach ($ymOrders as $order) {
                    $orderData = !empty($order->order_data) ? json_decode($order->order_data, true) : [];
                    $items = $orderData['items'] ?? [];
                    foreach ($items as $item) {
                        $offerId = (string) ($item['offerId'] ?? $item['external_offer_id'] ?? '');
                        if (empty($offerId)) {
                            continue;
                        }
                        $price = (float) ($item['buyerPrice'] ?? $item['price'] ?? 0);
                        $count = (int) ($item['count'] ?? $item['quantity'] ?? 1);
                        $ymProductItems->push([
                            'sku' => $offerId,
                            'name' => $item['offerName'] ?? $item['name'] ?? $offerId,
                            'quantity' => $count,
                            'revenue' => $price * $count,
                        ]);
                    }
                }

                foreach ($ymProductItems->groupBy('sku') as $sku => $rows) {
                    $allItems->push([
                        'sku' => $sku,
                        'name' => $rows->first()['name'],
                        'quantity' => (int) $rows->sum('quantity'),
                        'revenue' => $this->convertToDisplay((float) $rows->sum('revenue'), 'RUB'),
                        'cost' => null,
                        'has_cost' => false,
                    ]);
                }
            }
        }

        // Ручные продажи
        if ($this->shouldIncludeSource($source, 'manual')) {
        $manualItems = DB::table('sale_items')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->where('sales.company_id', $this->companyId)
            ->whereBetween('sales.created_at', [$dateRange['from'], $dateRange['to']])
            ->where('sales.status', '!=', 'cancelled')
            ->whereNull('sales.deleted_at')
            ->select(
                DB::raw('COALESCE(sale_items.sku, sale_items.product_name) as sku'),
                'sale_items.product_name as name',
                'sale_items.quantity',
                'sale_items.total as revenue',
                'sale_items.cost_price'
            )
            ->get();

        foreach ($manualItems->groupBy('sku') as $sku => $rows) {
            $totalQty = (int) $rows->sum('quantity');
            $totalRevenue = (float) $rows->sum('revenue');
            $totalCost = 0;
            $hasCost = false;
            foreach ($rows as $row) {
                if ($row->cost_price !== null) {
                    $totalCost += (float) $row->cost_price * (float) $row->quantity;
                    $hasCost = true;
                }
            }
            $allItems->push([
                'sku' => $sku,
                'name' => $rows->first()->name ?? $sku,
                'quantity' => $totalQty,
                'revenue' => $totalRevenue,
                'cost' => $hasCost ? $totalCost : null,
                'has_cost' => $hasCost,
            ]);
        }
        }

        // Оффлайн продажи — unit_cost
        if ($this->shouldIncludeSource($source, 'offline')) {
        $offlineItems = DB::table('offline_sale_items')
            ->join('offline_sales', 'offline_sale_items.offline_sale_id', '=', 'offline_sales.id')
            ->where('offline_sales.company_id', $this->companyId)
            ->whereBetween('offline_sales.sale_date', [$dateRange['from'], $dateRange['to']])
            ->whereIn('offline_sales.status', ['confirmed', 'delivered'])
            ->whereNull('offline_sales.deleted_at')
            ->select(
                DB::raw('COALESCE(offline_sale_items.sku_code, offline_sale_items.product_name) as sku'),
                'offline_sale_items.product_name as name',
                'offline_sale_items.quantity',
                'offline_sale_items.line_total as revenue',
                'offline_sale_items.unit_cost'
            )
            ->get();

        foreach ($offlineItems->groupBy('sku') as $sku => $rows) {
            $totalQty = (int) $rows->sum('quantity');
            $totalRevenue = (float) $rows->sum('revenue');
            $totalCost = 0;
            $hasCost = false;
            foreach ($rows as $row) {
                if ($row->unit_cost !== null && (float) $row->unit_cost > 0) {
                    $totalCost += (float) $row->unit_cost * (float) $row->quantity;
                    $hasCost = true;
                }
            }
            $allItems->push([
                'sku' => $sku,
                'name' => $rows->first()->name ?? $sku,
                'quantity' => $totalQty,
                'revenue' => $totalRevenue,
                'cost' => $hasCost ? $totalCost : null,
                'has_cost' => $hasCost,
            ]);
        }
        }

        // Объединяем дубликаты по SKU
        $merged = $allItems->groupBy('sku')->map(function ($group) {
            $first = $group->first();
            $totalRevenue = $group->sum('revenue');
            $totalQty = $group->sum('quantity');
            $hasCost = $group->contains('has_cost', true);
            $totalCost = $hasCost ? $group->sum(fn ($i) => $i['cost'] ?? 0) : null;

            return [
                'sku' => $first['sku'],
                'name' => $first['name'],
                'quantity' => $totalQty,
                'revenue' => $totalRevenue,
                'cost' => $totalCost,
                'has_cost' => $hasCost,
            ];
        })->values();

        // Рассчитываем маржу и сортируем
        $totalRevenue = $merged->sum('revenue');
        $totalCost = $merged->where('has_cost', true)->sum('cost');
        $totalProfit = $totalRevenue - $totalCost;
        $withCost = $merged->where('has_cost', true)->count();

        $products = $merged->map(function ($item) {
            $profit = $item['has_cost'] ? $item['revenue'] - $item['cost'] : null;
            $marginPercent = ($item['has_cost'] && $item['revenue'] > 0)
                ? round(($profit / $item['revenue']) * 100, 2)
                : null;

            return array_merge($item, [
                'profit' => $profit !== null ? round($profit, 2) : null,
                'margin_percent' => $marginPercent,
                'revenue' => round($item['revenue'], 2),
                'cost' => $item['cost'] !== null ? round($item['cost'], 2) : null,
            ]);
        });

        // Сначала товары с маржой (по убыванию), потом без маржи
        $sorted = $products->sortByDesc(fn ($p) => $p['margin_percent'] ?? -999)->values();

        $ranked = [];
        foreach ($sorted as $index => $item) {
            $ranked[] = array_merge($item, ['rank' => $index + 1]);
        }

        return [
            'products' => $ranked,
            'summary' => [
                'total_products' => count($ranked),
                'total_revenue' => round($totalRevenue, 2),
                'total_cost' => round($totalCost, 2),
                'total_profit' => round($totalProfit, 2),
                'avg_margin' => $totalRevenue > 0 ? round(($totalProfit / $totalRevenue) * 100, 2) : 0,
                'products_with_cost' => $withCost,
                'products_without_cost' => count($ranked) - $withCost,
            ],
            'period' => $period,
        ];
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
