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
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $warehouses = MarketplaceWarehouse::where('marketplace_account_id', $account->id)
            ->orderBy('name')
            ->get();

        return response()->json(['warehouses' => $warehouses]);
    }

    public function sync(Request $request, MarketplaceAccount $account, WildberriesStockService $service): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        // Check if account has required token
        $hasMarketplaceToken = ! empty($account->wb_marketplace_token);
        $hasApiKey = ! empty($account->api_key);

        \Log::info('WB warehouse sync started', [
            'account_id' => $account->id,
            'has_marketplace_token' => $hasMarketplaceToken,
            'has_api_key' => $hasApiKey,
        ]);

        // First, try syncing via WildberriesStockService (includes fallback to extractWarehousesFromStocks)
        $syncResult = $service->syncWarehouses($account);

        // Also update MarketplaceWarehouse records for mapping purposes
        $list = $service->getWarehouses($account);

        $created = 0;
        foreach ($list as $item) {
            $warehouseId = $item['id'] ?? null;
            if (! $warehouseId) {
                continue;
            }

            $mw = MarketplaceWarehouse::updateOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'marketplace_warehouse_id' => $warehouseId,
                ],
                [
                    'wildberries_warehouse_id' => $warehouseId,
                    'name' => $item['name'] ?? ('WB склад '.$warehouseId),
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
        $errors = $syncResult['errors'] ?? [];

        // If no warehouses from direct API call, check syncResult for fallback data
        if ($totalCount === 0 && ($syncResult['updated'] ?? 0) > 0) {
            $totalCount = $syncResult['updated'];
            $note = $syncResult['note'] ?? '';
        }

        // Provide helpful message if no warehouses found
        if ($totalCount === 0 && empty($syncResult['created']) && empty($syncResult['updated'])) {
            if (! $hasMarketplaceToken && ! $hasApiKey) {
                $errors[] = 'Не настроен токен API. Укажите wb_marketplace_token или api_key в настройках аккаунта.';
            } elseif (! $hasMarketplaceToken) {
                $note = 'Используется общий api_key. Для FBS складов рекомендуется настроить отдельный wb_marketplace_token.';
            }
        }

        \Log::info('WB warehouse sync completed', [
            'account_id' => $account->id,
            'total_count' => $totalCount,
            'created' => $created + ($syncResult['created'] ?? 0),
            'updated' => $syncResult['updated'] ?? 0,
            'errors_count' => count($errors),
        ]);

        return response()->json([
            'message' => $totalCount > 0 ? 'Склады синхронизированы' : 'Склады не найдены',
            'count' => $totalCount,
            'created' => $created + ($syncResult['created'] ?? 0),
            'updated' => $syncResult['updated'] ?? 0,
            'errors' => $errors,
            'note' => $note,
            'debug' => [
                'has_marketplace_token' => $hasMarketplaceToken,
                'has_api_key' => $hasApiKey,
            ],
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
