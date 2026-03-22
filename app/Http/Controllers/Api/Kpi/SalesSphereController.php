<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kpi;

use App\Http\Controllers\Controller;
use App\Models\Kpi\SalesSphere;
use App\Models\MarketplaceAccount;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * CRUD для сфер продаж
 */
final class SalesSphereController extends Controller
{
    use ApiResponder;

    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $spheres = SalesSphere::byCompany($companyId)
            ->with('marketplaceAccount:id,name,marketplace')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return $this->successResponse($spheres);
    }

    public function store(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'marketplace_account_id' => 'nullable|integer|exists:marketplace_accounts,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $validated['company_id'] = $companyId;
        $validated['code'] = $validated['code'] ?? Str::slug($validated['name']);

        // Проверяем уникальность кода в рамках компании
        $exists = SalesSphere::byCompany($companyId)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return $this->errorResponse('Сфера с таким кодом уже существует', 'duplicate', 'code', 422);
        }

        $sphere = SalesSphere::create($validated);

        return $this->successResponse($sphere->load('marketplaceAccount:id,name,marketplace'));
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $sphere = SalesSphere::byCompany($companyId)
            ->with('marketplaceAccount:id,name,marketplace')
            ->findOrFail($id);

        return $this->successResponse($sphere);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $sphere = SalesSphere::byCompany($companyId)->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'marketplace_account_id' => 'nullable|integer|exists:marketplace_accounts,id',
            'is_active' => 'boolean',
            'sort_order' => 'integer|min:0',
        ]);

        $sphere->update($validated);

        return $this->successResponse($sphere->fresh()->load('marketplaceAccount:id,name,marketplace'));
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $sphere = SalesSphere::byCompany($companyId)->findOrFail($id);

        // Проверяем нет ли активных планов
        if ($sphere->plans()->whereIn('status', ['active', 'calculated'])->exists()) {
            return $this->errorResponse('Нельзя удалить сферу с активными KPI-планами', 'has_plans', null, 422);
        }

        $sphere->delete();

        return $this->successResponse(['message' => 'Сфера удалена']);
    }

    /**
     * Список маркетплейс-аккаунтов компании для привязки к сфере
     */
    public function marketplaceAccounts(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->select(['id', 'name', 'marketplace'])
            ->orderBy('name')
            ->get();

        return $this->successResponse($accounts);
    }
}
