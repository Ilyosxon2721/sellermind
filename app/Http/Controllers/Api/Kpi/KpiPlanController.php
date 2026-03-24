<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Kpi\KpiPlan;
use App\Services\Kpi\KpiAiService;
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
        private readonly KpiAiService $kpiAiService,
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

        // Проверяем сумму весов = 100
        $weightSum = ($validated['weight_revenue'] ?? 0) + ($validated['weight_margin'] ?? 0) + ($validated['weight_orders'] ?? 0);
        if ($weightSum !== 100) {
            return $this->errorResponse(
                "Сумма весов должна быть 100 (сейчас: {$weightSum})",
                'invalid_weights',
                null,
                422
            );
        }

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

        // Проверяем сумму весов при обновлении
        $weightRevenue = $validated['weight_revenue'] ?? $plan->weight_revenue;
        $weightMargin = $validated['weight_margin'] ?? $plan->weight_margin;
        $weightOrders = $validated['weight_orders'] ?? $plan->weight_orders;
        $weightSum = $weightRevenue + $weightMargin + $weightOrders;
        if ($weightSum !== 100) {
            return $this->errorResponse(
                "Сумма весов должна быть 100 (сейчас: {$weightSum})",
                'invalid_weights',
                null,
                422
            );
        }

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

    /**
     * ИИ-рекомендация KPI-плана на основе исторических данных
     */
    public function aiSuggest(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $userId = $request->user()->id;

        $validated = $request->validate([
            'employee_id' => 'required|integer|exists:employees,id',
            'kpi_sales_sphere_id' => 'required|integer|exists:kpi_sales_spheres,id',
            'period_year' => 'required|integer|min:2020|max:2100',
            'period_month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $suggestion = $this->kpiAiService->suggestPlan(
                $companyId,
                $userId,
                $validated['employee_id'],
                $validated['kpi_sales_sphere_id'],
                $validated['period_year'],
                $validated['period_month'],
            );

            return $this->successResponse($suggestion);
        } catch (\Exception $e) {
            return $this->errorResponse(
                'Не удалось получить ИИ-рекомендацию: '.$e->getMessage(),
                'ai_error',
                null,
                500
            );
        }
    }

    /**
     * Исторические данные KPI для графиков (последние N месяцев)
     */
    public function chartData(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'months' => 'sometimes|integer|min:3|max:24',
            'employee_id' => 'sometimes|integer|exists:employees,id',
            'sphere_id' => 'sometimes|integer|exists:kpi_sales_spheres,id',
        ]);

        $months = $validated['months'] ?? 6;
        $employeeId = $validated['employee_id'] ?? null;
        $sphereId = $validated['sphere_id'] ?? null;

        $monthNames = [
            1 => 'Янв', 2 => 'Фев', 3 => 'Мар', 4 => 'Апр',
            5 => 'Май', 6 => 'Июн', 7 => 'Июл', 8 => 'Авг',
            9 => 'Сен', 10 => 'Окт', 11 => 'Ноя', 12 => 'Дек',
        ];

        $labels = [];
        $achievements = [];
        $bonuses = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $year = $date->year;
            $month = $date->month;

            $query = KpiPlan::byCompany($companyId)
                ->forPeriod($year, $month)
                ->where('status', '!=', KpiPlan::STATUS_CANCELLED);

            if ($employeeId) {
                $query->forEmployee($employeeId);
            }

            if ($sphereId) {
                $query->where('kpi_sales_sphere_id', $sphereId);
            }

            $plans = $query->get();

            $labels[] = ($monthNames[$month] ?? '').' '.$year;
            $achievements[] = $plans->count() > 0 ? round($plans->avg('achievement_percent'), 1) : 0;
            $bonuses[] = round($plans->sum('bonus_amount'), 0);
        }

        return $this->successResponse([
            'labels' => $labels,
            'achievements' => $achievements,
            'bonuses' => $bonuses,
        ]);
    }
}
