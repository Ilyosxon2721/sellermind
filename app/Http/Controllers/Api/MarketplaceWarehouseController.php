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

        $list = $service->getWarehouses($account);

        $created = 0;
        foreach ($list as $item) {
            $mw = MarketplaceWarehouse::updateOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'wildberries_warehouse_id' => $item['id'] ?? null,
                ],
                [
                    'name' => $item['name'] ?? ($item['officeId'] ?? 'WB склад'),
                    'type' => $item['type'] ?? 'FBS',
                    'is_active' => true,
                ]
            );
            if ($mw->wasRecentlyCreated) {
                $created++;
            }
        }

        return response()->json([
            'message' => 'Склады обновлены',
            'count' => count($list),
            'created' => $created,
        ]);
    }
}
