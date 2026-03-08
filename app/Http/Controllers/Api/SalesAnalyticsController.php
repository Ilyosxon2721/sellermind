<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Services\SalesAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SalesAnalyticsController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        protected SalesAnalyticsService $analyticsService
    ) {}

    /**
     * Получить сводку продаж за период.
     * Результат кэшируется на 30 минут (планировщик обновляет кэш каждый час).
     */
    public function overview(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
        ]);

        $companyId = $this->getCompanyId();
        $period = $validated['period'] ?? '30days';

        $cacheKey = "sales_analytics_{$companyId}_{$period}";
        $overview = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period) {
            return $this->analyticsService->getOverview($companyId, $period);
        });

        return response()->json($overview);
    }

    /**
     * Get sales by day for charts.
     */
    public function salesByDay(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
        ]);

        $companyId = $this->getCompanyId();
        $period = $validated['period'] ?? '30days';

        $data = $this->analyticsService->getSalesByDay($companyId, $period);

        return response()->json($data);
    }

    /**
     * Get top selling products.
     */
    public function topProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $companyId = $this->getCompanyId();
        $period = $validated['period'] ?? '30days';
        $limit = $validated['limit'] ?? 10;

        $products = $this->analyticsService->getTopProducts($companyId, $period, $limit);

        return response()->json($products);
    }

    /**
     * Get worst selling products (flop).
     */
    public function flopProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $companyId = $this->getCompanyId();
        $period = $validated['period'] ?? '30days';
        $limit = $validated['limit'] ?? 10;

        $products = $this->analyticsService->getFlopProducts($companyId, $period, $limit);

        return response()->json($products);
    }

    /**
     * Get sales by category.
     */
    public function salesByCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
        ]);

        $companyId = $this->getCompanyId();
        $period = $validated['period'] ?? '30days';

        $data = $this->analyticsService->getSalesByCategory($companyId, $period);

        return response()->json($data);
    }

    /**
     * Get sales by marketplace.
     */
    public function salesByMarketplace(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
        ]);

        $companyId = $this->getCompanyId();
        $period = $validated['period'] ?? '30days';

        $data = $this->analyticsService->getSalesByMarketplace($companyId, $period);

        return response()->json($data);
    }

    /**
     * Get product performance.
     */
    public function productPerformance(Request $request, int $productId): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
        ]);

        $period = $validated['period'] ?? '30days';

        $performance = $this->analyticsService->getProductPerformance($productId, $period);

        return response()->json($performance);
    }

    /**
     * Получить полный дашборд аналитики.
     * Каждый блок кэшируется независимо на 30 минут.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
        ]);

        $companyId = $this->getCompanyId();
        $period = $validated['period'] ?? '30days';
        $ttl = now()->addMinutes(30);

        $data = [
            'overview' => Cache::remember(
                "sales_analytics_{$companyId}_{$period}",
                $ttl,
                fn () => $this->analyticsService->getOverview($companyId, $period)
            ),
            'sales_by_day' => Cache::remember(
                "sales_by_day_{$companyId}_{$period}",
                $ttl,
                fn () => $this->analyticsService->getSalesByDay($companyId, $period)
            ),
            'top_products' => Cache::remember(
                "top_products_{$companyId}_{$period}",
                $ttl,
                fn () => $this->analyticsService->getTopProducts($companyId, $period, 5)
            ),
            'sales_by_category' => Cache::remember(
                "sales_by_category_{$companyId}_{$period}",
                $ttl,
                fn () => $this->analyticsService->getSalesByCategory($companyId, $period)
            ),
            'sales_by_marketplace' => Cache::remember(
                "sales_by_marketplace_{$companyId}_{$period}",
                $ttl,
                fn () => $this->analyticsService->getSalesByMarketplace($companyId, $period)
            ),
        ];

        return response()->json($data);
    }
}
