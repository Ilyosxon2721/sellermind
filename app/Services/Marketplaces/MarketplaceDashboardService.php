<?php
// file: app/Services/Marketplaces/MarketplaceDashboardService.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceReturn;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSyncLog;
use App\Models\MarketplaceOrderItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MarketplaceDashboardService
{
    /**
     * Построение всех данных для дашборда.
     *
     * @param Collection<int, MarketplaceAccount> $accounts
     * @param array{period:string,date_from:?string,date_to:?string,marketplace:string} $filters
     * @return array
     */
    public function buildDashboardData(Collection $accounts, array $filters): array
    {
        [$from, $to] = $this->resolvePeriod($filters);

        // Общие KPI по всем аккаунтам
        $overallKpi = $this->computeOverallKpi($accounts, $from, $to);

        // KPI по каждому аккаунту
        $byAccount = $this->computeKpiByAccount($accounts, $from, $to);

        // Проблемные SKU
        $problemSkus = $this->computeProblemSkus($accounts, $from, $to);

        // Последние события (логи синхронизаций)
        $recentEvents = $this->getRecentEvents($accounts);

        return [
            'period' => [
                'from' => $from,
                'to' => $to,
            ],
            'overall_kpi' => $overallKpi,
            'by_account' => $byAccount,
            'problem_skus' => $problemSkus,
            'recent_events' => $recentEvents,
        ];
    }

    /**
     * Определить временной интервал по фильтрам.
     *
     * Поддерживаем:
     *  - period = today  -> текущий день
     *  - period = 7d     -> последние 7 дней
     *  - period = 30d    -> последние 30 дней
     *  - period = custom -> использовать date_from, date_to
     */
    protected function resolvePeriod(array $filters): array
    {
        $now = Carbon::now()->endOfDay();

        switch ($filters['period'] ?? '7d') {
            case 'today':
                $from = Carbon::now()->startOfDay();
                $to = $now;
                break;

            case '30d':
                $from = $now->copy()->subDays(29)->startOfDay();
                $to = $now;
                break;

            case 'custom':
                $from = $filters['date_from']
                    ? Carbon::parse($filters['date_from'])->startOfDay()
                    : $now->copy()->subDays(6)->startOfDay();

                $to = $filters['date_to']
                    ? Carbon::parse($filters['date_to'])->endOfDay()
                    : $now;
                break;

            case '7d':
            default:
                $from = $now->copy()->subDays(6)->startOfDay();
                $to = $now;
                break;
        }

        return [$from, $to];
    }

    /**
     * Общие KPI по всем маркетплейсам.
     */
    protected function computeOverallKpi(Collection $accounts, Carbon $from, Carbon $to): array
    {
        if ($accounts->isEmpty()) {
            return [
                'revenue' => 0.0,
                'orders_count' => 0,
                'avg_check' => 0.0,
                'return_rate' => 0.0,
                'active_skus' => 0,
            ];
        }

        $accountIds = $accounts->pluck('id');

        $orders = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$from, $to])
            ->get();

        $revenue = (float) $orders->sum('total_amount');
        $ordersCount = $orders->count();
        $avgCheck = $ordersCount > 0 ? $revenue / $ordersCount : 0.0;

        // Возвраты за период
        $orderIds = $orders->pluck('id');
        $returnsCount = MarketplaceReturn::whereIn('marketplace_order_id', $orderIds)->count();
        $returnRate = $ordersCount > 0 ? ($returnsCount / $ordersCount) * 100.0 : 0.0;

        // Кол-во активных SKU
        $activeSkus = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
            ->where('status', 'active')
            ->count();

        return [
            'revenue' => $revenue,
            'orders_count' => $ordersCount,
            'avg_check' => $avgCheck,
            'return_rate' => $returnRate,
            'active_skus' => $activeSkus,
        ];
    }

    /**
     * KPI по каждому аккаунту.
     *
     * Возвращает массив:
     *  [
     *    account_id => [
     *      'revenue'       => float,
     *      'orders_count'  => int,
     *      'avg_check'     => float,
     *      'return_rate'   => float,
     *      'last_sync_at'  => ?Carbon,
     *      'last_sync_status' => string,
     *    ],
     *  ]
     */
    protected function computeKpiByAccount(Collection $accounts, Carbon $from, Carbon $to): array
    {
        $result = [];

        foreach ($accounts as $account) {
            $orders = MarketplaceOrder::where('marketplace_account_id', $account->id)
                ->whereBetween('ordered_at', [$from, $to])
                ->get();

            $revenue = (float) $orders->sum('total_amount');
            $ordersCount = $orders->count();
            $avgCheck = $ordersCount > 0 ? $revenue / $ordersCount : 0.0;

            $orderIds = $orders->pluck('id');
            $returnsCount = MarketplaceReturn::whereIn('marketplace_order_id', $orderIds)->count();
            $returnRate = $ordersCount > 0 ? ($returnsCount / $ordersCount) * 100.0 : 0.0;

            $lastSync = MarketplaceSyncLog::where('marketplace_account_id', $account->id)
                ->orderByDesc('created_at')
                ->first();

            $result[$account->id] = [
                'revenue' => $revenue,
                'orders_count' => $ordersCount,
                'avg_check' => $avgCheck,
                'return_rate' => $returnRate,
                'last_sync_at' => $lastSync?->finished_at ?? $lastSync?->created_at,
                'last_sync_status' => $lastSync?->status ?? null,
            ];
        }

        return $result;
    }

    /**
     * Проблемные SKU:
     *  - мало продаж;
     *  - много возвратов;
     *  - низкая выручка за период.
     *
     * Возвращает список с ограничением, например, до 10–20 строк.
     */
    protected function computeProblemSkus(Collection $accounts, Carbon $from, Carbon $to, int $limit = 20): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        $accountIds = $accounts->pluck('id');

        // Берём все заказы за период
        $orders = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$from, $to])
            ->get();

        if ($orders->isEmpty()) {
            return [];
        }

        $orderIds = $orders->pluck('id');

        $items = MarketplaceOrderItem::whereIn('marketplace_order_id', $orderIds)
            ->with('order')
            ->get();

        if ($items->isEmpty()) {
            return [];
        }

        // Группировка по external_offer_id + marketplace_account_id
        $grouped = $items->groupBy(function ($item) {
            return $item->external_offer_id . ':' . $item->order->marketplace_account_id;
        });

        $problemList = [];

        foreach ($grouped as $key => $group) {
            /** @var MarketplaceOrderItem $first */
            $first = $group->first();
            $accountId = $first->order->marketplace_account_id;
            $externalOfferId = $first->external_offer_id;

            $totalQty = (int) $group->sum('quantity');
            $totalRevenue = (float) $group->sum('total_price');

            // Возвраты по этим заказам
            $orderIdsForGroup = $group->pluck('marketplace_order_id');
            $returnsCount = MarketplaceReturn::whereIn('marketplace_order_id', $orderIdsForGroup)->count();
            $ordersCount = $group->pluck('marketplace_order_id')->unique()->count();
            $returnRate = $ordersCount > 0 ? ($returnsCount / $ordersCount) * 100.0 : 0.0;

            $problemList[] = [
                'account_id' => $accountId,
                'external_offer_id' => $externalOfferId,
                'name' => $first->name,
                'total_qty' => $totalQty,
                'total_revenue' => $totalRevenue,
                'return_rate' => $returnRate,
            ];
        }

        // Считаем проблемными:
        // - с низкой выручкой и/или высоким return_rate
        usort($problemList, function (array $a, array $b) {
            // Сначала по return_rate (по убыванию), потом по total_revenue (по возрастанию)
            $cmp = $b['return_rate'] <=> $a['return_rate'];
            if ($cmp === 0) {
                $cmp = $a['total_revenue'] <=> $b['total_revenue'];
            }
            return $cmp;
        });

        return array_slice($problemList, 0, $limit);
    }

    /**
     * Последние события по синхронизациям.
     */
    protected function getRecentEvents(Collection $accounts, int $limit = 20): array
    {
        if ($accounts->isEmpty()) {
            return [];
        }

        $accountIds = $accounts->pluck('id');

        $logs = MarketplaceSyncLog::whereIn('marketplace_account_id', $accountIds)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        return $logs->map(function (MarketplaceSyncLog $log) {
            return [
                'account_id' => $log->marketplace_account_id,
                'type' => $log->type,
                'status' => $log->status,
                'message' => $log->message,
                'started_at' => $log->started_at,
                'finished_at' => $log->finished_at,
                'created_at' => $log->created_at,
            ];
        })->all();
    }

    /**
     * Построить временные ряды для графиков:
     * - labels: массив дат (строковый формат Y-m-d);
     * - overall: общая выручка и заказы по всем аккаунтам;
     * - by_marketplace: разбивка по каждому маркетплейсу.
     *
     * @param Collection<int, MarketplaceAccount> $accounts
     */
    public function getDailySeries(Collection $accounts, Carbon $from, Carbon $to): array
    {
        $labels = [];
        $period = CarbonPeriod::create($from->copy()->startOfDay(), $to->copy()->startOfDay());

        foreach ($period as $date) {
            $labels[] = $date->format('Y-m-d');
        }

        $overallRevenue = array_fill(0, count($labels), 0.0);
        $overallOrders = array_fill(0, count($labels), 0);

        // Use marketplace codes from the project
        $byMarketplace = [
            'wb' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
            ],
            'ozon' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
            ],
            'uzum' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
            ],
            'ym' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
            ],
        ];

        if ($accounts->isEmpty()) {
            return [
                'labels' => $labels,
                'overall' => [
                    'revenue' => $overallRevenue,
                    'orders' => $overallOrders,
                ],
                'by_marketplace' => $byMarketplace,
            ];
        }

        $accountIds = $accounts->pluck('id');

        // Load all orders for the period
        $orders = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->get();

        if ($orders->isEmpty()) {
            return [
                'labels' => $labels,
                'overall' => [
                    'revenue' => $overallRevenue,
                    'orders' => $overallOrders,
                ],
                'by_marketplace' => $byMarketplace,
            ];
        }

        // Map account_id -> marketplace
        $accountsById = $accounts->keyBy('id');

        // Index by date
        $labelIndex = array_flip($labels);

        foreach ($orders as $order) {
            if (!$order->ordered_at) {
                continue;
            }

            $dateKey = $order->ordered_at->copy()->startOfDay()->format('Y-m-d');
            if (!isset($labelIndex[$dateKey])) {
                continue;
            }

            $idx = $labelIndex[$dateKey];

            $revenue = (float) $order->total_amount;
            $overallRevenue[$idx] += $revenue;
            $overallOrders[$idx]++;

            $account = $accountsById->get($order->marketplace_account_id);
            if (!$account) {
                continue;
            }

            $mp = $account->marketplace;

            if (isset($byMarketplace[$mp])) {
                $byMarketplace[$mp]['revenue'][$idx] += $revenue;
                $byMarketplace[$mp]['orders'][$idx]++;
            } else {
                // Create new marketplace key if not exists
                if (!isset($byMarketplace[$mp])) {
                    $byMarketplace[$mp] = [
                        'revenue' => array_fill(0, count($labels), 0.0),
                        'orders' => array_fill(0, count($labels), 0),
                    ];
                }
                $byMarketplace[$mp]['revenue'][$idx] += $revenue;
                $byMarketplace[$mp]['orders'][$idx]++;
            }
        }

        return [
            'labels' => $labels,
            'overall' => [
                'revenue' => $overallRevenue,
                'orders' => $overallOrders,
            ],
            'by_marketplace' => $byMarketplace,
        ];
    }
}
