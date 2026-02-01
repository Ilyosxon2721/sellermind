<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Services\SalesAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SalesAnalyticsController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        protected SalesAnalyticsService $analyticsService
    ) {}

    /**
     * Get sales overview.
     */
    public function overview(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');

        $overview = $this->analyticsService->getOverview($companyId, $period);

        return response()->json($overview);
    }

    /**
     * Get sales by day for charts.
     */
    public function salesByDay(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');

        $data = $this->analyticsService->getSalesByDay($companyId, $period);

        return response()->json($data);
    }

    /**
     * Get top selling products.
     */
    public function topProducts(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');
        $limit = (int) $request->input('limit', 10);

        $products = $this->analyticsService->getTopProducts($companyId, $period, $limit);

        return response()->json($products);
    }

    /**
     * Get worst selling products (flop).
     */
    public function flopProducts(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');
        $limit = (int) $request->input('limit', 10);

        $products = $this->analyticsService->getFlopProducts($companyId, $period, $limit);

        return response()->json($products);
    }

    /**
     * Get sales by category.
     */
    public function salesByCategory(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');

        $data = $this->analyticsService->getSalesByCategory($companyId, $period);

        return response()->json($data);
    }

    /**
     * Get sales by marketplace.
     */
    public function salesByMarketplace(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');

        $data = $this->analyticsService->getSalesByMarketplace($companyId, $period);

        return response()->json($data);
    }

    /**
     * Get product performance.
     */
    public function productPerformance(Request $request, int $productId): JsonResponse
    {
        $period = $request->input('period', '30days');

        $performance = $this->analyticsService->getProductPerformance($productId, $period);

        return response()->json($performance);
    }

    /**
     * Get comprehensive analytics dashboard data.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');

        $data = [
            'overview' => $this->analyticsService->getOverview($companyId, $period),
            'sales_by_day' => $this->analyticsService->getSalesByDay($companyId, $period),
            'top_products' => $this->analyticsService->getTopProducts($companyId, $period, 5),
            'sales_by_category' => $this->analyticsService->getSalesByCategory($companyId, $period),
            'sales_by_marketplace' => $this->analyticsService->getSalesByMarketplace($companyId, $period),
        ];

        return response()->json($data);
    }
}
