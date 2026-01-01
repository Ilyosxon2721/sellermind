<?php
// file: app/Http/Controllers/Api/WildberriesAnalyticsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WildberriesAnalyticsController extends Controller
{
    protected WildberriesAnalyticsService $analyticsService;

    public function __construct(WildberriesAnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get sales funnel analytics
     */
    public function salesFunnel(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'nm_ids' => 'sometimes|array',
            'nm_ids.*' => 'integer',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();
        $nmIds = $validated['nm_ids'] ?? [];

        try {
            $funnel = $this->analyticsService->getSalesFunnel($account, $dateFrom, $dateTo, $nmIds);

            return response()->json([
                'success' => true,
                'funnel' => $funnel,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB sales funnel', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить воронку продаж: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get sales funnel history
     */
    public function salesFunnelHistory(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'nm_ids' => 'sometimes|array',
            'nm_ids.*' => 'integer',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();
        $nmIds = $validated['nm_ids'] ?? [];

        try {
            $history = $this->analyticsService->getSalesFunnelHistory($account, $dateFrom, $dateTo, $nmIds);

            return response()->json([
                'success' => true,
                'history' => $history,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB sales funnel history', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить историю воронки продаж: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get search report analytics
     */
    public function searchReport(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'nm_ids' => 'sometimes|array',
            'nm_ids.*' => 'integer',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();
        $nmIds = $validated['nm_ids'] ?? [];

        try {
            $searchReport = $this->analyticsService->getSearchReport($account, $dateFrom, $dateTo, $nmIds);

            return response()->json([
                'success' => true,
                'search_report' => $searchReport,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB search report', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить отчёт по поиску: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get product search texts
     */
    public function productSearchTexts(Request $request, MarketplaceAccount $account, int $nmId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();

        try {
            $searchTexts = $this->analyticsService->getProductSearchTexts($account, $nmId, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'nm_id' => $nmId,
                'search_texts' => $searchTexts,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB product search texts', [
                'account_id' => $account->id,
                'nm_id' => $nmId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить поисковые запросы товара: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get stocks report
     */
    public function stocksReport(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();

        try {
            $stocksReport = $this->analyticsService->getStocksReportGroups($account, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'stocks_report' => $stocksReport,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB stocks report', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить отчёт по остаткам: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get antifraud details (self-buyout detection)
     */
    public function antifraud(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();

        try {
            $antifraud = $this->analyticsService->getAntifraudDetails($account, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'antifraud' => $antifraud,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB antifraud details', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить данные антифрода: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get blocked products
     */
    public function blockedProducts(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $blocked = $this->analyticsService->getBlockedProducts($account);

            return response()->json([
                'success' => true,
                'blocked_products' => $blocked,
                'count' => count($blocked),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB blocked products', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить список заблокированных товаров: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get comprehensive dashboard data
     */
    public function dashboard(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();

        try {
            $dashboard = $this->analyticsService->getDashboardData($account, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'dashboard' => $dashboard,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB dashboard data', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить данные дашборда: ' . $e->getMessage(),
            ], 500);
        }
    }
}
