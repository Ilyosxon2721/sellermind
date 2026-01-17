<?php
// file: app/Services/Marketplaces/MarketplaceDashboardService.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceReturn;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceSyncLog;
use App\Models\UzumOrder;
use App\Models\WildberriesOrder;
use Illuminate\Support\Collection;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class MarketplaceDashboardService
{
    /**
     * Статусы отменённых заказов (исключаются из расчёта выручки)
     */
    private const CANCELLED_STATUSES = ['cancelled', 'canceled', 'CANCELED', 'PENDING_CANCELLATION'];
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
                'cancelled_amount' => 0.0,
                'cancelled_count' => 0,
            ];
        }

        $accountIds = $accounts->pluck('id');

        // Собираем заказы из всех маркетплейсов (исключая отменённые из выручки)
        $uzumOrders = UzumOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$from, $to])
            ->whereNotIn('status_normalized', self::CANCELLED_STATUSES)
            ->get();

        $wbOrders = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$from, $to])
            ->where('is_cancel', false)
            ->where('is_return', false)
            ->get();

        $allOrders = $uzumOrders->concat($wbOrders);

        $revenue = (float) $uzumOrders->sum('total_amount') + (float) $wbOrders->sum('for_pay');
        $ordersCount = $allOrders->count();
        $avgCheck = $ordersCount > 0 ? $revenue / $ordersCount : 0.0;

        // Отменённые заказы отдельно
        $cancelledUzum = UzumOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('ordered_at', [$from, $to])
            ->whereIn('status_normalized', self::CANCELLED_STATUSES)
            ->get();

        $cancelledWb = WildberriesOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('order_date', [$from, $to])
            ->where(fn($q) => $q->where('is_cancel', true)->orWhere('is_return', true))
            ->get();

        $cancelledOrders = $cancelledUzum->concat($cancelledWb);
        $cancelledAmount = (float) $cancelledUzum->sum('total_amount') + (float) $cancelledWb->sum('for_pay');
        $cancelledCount = $cancelledOrders->count();

        // Возвраты за период (по всем заказам, включая отменённые)
        $allOrderIds = $allOrders->pluck('id')->concat($cancelledOrders->pluck('id'));
        $returnsCount = MarketplaceReturn::whereIn('marketplace_order_id', $allOrderIds)->count();
        $totalOrdersCount = $ordersCount + $cancelledCount;
        $returnRate = $totalOrdersCount > 0 ? ($returnsCount / $totalOrdersCount) * 100.0 : 0.0;

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
            'cancelled_amount' => $cancelledAmount,
            'cancelled_count' => $cancelledCount,
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
     *      'cancelled_amount' => float,
     *      'cancelled_count'  => int,
     *      'last_sync_at'  => ?Carbon,
     *      'last_sync_status' => string,
     *    ],
     *  ]
     */
    protected function computeKpiByAccount(Collection $accounts, Carbon $from, Carbon $to): array
    {
        $result = [];

        foreach ($accounts as $account) {
            // Получаем заказы в зависимости от маркетплейса
            $orders = $this->getOrdersForAccount($account, $from, $to, excludeCancelled: true);
            $cancelledOrders = $this->getOrdersForAccount($account, $from, $to, onlyCancelled: true);

            $revenue = (float) $orders->sum('total_amount');
            $ordersCount = $orders->count();
            $avgCheck = $ordersCount > 0 ? $revenue / $ordersCount : 0.0;

            $cancelledAmount = (float) $cancelledOrders->sum('total_amount');
            $cancelledCount = $cancelledOrders->count();

            $allOrderIds = $orders->pluck('id')->concat($cancelledOrders->pluck('id'));
            $returnsCount = MarketplaceReturn::whereIn('marketplace_order_id', $allOrderIds)->count();
            $totalOrdersCount = $ordersCount + $cancelledCount;
            $returnRate = $totalOrdersCount > 0 ? ($returnsCount / $totalOrdersCount) * 100.0 : 0.0;

            $lastSync = MarketplaceSyncLog::where('marketplace_account_id', $account->id)
                ->orderByDesc('created_at')
                ->first();

            $result[$account->id] = [
                'revenue' => $revenue,
                'orders_count' => $ordersCount,
                'avg_check' => $avgCheck,
                'return_rate' => $returnRate,
                'cancelled_amount' => $cancelledAmount,
                'cancelled_count' => $cancelledCount,
                'last_sync_at' => $lastSync?->finished_at ?? $lastSync?->created_at,
                'last_sync_status' => $lastSync?->status ?? null,
            ];
        }

        return $result;
    }

    /**
     * Получить заказы для конкретного аккаунта маркетплейса.
     */
    protected function getOrdersForAccount(
        MarketplaceAccount $account,
        Carbon $from,
        Carbon $to,
        bool $excludeCancelled = false,
        bool $onlyCancelled = false
    ): Collection {
        $marketplace = $account->marketplace;

        if ($marketplace === 'uzum') {
            $query = UzumOrder::where('marketplace_account_id', $account->id)
                ->whereBetween('ordered_at', [$from, $to]);

            if ($excludeCancelled) {
                $query->whereNotIn('status_normalized', self::CANCELLED_STATUSES);
            } elseif ($onlyCancelled) {
                $query->whereIn('status_normalized', self::CANCELLED_STATUSES);
            }

            return $query->get();
        }

        if ($marketplace === 'wb') {
            $query = WildberriesOrder::where('marketplace_account_id', $account->id)
                ->whereBetween('order_date', [$from, $to]);

            if ($excludeCancelled) {
                $query->where('is_cancel', false)->where('is_return', false);
            } elseif ($onlyCancelled) {
                $query->where(fn($q) => $q->where('is_cancel', true)->orWhere('is_return', true));
            }

            return $query->get();
        }

        // Для других маркетплейсов возвращаем пустую коллекцию
        return collect();
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

        // Собираем все items из заказов (исключая отменённые)
        $allItems = collect();

        foreach ($accounts as $account) {
            $orders = $this->getOrdersForAccount($account, $from, $to, excludeCancelled: true);

            if ($orders->isEmpty()) {
                continue;
            }

            foreach ($orders as $order) {
                if (method_exists($order, 'items')) {
                    $items = $order->items;
                    foreach ($items as $item) {
                        $allItems->push([
                            'marketplace_account_id' => $account->id,
                            'marketplace_order_id' => $order->id,
                            'external_offer_id' => $item->external_offer_id,
                            'name' => $item->name,
                            'quantity' => $item->quantity,
                            'total_price' => $item->total_price,
                        ]);
                    }
                }
            }
        }

        if ($allItems->isEmpty()) {
            return [];
        }

        // Группировка по external_offer_id + marketplace_account_id
        $grouped = $allItems->groupBy(function ($item) {
            return $item['external_offer_id'] . ':' . $item['marketplace_account_id'];
        });

        $problemList = [];

        foreach ($grouped as $key => $group) {
            $first = $group->first();
            $accountId = $first['marketplace_account_id'];
            $externalOfferId = $first['external_offer_id'];

            $totalQty = (int) $group->sum('quantity');
            $totalRevenue = (float) $group->sum('total_price');

            // Возвраты по этим заказам
            $orderIdsForGroup = $group->pluck('marketplace_order_id')->unique();
            $returnsCount = MarketplaceReturn::whereIn('marketplace_order_id', $orderIdsForGroup)->count();
            $ordersCount = $orderIdsForGroup->count();
            $returnRate = $ordersCount > 0 ? ($returnsCount / $ordersCount) * 100.0 : 0.0;

            $problemList[] = [
                'account_id' => $accountId,
                'external_offer_id' => $externalOfferId,
                'name' => $first['name'],
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
        $overallCancelledRevenue = array_fill(0, count($labels), 0.0);
        $overallCancelledOrders = array_fill(0, count($labels), 0);

        // Use marketplace codes from the project
        $byMarketplace = [
            'wb' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
                'cancelled_revenue' => array_fill(0, count($labels), 0.0),
                'cancelled_orders' => array_fill(0, count($labels), 0),
            ],
            'ozon' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
                'cancelled_revenue' => array_fill(0, count($labels), 0.0),
                'cancelled_orders' => array_fill(0, count($labels), 0),
            ],
            'uzum' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
                'cancelled_revenue' => array_fill(0, count($labels), 0.0),
                'cancelled_orders' => array_fill(0, count($labels), 0),
            ],
            'ym' => [
                'revenue' => array_fill(0, count($labels), 0.0),
                'orders' => array_fill(0, count($labels), 0),
                'cancelled_revenue' => array_fill(0, count($labels), 0.0),
                'cancelled_orders' => array_fill(0, count($labels), 0),
            ],
        ];

        if ($accounts->isEmpty()) {
            return [
                'labels' => $labels,
                'overall' => [
                    'revenue' => $overallRevenue,
                    'orders' => $overallOrders,
                    'cancelled_revenue' => $overallCancelledRevenue,
                    'cancelled_orders' => $overallCancelledOrders,
                ],
                'by_marketplace' => $byMarketplace,
            ];
        }

        // Map account_id -> marketplace
        $accountsById = $accounts->keyBy('id');

        // Index by date
        $labelIndex = array_flip($labels);

        // Load all orders for the period from each marketplace
        foreach ($accounts as $account) {
            $orders = $this->getOrdersForAccount($account, $from->copy()->startOfDay(), $to->copy()->endOfDay(), excludeCancelled: true);
            $cancelledOrders = $this->getOrdersForAccount($account, $from->copy()->startOfDay(), $to->copy()->endOfDay(), onlyCancelled: true);

            $mp = $account->marketplace;

            // Process non-cancelled orders
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

                if (isset($byMarketplace[$mp])) {
                    $byMarketplace[$mp]['revenue'][$idx] += $revenue;
                    $byMarketplace[$mp]['orders'][$idx]++;
                }
            }

            // Process cancelled orders separately
            foreach ($cancelledOrders as $order) {
                if (!$order->ordered_at) {
                    continue;
                }

                $dateKey = $order->ordered_at->copy()->startOfDay()->format('Y-m-d');
                if (!isset($labelIndex[$dateKey])) {
                    continue;
                }

                $idx = $labelIndex[$dateKey];
                $revenue = (float) $order->total_amount;

                $overallCancelledRevenue[$idx] += $revenue;
                $overallCancelledOrders[$idx]++;

                if (isset($byMarketplace[$mp])) {
                    $byMarketplace[$mp]['cancelled_revenue'][$idx] += $revenue;
                    $byMarketplace[$mp]['cancelled_orders'][$idx]++;
                }
            }
        }

        return [
            'labels' => $labels,
            'overall' => [
                'revenue' => $overallRevenue,
                'orders' => $overallOrders,
                'cancelled_revenue' => $overallCancelledRevenue,
                'cancelled_orders' => $overallCancelledOrders,
            ],
            'by_marketplace' => $byMarketplace,
        ];
    }
}
