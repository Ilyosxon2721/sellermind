<?php
// file: app/Http/Controllers/Api/MarketplaceInsightsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Marketplaces\MarketplaceInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceInsightsController extends Controller
{
    public function __construct(
        protected MarketplaceInsightsService $insightsService
    ) {}

    /**
     * Get marketplace insights summary
     */
    public function summary(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'period_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'period_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:period_from'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $periodFrom = $request->input('period_from', now()->subDays(30)->toDateString());
        $periodTo = $request->input('period_to', now()->toDateString());

        $summary = $this->insightsService->getSummaryForPeriod(
            $request->company_id,
            $periodFrom,
            $periodTo
        );

        return response()->json($summary);
    }

    /**
     * Get problem SKUs
     */
    public function problems(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $accountIds = \App\Models\MarketplaceAccount::where('company_id', $request->company_id)
            ->pluck('id');

        $problems = $this->insightsService->getProblemSkus($accountIds);

        return response()->json([
            'problems' => $problems,
            'total' => count($problems),
        ]);
    }

    /**
     * Get AI-ready insights for agent
     */
    public function forAgent(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $insights = $this->insightsService->getInsightsForAgent($request->company_id);

        return response()->json($insights);
    }

    /**
     * Get recommendations
     */
    public function recommendations(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $recommendations = $this->insightsService->getRecommendations($request->company_id);

        return response()->json([
            'recommendations' => $recommendations,
            'total' => count($recommendations),
        ]);
    }

    /**
     * Get text summary for AI
     */
    public function textSummary(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'period_from' => ['nullable', 'date', 'date_format:Y-m-d'],
            'period_to' => ['nullable', 'date', 'date_format:Y-m-d', 'after_or_equal:period_from'],
        ]);

        if (!$request->user()->hasCompanyAccess($request->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $periodFrom = $request->input('period_from', now()->subDays(7)->toDateString());
        $periodTo = $request->input('period_to', now()->toDateString());

        $text = $this->insightsService->generateTextSummary(
            $request->company_id,
            $periodFrom,
            $periodTo
        );

        return response()->json([
            'summary' => $text,
            'period' => [
                'from' => $periodFrom,
                'to' => $periodTo,
            ],
        ]);
    }
}
