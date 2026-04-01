<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Kpi\KpiPlan;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * CRUD филиалов + распределение KPI-плана филиала по сотрудникам
 */
final class BranchController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $branches = Branch::byCompany($companyId)
            ->with(['director:id,first_name,last_name,middle_name'])
            ->withCount('activeEmployees')
            ->orderBy('name')
            ->get();

        return $this->successResponse($branches);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('branches')->where('company_id', $companyId)],
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'director_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)],
        ]);

        $validated['company_id'] = $companyId;

        $branch = Branch::create($validated);

        return $this->successResponse($branch->load('director:id,first_name,last_name,middle_name'));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $branch = Branch::byCompany($companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'code' => ['nullable', 'string', 'max:50', Rule::unique('branches')->where('company_id', $companyId)->ignore($id)],
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:30',
            'director_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'is_active' => 'boolean',
        ]);

        $branch->update($validated);

        return $this->successResponse($branch->fresh()->load('director:id,first_name,last_name,middle_name'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $branch = Branch::byCompany($companyId)->findOrFail($id);

        if ($branch->employees()->exists()) {
            return $this->errorResponse('Нельзя удалить филиал с привязанными сотрудниками', 'has_employees', null, 422);
        }

        $branch->delete();

        return $this->successResponse(['message' => 'Филиал удалён']);
    }

    /**
     * Распределить план филиала по сотрудникам
     *
     * Принимает массив распределений: [{employee_id, target_revenue, target_margin, target_orders}]
     */
    public function distribute(Request $request, int $planId): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $branchPlan = KpiPlan::byCompany($companyId)
            ->where('plan_type', 'branch')
            ->findOrFail($planId);

        $validated = $request->validate([
            'distributions' => 'required|array|min:1',
            'distributions.*.employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'distributions.*.target_revenue' => 'required|numeric|min:0',
            'distributions.*.target_margin' => 'required|numeric|min:0',
            'distributions.*.target_orders' => 'required|integer|min:0',
        ]);

        $childPlans = DB::transaction(function () use ($branchPlan, $validated) {
            // Удалить старые дочерние планы (только черновики)
            $branchPlan->childPlans()
                ->where('status', KpiPlan::STATUS_ACTIVE)
                ->delete();

            $plans = [];
            foreach ($validated['distributions'] as $dist) {
                $plans[] = KpiPlan::create([
                    'company_id' => $branchPlan->company_id,
                    'employee_id' => $dist['employee_id'],
                    'branch_id' => $branchPlan->branch_id,
                    'parent_plan_id' => $branchPlan->id,
                    'plan_type' => 'employee',
                    'kpi_sales_sphere_id' => $branchPlan->kpi_sales_sphere_id,
                    'kpi_bonus_scale_id' => $branchPlan->kpi_bonus_scale_id,
                    'period_year' => $branchPlan->period_year,
                    'period_month' => $branchPlan->period_month,
                    'target_revenue' => $dist['target_revenue'],
                    'target_margin' => $dist['target_margin'],
                    'target_orders' => $dist['target_orders'],
                    'weight_revenue' => $branchPlan->weight_revenue,
                    'weight_margin' => $branchPlan->weight_margin,
                    'weight_orders' => $branchPlan->weight_orders,
                    'currency' => $branchPlan->currency,
                    'status' => KpiPlan::STATUS_ACTIVE,
                ]);
            }

            return $plans;
        });

        return $this->successResponse([
            'branch_plan_id' => $branchPlan->id,
            'distributed_count' => count($childPlans),
            'distribution_percent' => $branchPlan->fresh()->distribution_percent,
            'plans' => $childPlans,
        ]);
    }
}
