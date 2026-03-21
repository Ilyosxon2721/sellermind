<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Kpi\BonusScale;
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

        $scales = BonusScale::byCompany($companyId)
            ->with('tiers')
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return $this->successResponse($scales);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_default' => 'boolean',
            'tiers' => 'required|array|min:1',
            'tiers.*.min_percent' => 'required|integer|min:0|max:300',
            'tiers.*.max_percent' => 'nullable|integer|min:1|max:300',
            'tiers.*.bonus_type' => 'required|string|in:fixed,percent_revenue,percent_margin',
            'tiers.*.bonus_value' => 'required|numeric|min:0',
        ]);

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

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $scale = BonusScale::byCompany($companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'is_default' => 'boolean',
            'tiers' => 'sometimes|array|min:1',
            'tiers.*.min_percent' => 'required_with:tiers|integer|min:0|max:300',
            'tiers.*.max_percent' => 'nullable|integer|min:1|max:300',
            'tiers.*.bonus_type' => 'required_with:tiers|string|in:fixed,percent_revenue,percent_margin',
            'tiers.*.bonus_value' => 'required_with:tiers|numeric|min:0',
        ]);

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

        $scale->delete();

        return $this->successResponse(['message' => 'Шкала удалена']);
    }
}
