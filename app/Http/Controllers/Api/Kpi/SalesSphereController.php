<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Kpi;

use App\Http\Controllers\Controller;
use App\Http\Requests\Kpi\StoreSalesSphereRequest;
use App\Http\Requests\Kpi\UpdateSalesSphereRequest;
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

        $perPage = min((int) $request->get('per_page', 50), 100);

        $spheres = SalesSphere::byCompany($companyId)
            ->with('marketplaceAccount:id,name,marketplace')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage);

        // Подгружаем названия привязанных аккаунтов для мульти-привязки
        $items = collect($spheres->items());
        $allAccountIds = $items->pluck('marketplace_account_ids')->flatten()->filter()->unique()->values()->toArray();
        $accountsMap = [];
        if (! empty($allAccountIds)) {
            $accountsMap = MarketplaceAccount::whereIn('id', $allAccountIds)
                ->get(['id', 'name', 'marketplace'])
                ->keyBy('id')
                ->toArray();
        }

        $items->each(function ($sphere) use ($accountsMap) {
            $ids = $sphere->marketplace_account_ids ?? [];
            $sphere->linked_accounts = array_values(array_filter(
                array_map(fn ($id) => $accountsMap[$id] ?? null, $ids)
            ));
        });

        return $this->successResponse($spheres->items(), [
            'current_page' => $spheres->currentPage(),
            'last_page' => $spheres->lastPage(),
            'per_page' => $spheres->perPage(),
            'total' => $spheres->total(),
        ]);
    }

    public function store(StoreSalesSphereRequest $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $validated = $request->validated();

        $validated['company_id'] = $companyId;
        $validated['code'] = $validated['code'] ?? Str::slug($validated['name']);

        // Синхронизируем старое поле с новым (первый ID)
        if (! empty($validated['marketplace_account_ids'])) {
            $validated['marketplace_account_id'] = $validated['marketplace_account_ids'][0];
        }

        // Проверяем уникальность кода в рамках компании
        $exists = SalesSphere::byCompany($companyId)
            ->where('code', $validated['code'])
            ->exists();

        if ($exists) {
            return $this->errorResponse('Сфера с таким кодом уже существует', 'duplicate', 'code', 422);
        }

        $sphere = SalesSphere::create($validated);

        return $this->successResponse($sphere);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $sphere = SalesSphere::byCompany($companyId)
            ->with('marketplaceAccount:id,name,marketplace')
            ->findOrFail($id);

        return $this->successResponse($sphere);
    }

    public function update(UpdateSalesSphereRequest $request, int $id): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $sphere = SalesSphere::byCompany($companyId)->findOrFail($id);

        $validated = $request->validated();

        // Синхронизируем старое поле с новым
        if (array_key_exists('marketplace_account_ids', $validated)) {
            $validated['marketplace_account_id'] = ! empty($validated['marketplace_account_ids'])
                ? $validated['marketplace_account_ids'][0]
                : null;
        }

        $sphere->update($validated);

        return $this->successResponse($sphere->fresh());
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
