<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\SalesFunnel;
use App\Services\SalesFunnelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SalesFunnelController extends Controller
{
    use HasCompanyScope;

    public function __construct(
        private readonly SalesFunnelService $funnelService,
    ) {}

    /**
     * Список сохранённых воронок
     */
    public function index(): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $funnels = SalesFunnel::byCompany($companyId)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (SalesFunnel $f) => [
                'id' => $f->id,
                'name' => $f->name,
                'is_auto' => $f->is_auto,
                'currency' => $f->currency,
                'source_filter' => $f->source_filter,
                'funnel' => $f->calculateFunnel(),
                'created_at' => $f->created_at->toDateTimeString(),
            ]);

        return response()->json(['data' => $funnels]);
    }

    /**
     * Просмотр конкретной воронки
     */
    public function show(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $funnel = SalesFunnel::byCompany($companyId)->findOrFail($id);

        return response()->json([
            'data' => $funnel,
            'funnel' => $funnel->calculateFunnel(),
        ]);
    }

    /**
     * Сохранить новую воронку
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'views' => 'integer|min:0',
            'inquiry_rate' => 'numeric|min:0|max:100',
            'meeting_rate' => 'numeric|min:0|max:100',
            'sale_rate' => 'numeric|min:0|max:100',
            'average_check' => 'numeric|min:0',
            'profit_margin' => 'numeric|min:0|max:100',
            'bonus_rate' => 'numeric|min:0|max:100',
            'currency' => 'string|size:3',
            'is_auto' => 'boolean',
            'source_filter' => 'nullable|array',
            'source_filter.*' => 'string|in:wb,ozon,uzum,ym,manual,retail,wholesale,direct',
            'period_from' => 'nullable|date',
            'period_to' => 'nullable|date|after_or_equal:period_from',
        ]);

        $funnel = $this->funnelService->save($companyId, $validated);

        return response()->json([
            'data' => $funnel,
            'funnel' => $funnel->calculateFunnel(),
        ], 201);
    }

    /**
     * Обновить воронку
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $funnel = SalesFunnel::byCompany($companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'string|max:255',
            'views' => 'integer|min:0',
            'inquiry_rate' => 'numeric|min:0|max:100',
            'meeting_rate' => 'numeric|min:0|max:100',
            'sale_rate' => 'numeric|min:0|max:100',
            'average_check' => 'numeric|min:0',
            'profit_margin' => 'numeric|min:0|max:100',
            'bonus_rate' => 'numeric|min:0|max:100',
            'currency' => 'string|size:3',
            'is_auto' => 'boolean',
            'source_filter' => 'nullable|array',
            'source_filter.*' => 'string|in:wb,ozon,uzum,ym,manual,retail,wholesale,direct',
            'period_from' => 'nullable|date',
            'period_to' => 'nullable|date|after_or_equal:period_from',
        ]);

        $funnel = $this->funnelService->update($funnel, $validated);

        return response()->json([
            'data' => $funnel,
            'funnel' => $funnel->calculateFunnel(),
        ]);
    }

    /**
     * Удалить воронку
     */
    public function destroy(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $funnel = SalesFunnel::byCompany($companyId)->findOrFail($id);
        $funnel->delete();

        return response()->json(['message' => 'Воронка удалена']);
    }

    /**
     * Расчёт воронки на лету (без сохранения)
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'views' => 'required|integer|min:0',
            'inquiry_rate' => 'required|numeric|min:0|max:100',
            'meeting_rate' => 'required|numeric|min:0|max:100',
            'sale_rate' => 'required|numeric|min:0|max:100',
            'average_check' => 'required|numeric|min:0',
            'profit_margin' => 'required|numeric|min:0|max:100',
            'bonus_rate' => 'required|numeric|min:0|max:100',
            'currency' => 'string|size:3',
        ]);

        $funnel = $this->funnelService->calculateManual($validated);

        return response()->json(['data' => $funnel]);
    }

    /**
     * Авто-воронка из реальных данных
     */
    public function auto(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');
        $sourceFilter = $request->input('source_filter', []);

        $cacheKey = "sales_funnel_auto_{$companyId}_{$period}_" . md5(json_encode($sourceFilter));

        $result = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($companyId, $period, $sourceFilter) {
            return $this->funnelService->calculateAuto($companyId, $period, $sourceFilter);
        });

        return response()->json(['data' => $result]);
    }

    /**
     * Разбивка по источникам
     */
    public function sourceBreakdown(Request $request): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $period = $request->input('period', '30days');

        $cacheKey = "sales_funnel_sources_{$companyId}_{$period}";

        $result = Cache::remember($cacheKey, now()->addMinutes(15), function () use ($companyId, $period) {
            return $this->funnelService->getSourcesBreakdown($companyId, $period);
        });

        return response()->json(['data' => $result]);
    }

    /**
     * Обновить авто-воронку (пересчитать snapshot)
     */
    public function refresh(int $id): JsonResponse
    {
        $companyId = $this->getCompanyId();
        $funnel = SalesFunnel::byCompany($companyId)->findOrFail($id);

        $snapshot = $this->funnelService->refreshAutoFunnel($funnel);

        return response()->json([
            'data' => $funnel->fresh(),
            'snapshot' => $snapshot,
        ]);
    }
}
