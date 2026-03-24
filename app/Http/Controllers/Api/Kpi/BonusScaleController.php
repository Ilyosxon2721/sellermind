<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kpi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kpi\StoreBonusScaleRequest;
use App\Http\Requests\Kpi\UpdateBonusScaleRequest;
use App\Models\Kpi\BonusScale;
use App\Models\Kpi\KpiPlan;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * CRUD для шкал бонусов
 */
final class BonusScaleController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $perPage = min((int) $request->get('per_page', 50), 100);

        $scales = BonusScale::byCompany($companyId)
            ->with('tiers')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->paginate($perPage);

        return $this->successResponse($scales->items(), [
            'current_page' => $scales->currentPage(),
            'last_page' => $scales->lastPage(),
            'per_page' => $scales->perPage(),
            'total' => $scales->total(),
        ]);
    }

    public function store(StoreBonusScaleRequest $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validated();

        return DB::transaction(function () use ($companyId, $validated) {
            // Если это дефолтная, снимаем флаг с других
            if (! empty($validated['is_default'])) {
                BonusScale::byCompany($companyId)->update(['is_default' => false]);
            }

            $scale = BonusScale::create([
                'company_id' => $companyId,
                'name' => $validated['name'],
                'is_default' => $validated['is_default'] ?? false,
            ]);

            foreach ($validated['tiers'] as $tierData) {
                $scale->tiers()->create($tierData);
            }

            return $this->successResponse($scale->load('tiers'));
        });
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $scale = BonusScale::byCompany($companyId)
            ->with('tiers')
            ->findOrFail($id);

        return $this->successResponse($scale);
    }

    public function update(UpdateBonusScaleRequest $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $scale = BonusScale::byCompany($companyId)->findOrFail($id);

        $validated = $request->validated();

        return DB::transaction(function () use ($companyId, $scale, $validated) {
            if (isset($validated['is_default']) && $validated['is_default']) {
                BonusScale::byCompany($companyId)
                    ->where('id', '!=', $scale->id)
                    ->update(['is_default' => false]);
            }

            $scale->update(collect($validated)->only(['name', 'is_default'])->toArray());

            // Пересоздаём ступени если переданы
            if (isset($validated['tiers'])) {
                $scale->tiers()->delete();
                foreach ($validated['tiers'] as $tierData) {
                    $scale->tiers()->create($tierData);
                }
            }

            return $this->successResponse($scale->fresh()->load('tiers'));
        });
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $scale = BonusScale::byCompany($companyId)->findOrFail($id);

        // Проверяем нет ли активных планов с этой шкалой
        $hasActivePlans = KpiPlan::byCompany($companyId)
            ->where('kpi_bonus_scale_id', $id)
            ->whereIn('status', [KpiPlan::STATUS_ACTIVE, KpiPlan::STATUS_CALCULATED])
            ->exists();

        if ($hasActivePlans) {
            return $this->errorResponse('Нельзя удалить шкалу с активными KPI-планами', 'has_plans', null, 422);
        }

        $scale->delete();

        return $this->successResponse(['message' => 'Шкала удалена']);
    }
}
