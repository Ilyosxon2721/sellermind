<?php
// file: app/Http/Controllers/Api/MarketplaceWarehouseController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceWarehouse;
use App\Services\Marketplaces\Wildberries\WildberriesStockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceWarehouseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:marketplace_accounts,id'],
        ]);

        $account = MarketplaceAccount::findOrFail($data['account_id']);
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $warehouses = MarketplaceWarehouse::where('marketplace_account_id', $account->id)
            ->orderBy('name')
            ->get();

        return response()->json(['warehouses' => $warehouses]);
    }

    public function sync(Request $request, MarketplaceAccount $account, WildberriesStockService $service): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        // First, try syncing via WildberriesStockService (includes fallback to extractWarehousesFromStocks)
        $syncResult = $service->syncWarehouses($account);

        // Also update MarketplaceWarehouse records for mapping purposes
        $list = $service->getWarehouses($account);

        $created = 0;
        foreach ($list as $item) {
            $warehouseId = $item['id'] ?? null;
            if (!$warehouseId) {
                continue;
            }

            $mw = MarketplaceWarehouse::updateOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'marketplace_warehouse_id' => $warehouseId,
                ],
                [
                    'wildberries_warehouse_id' => $warehouseId,
                    'name' => $item['name'] ?? ('WB склад ' . $warehouseId),
                    'type' => $this->getWarehouseType($item['deliveryType'] ?? null),
                    'is_active' => true,
                ]
            );
            if ($mw->wasRecentlyCreated) {
                $created++;
            }
        }

        $totalCount = count($list);
        $note = '';

        // If no warehouses from direct API call, check syncResult for fallback data
        if ($totalCount === 0 && ($syncResult['updated'] ?? 0) > 0) {
            $totalCount = $syncResult['updated'];
            $note = $syncResult['note'] ?? '';
        }

        return response()->json([
            'message' => 'Склады синхронизированы',
            'count' => $totalCount,
            'created' => $created + ($syncResult['created'] ?? 0),
            'updated' => $syncResult['updated'] ?? 0,
            'errors' => $syncResult['errors'] ?? [],
            'note' => $note,
        ]);
    }

    /**
     * Get warehouse type from WB deliveryType
     */
    protected function getWarehouseType(?int $deliveryType): string
    {
        return match ($deliveryType) {
            1 => 'FBS',
            2 => 'DBS',
            6 => 'EDBS',
            5 => 'C&C',
            default => 'FBS',
        };
    }
}
