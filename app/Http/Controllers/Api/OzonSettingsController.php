<?php
// file: app/Http/Controllers/Api/OzonSettingsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\OzonWarehouse;
use App\Services\Marketplaces\OzonClient;
use App\Services\Marketplaces\MarketplaceHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OzonSettingsController extends Controller
{
    /**
     * Get Ozon account settings
     */
    public function show(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        // Use credentials_json for warehouse settings
        $settings = $account->credentials_json ?? [];

        return response()->json([
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'marketplace' => $account->marketplace,
                'is_active' => $account->is_active,
                // Credentials status (not the actual values for security)
                'credentials' => [
                    'client_id' => !empty($account->client_id),
                    'api_key' => !empty($account->api_key),
                ],
                // Stock sync settings from credentials_json
                'settings' => [
                    'stock_sync_mode' => $settings['stock_sync_mode'] ?? 'basic',
                    'warehouse_id' => $settings['warehouse_id'] ?? null,
                    'source_warehouse_ids' => $settings['source_warehouse_ids'] ?? [],
                ],
            ],
        ]);
    }

    /**
     * Update Ozon account credentials and settings
     */
    public function update(Request $request, MarketplaceAccount $account): JsonResponse
    {
        \Log::info('Ozon Settings Update Request', [
            'account_id' => $account->id,
            'user_id' => $request->user()?->id,
            'has_client_id' => $request->has('client_id'),
            'has_api_key' => $request->has('api_key'),
        ]);

        if (!$request->user()->isOwnerOf($account->company_id)) {
            return response()->json(['message' => 'Только владелец может изменять настройки.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        $validated = $request->validate([
            'client_id' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:4000'],
            'stock_sync_mode' => ['nullable', 'in:basic,aggregated'],
            'warehouse_id' => ['nullable', 'string'], // Ozon warehouse ID (for basic mode)
            'source_warehouse_ids' => ['nullable', 'array'], // Local warehouse IDs (for aggregated mode)
            'source_warehouse_ids.*' => ['integer'],
        ]);

        // Update credentials in dedicated database fields
        if (array_key_exists('client_id', $validated)) {
            $value = $validated['client_id'];
            $account->client_id = $value === '' ? null : $value;
        }

        if (array_key_exists('api_key', $validated)) {
            $value = $validated['api_key'];
            $account->api_key = $value === '' ? null : $value;
        }

        // Update stock sync settings in credentials_json
        $settings = $account->credentials_json ?? [];

        if (array_key_exists('stock_sync_mode', $validated)) {
            $settings['stock_sync_mode'] = $validated['stock_sync_mode'];
            \Log::info('Ozon Settings: Updating stock_sync_mode', ['mode' => $validated['stock_sync_mode']]);
        }

        if (array_key_exists('warehouse_id', $validated)) {
            $settings['warehouse_id'] = $validated['warehouse_id'];
            \Log::info('Ozon Settings: Updating warehouse_id', ['warehouse_id' => $validated['warehouse_id']]);
        }

        if (array_key_exists('source_warehouse_ids', $validated)) {
            $settings['source_warehouse_ids'] = $validated['source_warehouse_ids'];
            \Log::info('Ozon Settings: Updating source_warehouse_ids', ['ids' => $validated['source_warehouse_ids']]);
        }

        // Save settings to credentials_json
        $account->credentials_json = $settings;
        $account->save();

        \Log::info('Ozon Settings: Settings updated successfully', ['account_id' => $account->id]);

        return response()->json([
            'message' => 'Настройки обновлены.',
            'account' => [
                'id' => $account->id,
                'credentials' => [
                    'client_id' => !empty($account->client_id),
                    'api_key' => !empty($account->api_key),
                ],
                'settings' => [
                    'stock_sync_mode' => $settings['stock_sync_mode'] ?? 'basic',
                    'warehouse_id' => $settings['warehouse_id'] ?? null,
                    'source_warehouse_ids' => $settings['source_warehouse_ids'] ?? [],
                ],
            ],
        ]);
    }

    /**
     * Test Ozon API connection
     */
    public function test(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        $httpClient = app(MarketplaceHttpClient::class);
        $client = new OzonClient($httpClient);

        $startTime = microtime(true);
        $pingResult = $client->ping($account);
        $duration = round((microtime(true) - $startTime) * 1000);

        return response()->json([
            'success' => $pingResult['success'],
            'message' => $pingResult['message'],
            'response_time_ms' => $duration,
            'data' => $pingResult['data'] ?? null,
        ]);
    }

    /**
     * Get Ozon warehouses
     */
    public function getWarehouses(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        try {
            $httpClient = app(MarketplaceHttpClient::class);
            $client = new OzonClient($httpClient);
            
            $warehouses = $client->getWarehouses($account);

            // Sync warehouses to local DB
            foreach ($warehouses as $whData) {
                OzonWarehouse::updateOrCreate(
                    [
                        'marketplace_account_id' => $account->id,
                        'warehouse_id' => $whData['warehouse_id'],
                    ],
                    [
                        'name' => $whData['name'] ?? 'Unknown',
                        'type' => $whData['type'] ?? null,
                        'is_active' => $whData['is_active'] ?? true,
                        'has_entrusting' => $whData['has_entrusting'] ?? false,
                        'can_print_act_in_advance' => $whData['can_print_act_in_advance'] ?? false,
                        'metadata' => $whData,
                    ]
                );
            }

            return response()->json([
                'success' => true,
                'warehouses' => $warehouses,
            ]);
        } catch (\Exception $e) {
            \Log::error('Ozon: Failed to fetch warehouses', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка при загрузке складов: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get warehouse mapping settings
     */
    public function getWarehouseMapping(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        // Use credentials_json field directly instead of getDecryptedCredentials()
        $settings = $account->credentials_json ?? [];

        \Log::info('Ozon: Getting warehouse mapping', [
            'account_id' => $account->id,
            'settings' => $settings,
        ]);

        return response()->json([
            'stock_sync_mode' => $settings['stock_sync_mode'] ?? 'basic',
            'warehouse_id' => $settings['warehouse_id'] ?? null,
            'source_warehouse_ids' => $settings['source_warehouse_ids'] ?? [],
        ]);
    }

    /**
     * Save warehouse mapping settings
     */
    public function saveWarehouseMapping(Request $request, MarketplaceAccount $account): JsonResponse
    {
        \Log::info('Ozon: Saving warehouse mapping - Start', [
            'account_id' => $account->id,
            'user_id' => $request->user()?->id,
            'request_data' => $request->all(),
        ]);

        if (!$request->user()->isOwnerOf($account->company_id)) {
            return response()->json(['message' => 'Только владелец может изменять настройки.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        $validated = $request->validate([
            'stock_sync_mode' => ['required', 'in:basic,aggregated'],
            'warehouse_id' => ['nullable', 'string'], // Ozon warehouse ID
            'source_warehouse_ids' => ['nullable', 'array'], // Local warehouse IDs
            'source_warehouse_ids.*' => ['integer'],
        ]);

        \Log::info('Ozon: Validated warehouse mapping data', [
            'validated' => $validated,
        ]);

        // Get existing credentials_json settings (not the encrypted credentials field)
        $settings = $account->credentials_json ?? [];

        \Log::info('Ozon: Current settings before update', [
            'current_settings' => $settings,
        ]);

        // Update warehouse mapping settings
        $settings['stock_sync_mode'] = $validated['stock_sync_mode'];
        $settings['warehouse_id'] = $validated['warehouse_id'] ?? null;
        $settings['source_warehouse_ids'] = $validated['source_warehouse_ids'] ?? [];

        \Log::info('Ozon: New settings to be saved', [
            'new_settings' => $settings,
        ]);

        // Save to credentials_json field
        $account->credentials_json = $settings;
        $saved = $account->save();

        \Log::info('Ozon: Save operation completed', [
            'save_result' => $saved,
            'account_id' => $account->id,
        ]);

        // Verify the save by reloading from DB
        $account->refresh();
        $verifySettings = $account->credentials_json;

        \Log::info('Ozon: Verification after save', [
            'verified_settings' => $verifySettings,
            'stock_sync_mode_matches' => ($verifySettings['stock_sync_mode'] ?? null) === $validated['stock_sync_mode'],
            'warehouse_id_matches' => ($verifySettings['warehouse_id'] ?? null) === ($validated['warehouse_id'] ?? null),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Настройки маппинга складов сохранены',
            'saved_settings' => [
                'stock_sync_mode' => $verifySettings['stock_sync_mode'] ?? null,
                'warehouse_id' => $verifySettings['warehouse_id'] ?? null,
                'source_warehouse_ids' => $verifySettings['source_warehouse_ids'] ?? [],
            ],
        ]);
    }
}
