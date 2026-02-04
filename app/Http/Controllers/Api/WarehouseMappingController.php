<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceWarehouse;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WarehouseMappingController extends Controller
{
    /**
     * Get all warehouse mappings for an account
     *
     * GET /api/marketplace/{account}/warehouse-mappings
     */
    public function index(MarketplaceAccount $account)
    {
        $mappings = MarketplaceWarehouse::where('marketplace_account_id', $account->id)
            ->with('localWarehouse')
            ->orderBy('name')
            ->get();

        return response()->json([
            'mappings' => $mappings,
        ]);
    }

    /**
     * Get available marketplace warehouses for mapping
     *
     * GET /api/marketplace/{account}/available-warehouses
     */
    public function availableWarehouses(MarketplaceAccount $account)
    {
        $warehouses = MarketplaceWarehouse::getAvailableWbWarehouses($account);

        return response()->json([
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Sync warehouses from marketplace API
     *
     * POST /api/marketplace/{account}/sync-warehouses
     */
    public function syncWarehouses(MarketplaceAccount $account)
    {
        $result = MarketplaceWarehouse::syncFromMarketplace($account);

        return response()->json([
            'success' => true,
            'synced' => $result['synced'],
            'errors' => $result['errors'],
            'message' => "Синхронизировано складов: {$result['synced']}",
        ]);
    }

    /**
     * Create or update a warehouse mapping
     *
     * POST /api/marketplace/{account}/warehouse-mappings
     */
    public function store(Request $request, MarketplaceAccount $account)
    {
        $validator = Validator::make($request->all(), [
            'marketplace_warehouse_id' => 'required|integer',
            'local_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        // Валидация: local_warehouse_id должен быть уникальным в рамках аккаунта
        if (! empty($data['local_warehouse_id'])) {
            $existingMapping = MarketplaceWarehouse::where('marketplace_account_id', $account->id)
                ->where('local_warehouse_id', $data['local_warehouse_id'])
                ->where('marketplace_warehouse_id', '!=', $data['marketplace_warehouse_id'])
                ->first();

            if ($existingMapping) {
                return response()->json([
                    'success' => false,
                    'message' => 'Этот внутренний склад уже привязан к другому складу маркетплейса',
                ], 422);
            }
        }

        $data['marketplace_account_id'] = $account->id;
        $data['is_active'] = true;

        $mapping = MarketplaceWarehouse::updateOrCreate(
            [
                'marketplace_account_id' => $account->id,
                'marketplace_warehouse_id' => $data['marketplace_warehouse_id'],
            ],
            $data
        );

        return response()->json([
            'success' => true,
            'mapping' => $mapping->load('localWarehouse'),
            'message' => 'Склад успешно привязан',
        ]);
    }

    /**
     * Update a warehouse mapping
     *
     * PUT /api/marketplace/{account}/warehouse-mappings/{mapping}
     */
    public function update(Request $request, MarketplaceAccount $account, MarketplaceWarehouse $mapping)
    {
        // Ensure the mapping belongs to this account
        if ($mapping->marketplace_account_id !== $account->id) {
            return response()->json([
                'success' => false,
                'message' => 'Mapping not found',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'local_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $mapping->update($validator->validated());

        return response()->json([
            'success' => true,
            'mapping' => $mapping->load('localWarehouse'),
            'message' => 'Маппинг обновлён',
        ]);
    }

    /**
     * Delete a warehouse mapping
     *
     * DELETE /api/marketplace/{account}/warehouse-mappings/{mapping}
     */
    public function destroy(MarketplaceAccount $account, MarketplaceWarehouse $mapping)
    {
        // Ensure the mapping belongs to this account
        if ($mapping->marketplace_account_id !== $account->id) {
            return response()->json([
                'success' => false,
                'message' => 'Mapping not found',
            ], 404);
        }

        $mapping->delete();

        return response()->json([
            'success' => true,
            'message' => 'Маппинг удалён',
        ]);
    }

    /**
     * Get local warehouses for dropdown
     *
     * GET /api/marketplace/warehouses/local
     */
    public function localWarehouses(Request $request)
    {
        $companyId = $request->input('company_id');

        if (! $companyId) {
            return response()->json(['warehouses' => []]);
        }

        // Check user has access to this company
        if (! $request->user()->hasCompanyAccess($companyId)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $warehouses = Warehouse::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        return response()->json([
            'warehouses' => $warehouses,
        ]);
    }
}
