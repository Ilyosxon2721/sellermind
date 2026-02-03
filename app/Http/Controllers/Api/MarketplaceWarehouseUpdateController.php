<?php

// file: app/Http/Controllers/Api/MarketplaceWarehouseUpdateController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceWarehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceWarehouseUpdateController extends Controller
{
    public function update(Request $request, MarketplaceWarehouse $warehouse): JsonResponse
    {
        $data = $request->validate([
            'is_active' => ['nullable', 'boolean'],
            'local_warehouse_id' => ['nullable', 'integer'],
        ]);

        if (! $request->user()->hasCompanyAccess($warehouse->account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $warehouse->update($data);

        return response()->json([
            'message' => 'Склад обновлён',
            'warehouse' => $warehouse,
        ]);
    }
}
