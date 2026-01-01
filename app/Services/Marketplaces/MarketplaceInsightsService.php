<?php
// file: app/Services/Marketplaces/MarketplaceInsightsService.php

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceReturn;
use App\Models\MarketplacePayout;
use App\Models\MarketplaceStock;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Service for generating AI-ready insights about marketplace data
 */
class MarketplaceInsightsService
{
    /**
     * Get comprehensive summary for a period
     *
     * @param int $companyId
     * @param string $periodFrom Y-m-d
     * @param string $periodTo Y-m-d
     * @return array
     */
    public function getSummaryForPeriod(int $companyId, string $periodFrom, string $periodTo): array
    {
        $accountIds = MarketplaceAccount::where('company_id', $companyId)
            ->pluck('id');

        if ($accountIds->isEmpty()) {
            return [
                'period' => ['from' => $periodFrom, 'to' => $periodTo],
                'message' => 'Нет подключённых маркетплейсов',
                'data' => [],
            ];
        }

        return [
            'period' => ['from' => $periodFrom, 'to' => $periodTo],
            'accounts_count' => $accountIds->count(),
            'sales' => $this->getSalesSummary($accountIds, $periodFrom, $periodTo),
            'returns' => $this->getReturnsSummary($accountIds, $periodFrom, $periodTo),
            'products' => $this->getProductsSummary($accountIds),
            'stocks' => $this->getStocksSummary($accountIds),
            'payouts' => $this->getPayoutsSummary($accountIds, $periodFrom, $periodTo),
            'top_products' => $this->getTopProducts($accountIds, $periodFrom, $periodTo),
            'problem_skus' => $this->getProblemSkus($accountIds),
        ];
    }

    /**
     * Get sales summary
     */
    protected function getSalesSummary(Collection $accountIds, string $from, string $to): array
    {
        $orders = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->get();

        $delivered = $orders->where('internal_status', MarketplaceOrder::INTERNAL_STATUS_DELIVERED);
        $cancelled = $orders->where('internal_status', MarketplaceOrder::INTERNAL_STATUS_CANCELLED);

        $byMarketplace = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->join('marketplace_accounts', 'marketplace_orders.marketplace_account_id', '=', 'marketplace_accounts.id')
            ->select('marketplace_accounts.marketplace', DB::raw('count(*) as count'), DB::raw('sum(total_amount) as revenue'))
            ->groupBy('marketplace_accounts.marketplace')
            ->get()
            ->mapWithKeys(fn($r) => [$r->marketplace => ['count' => $r->count, 'revenue' => (float) $r->revenue]]);

        return [
            'total_orders' => $orders->count(),
            'delivered_orders' => $delivered->count(),
            'cancelled_orders' => $cancelled->count(),
            'total_revenue' => round($delivered->sum('total_amount'), 2),
            'average_order_value' => $delivered->count() > 0
                ? round($delivered->sum('total_amount') / $delivered->count(), 2)
                : 0,
            'by_marketplace' => $byMarketplace->toArray(),
        ];
    }

    /**
     * Get returns summary
     */
    protected function getReturnsSummary(Collection $accountIds, string $from, string $to): array
    {
        $returns = MarketplaceReturn::whereHas('order', function ($q) use ($accountIds) {
            $q->whereIn('marketplace_account_id', $accountIds);
        })
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->get();

        $totalOrders = MarketplaceOrder::whereIn('marketplace_account_id', $accountIds)
            ->whereBetween('created_at', [$from, $to . ' 23:59:59'])
            ->count();

        $topReasons = $returns->groupBy('reason_code')
            ->map(fn($group) => [
                'count' => $group->count(),
                'amount' => round($group->sum('amount'), 2),
            ])
            ->sortByDesc('count')
            ->take(5);

        return [
            'total_returns' => $returns->count(),
            'return_rate' => $totalOrders > 0
                ? round(($returns->count() / $totalOrders) * 100, 2)
                : 0,
            'total_amount' => round($returns->sum('amount'), 2),
            'top_reasons' => $topReasons->toArray(),
        ];
    }

    /**
     * Get products summary
     */
    protected function getProductsSummary(Collection $accountIds): array
    {
        $products = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)->get();

        $byStatus = $products->groupBy('status')
            ->map(fn($group) => $group->count());

        return [
            'total' => $products->count(),
            'active' => $byStatus->get(MarketplaceProduct::STATUS_ACTIVE, 0),
            'pending' => $byStatus->get(MarketplaceProduct::STATUS_PENDING, 0),
            'error' => $byStatus->get(MarketplaceProduct::STATUS_ERROR, 0),
            'archived' => $byStatus->get(MarketplaceProduct::STATUS_ARCHIVED, 0),
        ];
    }

    /**
     * Get stocks summary
     */
    protected function getStocksSummary(Collection $accountIds): array
    {
        $stocks = MarketplaceStock::whereIn('marketplace_account_id', $accountIds)->get();

        $outOfStock = $stocks->filter(fn($s) => $s->getAvailableQuantity() <= 0)->count();
        $lowStock = $stocks->filter(fn($s) => $s->getAvailableQuantity() > 0 && $s->isLowStock())->count();
        $healthy = $stocks->count() - $outOfStock - $lowStock;

        return [
            'total_skus' => $stocks->count(),
            'out_of_stock' => $outOfStock,
            'low_stock' => $lowStock,
            'healthy' => $healthy,
            'total_quantity' => $stocks->sum('quantity'),
            'total_reserved' => $stocks->sum('reserved'),
        ];
    }

    /**
     * Get payouts summary
     */
    protected function getPayoutsSummary(Collection $accountIds, string $from, string $to): array
    {
        $payouts = MarketplacePayout::whereIn('marketplace_account_id', $accountIds)
            ->where(function ($q) use ($from, $to) {
                $q->whereBetween('period_from', [$from, $to])
                    ->orWhereBetween('period_to', [$from, $to]);
            })
            ->get();

        return [
            'total_payouts' => $payouts->count(),
            'total_amount' => round($payouts->sum('total_amount'), 2),
            'total_commission' => round($payouts->sum('commission_amount'), 2),
            'total_logistics' => round($payouts->sum('logistics_amount'), 2),
            'net_profit' => round($payouts->sum(fn($p) => $p->getNetProfit()), 2),
        ];
    }

    /**
     * Get top selling products
     */
    protected function getTopProducts(Collection $accountIds, string $from, string $to, int $limit = 10): array
    {
        return MarketplaceOrder::whereIn('marketplace_orders.marketplace_account_id', $accountIds)
            ->whereBetween('marketplace_orders.created_at', [$from, $to . ' 23:59:59'])
            ->where('marketplace_orders.internal_status', MarketplaceOrder::INTERNAL_STATUS_DELIVERED)
            ->join('marketplace_order_items', 'marketplace_orders.id', '=', 'marketplace_order_items.marketplace_order_id')
            ->select(
                'marketplace_order_items.sku',
                'marketplace_order_items.name',
                DB::raw('sum(marketplace_order_items.quantity) as total_quantity'),
                DB::raw('sum(marketplace_order_items.price * marketplace_order_items.quantity) as total_revenue'),
                DB::raw('count(distinct marketplace_orders.id) as orders_count')
            )
            ->groupBy('marketplace_order_items.sku', 'marketplace_order_items.name')
            ->orderByDesc('total_revenue')
            ->limit($limit)
            ->get()
            ->map(fn($item) => [
                'sku' => $item->sku,
                'name' => $item->name,
                'quantity_sold' => (int) $item->total_quantity,
                'revenue' => round((float) $item->total_revenue, 2),
                'orders_count' => (int) $item->orders_count,
            ])
            ->toArray();
    }

    /**
     * Get problem SKUs (out of stock, high returns, errors)
     */
    public function getProblemSkus(Collection $accountIds): array
    {
        $problems = [];

        // Out of stock
        $outOfStock = MarketplaceStock::whereIn('marketplace_account_id', $accountIds)
            ->where('quantity', '<=', DB::raw('reserved'))
            ->with('product:id,sku,name')
            ->limit(10)
            ->get();

        foreach ($outOfStock as $stock) {
            $problems[] = [
                'type' => 'out_of_stock',
                'severity' => 'high',
                'sku' => $stock->sku,
                'name' => $stock->product?->name ?? $stock->sku,
                'warehouse' => $stock->warehouse_id,
                'message' => "SKU {$stock->sku} закончился на складе {$stock->warehouse_id}",
            ];
        }

        // Products with errors
        $errorProducts = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
            ->where('status', MarketplaceProduct::STATUS_ERROR)
            ->limit(10)
            ->get();

        foreach ($errorProducts as $product) {
            $problems[] = [
                'type' => 'product_error',
                'severity' => 'medium',
                'sku' => $product->sku,
                'name' => $product->name,
                'marketplace' => $product->account->marketplace ?? 'unknown',
                'message' => "Товар {$product->sku} имеет ошибку на маркетплейсе",
            ];
        }

        // High return rate SKUs (last 30 days)
        $monthAgo = now()->subDays(30)->toDateString();
        $highReturns = MarketplaceReturn::whereHas('order', function ($q) use ($accountIds) {
            $q->whereIn('marketplace_account_id', $accountIds);
        })
            ->where('created_at', '>=', $monthAgo)
            ->select('marketplace_order_item_id', DB::raw('count(*) as return_count'))
            ->groupBy('marketplace_order_item_id')
            ->having('return_count', '>=', 3)
            ->with('orderItem:id,sku,name')
            ->limit(10)
            ->get();

        foreach ($highReturns as $return) {
            if ($return->orderItem) {
                $problems[] = [
                    'type' => 'high_returns',
                    'severity' => 'medium',
                    'sku' => $return->orderItem->sku,
                    'name' => $return->orderItem->name,
                    'return_count' => $return->return_count,
                    'message' => "SKU {$return->orderItem->sku} имеет {$return->return_count} возвратов за 30 дней",
                ];
            }
        }

        // Sort by severity
        usort($problems, function ($a, $b) {
            $severityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
            return ($severityOrder[$a['severity']] ?? 4) <=> ($severityOrder[$b['severity']] ?? 4);
        });

        return array_slice($problems, 0, 20);
    }

    /**
     * Generate AI-friendly text summary
     */
    public function generateTextSummary(int $companyId, string $periodFrom, string $periodTo): string
    {
        $data = $this->getSummaryForPeriod($companyId, $periodFrom, $periodTo);

        if (isset($data['message'])) {
            return $data['message'];
        }

        $sales = $data['sales'];
        $returns = $data['returns'];
        $stocks = $data['stocks'];
        $problems = $data['problem_skus'];

        $lines = [
            "=== Сводка по маркетплейсам за период {$periodFrom} - {$periodTo} ===",
            "",
            "ПРОДАЖИ:",
            "- Всего заказов: {$sales['total_orders']}",
            "- Доставлено: {$sales['delivered_orders']}",
            "- Отменено: {$sales['cancelled_orders']}",
            "- Выручка: " . number_format($sales['total_revenue'], 0, '.', ' ') . " руб.",
            "- Средний чек: " . number_format($sales['average_order_value'], 0, '.', ' ') . " руб.",
            "",
            "ВОЗВРАТЫ:",
            "- Всего возвратов: {$returns['total_returns']}",
            "- Процент возвратов: {$returns['return_rate']}%",
            "- Сумма возвратов: " . number_format($returns['total_amount'], 0, '.', ' ') . " руб.",
            "",
            "СКЛАДЫ:",
            "- Всего SKU: {$stocks['total_skus']}",
            "- Нет в наличии: {$stocks['out_of_stock']}",
            "- Низкий остаток: {$stocks['low_stock']}",
            "",
        ];

        if (!empty($problems)) {
            $lines[] = "ПРОБЛЕМЫ (" . count($problems) . "):";
            foreach (array_slice($problems, 0, 5) as $problem) {
                $lines[] = "- [{$problem['severity']}] {$problem['message']}";
            }
            if (count($problems) > 5) {
                $lines[] = "  ... и ещё " . (count($problems) - 5) . " проблем";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get insights for AI agent context
     */
    public function getInsightsForAgent(int $companyId): array
    {
        $today = now()->toDateString();
        $weekAgo = now()->subDays(7)->toDateString();
        $monthAgo = now()->subDays(30)->toDateString();

        return [
            'week_summary' => $this->getSummaryForPeriod($companyId, $weekAgo, $today),
            'month_summary' => $this->getSummaryForPeriod($companyId, $monthAgo, $today),
            'text_summary' => $this->generateTextSummary($companyId, $weekAgo, $today),
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * Get recommendations based on insights
     */
    public function getRecommendations(int $companyId): array
    {
        $recommendations = [];

        $accountIds = MarketplaceAccount::where('company_id', $companyId)->pluck('id');
        if ($accountIds->isEmpty()) {
            return ['Подключите маркетплейсы для получения рекомендаций'];
        }

        // Check stocks
        $outOfStock = MarketplaceStock::whereIn('marketplace_account_id', $accountIds)
            ->where('quantity', '<=', DB::raw('reserved'))
            ->count();

        if ($outOfStock > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'stocks',
                'message' => "Пополните запасы: {$outOfStock} SKU закончились на складах",
                'action' => 'replenish_stocks',
            ];
        }

        // Check error products
        $errorProducts = MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
            ->where('status', MarketplaceProduct::STATUS_ERROR)
            ->count();

        if ($errorProducts > 0) {
            $recommendations[] = [
                'priority' => 'high',
                'category' => 'products',
                'message' => "Исправьте ошибки в {$errorProducts} карточках товаров",
                'action' => 'fix_product_errors',
            ];
        }

        // Check return rate
        $monthAgo = now()->subDays(30)->toDateString();
        $today = now()->toDateString();
        $returnsSummary = $this->getReturnsSummary($accountIds, $monthAgo, $today);

        if ($returnsSummary['return_rate'] > 5) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'returns',
                'message' => "Высокий процент возвратов ({$returnsSummary['return_rate']}%). Проверьте качество товаров и описания",
                'action' => 'analyze_returns',
            ];
        }

        // Check low stock
        $lowStock = MarketplaceStock::whereIn('marketplace_account_id', $accountIds)
            ->whereColumn('quantity', '>', 'reserved')
            ->whereRaw('(quantity - reserved) < low_stock_threshold')
            ->count();

        if ($lowStock > 0) {
            $recommendations[] = [
                'priority' => 'medium',
                'category' => 'stocks',
                'message' => "Запасы заканчиваются для {$lowStock} SKU",
                'action' => 'plan_replenishment',
            ];
        }

        // Sort by priority
        usort($recommendations, function ($a, $b) {
            $priorityOrder = ['high' => 1, 'medium' => 2, 'low' => 3];
            return ($priorityOrder[$a['priority']] ?? 4) <=> ($priorityOrder[$b['priority']] ?? 4);
        });

        return $recommendations;
    }
}
