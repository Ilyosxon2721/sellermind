<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Http\Requests\Analytics\SalesAnalyticsRequest;
use App\Services\SalesAnalyticsService;
use Illuminate\Http\JsonResponse;
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
    public function overview(SalesAnalyticsRequest $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->getPeriod();

        $cacheKey = "sales_analytics_{$companyId}_{$period}";
        $overview = Cache::remember($cacheKey, now()->addMinutes(30), function () use ($companyId, $period) {
            return $this->analyticsService->getOverview($companyId, $period);
        });

        return response()->json($overview);
    }

    /**
     * Получить продажи по дням для графиков
     */
    public function salesByDay(SalesAnalyticsRequest $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->getPeriod();

        $data = $this->analyticsService->getSalesByDay($companyId, $period);

        return response()->json($data);
    }

    /**
     * Получить топ продаваемых товаров
     */
    public function topProducts(SalesAnalyticsRequest $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->getPeriod();
        $limit = $request->getLimit();

        $products = $this->analyticsService->getTopProducts($companyId, $period, $limit);

        return response()->json($products);
    }

    /**
     * Получить худшие по продажам товары
     */
    public function flopProducts(SalesAnalyticsRequest $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->getPeriod();
        $limit = $request->getLimit();

        $products = $this->analyticsService->getFlopProducts($companyId, $period, $limit);

        return response()->json($products);
    }

    /**
     * Получить продажи по категориям
     */
    public function salesByCategory(SalesAnalyticsRequest $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->getPeriod();

        $data = $this->analyticsService->getSalesByCategory($companyId, $period);

        return response()->json($data);
    }

    /**
     * Получить продажи по маркетплейсам
     */
    public function salesByMarketplace(SalesAnalyticsRequest $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->getPeriod();

        $data = $this->analyticsService->getSalesByMarketplace($companyId, $period);

        return response()->json($data);
    }

    /**
     * Получить показатели эффективности товара
     */
    public function productPerformance(SalesAnalyticsRequest $request, int $productId): JsonResponse
    {
        $period = $request->getPeriod();

        $performance = $this->analyticsService->getProductPerformance($productId, $period);

        return response()->json($performance);
    }

    /**
     * Получить полный дашборд аналитики.
     * Каждый блок кэшируется независимо на 30 минут.
     */
    public function dashboard(SalesAnalyticsRequest $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->getPeriod();
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
