<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Kpi\KpiPlan;
use App\Services\Kpi\KpiCalculationService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * CRUD для KPI-планов + расчёт + дашборд
 */
final class KpiPlanController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly KpiCalculationService $kpiService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $query = KpiPlan::byCompany($companyId)
            ->with(['employee', 'salesSphere', 'bonusScale']);

        if ($request->filled('year') && $request->filled('month')) {
            $query->forPeriod((int) $request->year, (int) $request->month);
        }

        if ($request->filled('employee_id')) {
            $query->forEmployee((int) $request->employee_id);
        }

        if ($request->filled('sphere_id')) {
            $query->where('kpi_sales_sphere_id', $request->sphere_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $plans = $query->orderByDesc('period_year')
            ->orderByDesc('period_month')
            ->orderBy('employee_id')
            ->get();

        return $this->successResponse($plans);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'kpi_sales_sphere_id' => 'required|integer|exists:kpi_sales_spheres,id',
            'kpi_bonus_scale_id' => 'required|integer|exists:kpi_bonus_scales,id',
            'period_year' => 'required|integer|min:2020|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
            'target_revenue' => 'required|numeric|min:0',
            'target_margin' => 'required|numeric|min:0',
            'target_orders' => 'required|integer|min:0',
            'weight_revenue' => 'integer|min:0|max:100',
            'weight_margin' => 'integer|min:0|max:100',
            'weight_orders' => 'integer|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $validated['company_id'] = $companyId;

        // Проверяем уникальность
        $exists = KpiPlan::byCompany($companyId)
            ->forEmployee($validated['employee_id'])
            ->forPeriod($validated['period_year'], $validated['period_month'])
            ->where('kpi_sales_sphere_id', $validated['kpi_sales_sphere_id'])
            ->exists();

        if ($exists) {
            return $this->errorResponse(
                'KPI-план для этого сотрудника в данной сфере за этот период уже существует',
                'duplicate',
                null,
                422
            );
        }

        $plan = KpiPlan::create($validated);

        return $this->successResponse($plan->load(['employee', 'salesSphere', 'bonusScale']));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $plan = KpiPlan::byCompany($companyId)
            ->with(['employee', 'salesSphere', 'bonusScale.tiers', 'approvedByUser'])
            ->findOrFail($id);

        return $this->successResponse($plan);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $plan = KpiPlan::byCompany($companyId)->findOrFail($id);

        if ($plan->status === KpiPlan::STATUS_APPROVED) {
            return $this->errorResponse('Нельзя редактировать утверждённый план', 'invalid_state', null, 422);
        }

        $validated = $request->validate([
            'kpi_bonus_scale_id' => 'sometimes|integer|exists:kpi_bonus_scales,id',
            'target_revenue' => 'sometimes|numeric|min:0',
            'target_margin' => 'sometimes|numeric|min:0',
            'target_orders' => 'sometimes|integer|min:0',
            'weight_revenue' => 'integer|min:0|max:100',
            'weight_margin' => 'integer|min:0|max:100',
            'weight_orders' => 'integer|min:0|max:100',
            'notes' => 'nullable|string|max:1000',
        ]);

        $plan->update($validated);

        return $this->successResponse($plan->fresh()->load(['employee', 'salesSphere', 'bonusScale']));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $plan = KpiPlan::byCompany($companyId)->findOrFail($id);

        if ($plan->status === KpiPlan::STATUS_APPROVED) {
            return $this->errorResponse('Нельзя удалить утверждённый план', 'invalid_state', null, 422);
        }

        $plan->delete();

        return $this->successResponse(['message' => 'KPI-план удалён']);
    }

    /**
     * Ввести фактические данные вручную (для сфер без МП-привязки)
     */
    public function updateActuals(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $plan = KpiPlan::byCompany($companyId)->findOrFail($id);

        if ($plan->status === KpiPlan::STATUS_APPROVED) {
            return $this->errorResponse('Нельзя редактировать утверждённый план', 'invalid_state', null, 422);
        }

        $validated = $request->validate([
            'actual_revenue' => 'sometimes|numeric|min:0',
            'actual_margin' => 'sometimes|numeric|min:0',
            'actual_orders' => 'sometimes|integer|min:0',
        ]);

        $plan->update($validated);

        return $this->successResponse($plan->fresh()->load(['employee', 'salesSphere', 'bonusScale']));
    }

    /**
     * Массовый расчёт всех планов за период
     */
    public function calculate(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2100',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $plans = $this->kpiService->calculatePeriod(
            $companyId,
            $validated['year'],
            $validated['month']
        );

        return $this->successResponse([
            'calculated' => $plans->count(),
            'plans' => $plans->load(['employee', 'salesSphere']),
        ]);
    }

    /**
     * Утвердить KPI-план
     */
    public function approve(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $plan = KpiPlan::byCompany($companyId)->findOrFail($id);

        if ($plan->status !== KpiPlan::STATUS_CALCULATED) {
            return $this->errorResponse('Можно утвердить только рассчитанный план', 'invalid_state', null, 422);
        }

        $plan = $this->kpiService->approvePlan($plan, Auth::id());

        return $this->successResponse($plan->load(['employee', 'salesSphere', 'bonusScale', 'approvedByUser']));
    }

    /**
     * KPI дашборд — сводка за период
     */
    public function dashboard(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $data = $this->kpiService->getDashboard($companyId, $year, $month);

        return $this->successResponse($data);
    }
}
