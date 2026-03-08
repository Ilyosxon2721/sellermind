<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Store\Store;
use App\Models\Store\StoreAnalytics;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Аналитика магазина
 */
final class StoreAnalyticsController extends Controller
{
    use ApiResponder, HasCompanyScope;

    /**
     * Получить аналитику магазина за период
     */
    public function index(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $request->input('from', now()->subDays(30)->toDateString());
        $to = $request->input('to', now()->toDateString());

        $analytics = StoreAnalytics::where('store_id', $store->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();

        // Агрегированные итоги за период
        $totals = [
            'visits' => $analytics->sum('visits'),
            'unique_visitors' => $analytics->sum('unique_visitors'),
            'page_views' => $analytics->sum('page_views'),
            'cart_additions' => $analytics->sum('cart_additions'),
            'checkouts_started' => $analytics->sum('checkouts_started'),
            'orders_completed' => $analytics->sum('orders_completed'),
            'revenue' => (float) $analytics->sum('revenue'),
            'average_order' => $analytics->where('orders_completed', '>', 0)->avg('average_order') ?? 0.0,
            'conversion_rate' => $analytics->sum('visits') > 0
                ? round($analytics->sum('orders_completed') / $analytics->sum('visits') * 100, 2)
                : 0.0,
        ];

        return $this->successResponse([
            'period' => ['from' => $from, 'to' => $to],
            'totals' => $totals,
            'daily' => $analytics,
        ]);
    }

    /**
     * Найти магазин текущей компании
     */
    private function findStore(int $storeId): Store
    {
        $companyId = $this->getCompanyId();

        return Store::where('company_id', $companyId)->findOrFail($storeId);
    }
}
