<?php

// file: app/Http/Controllers/Api/WildberriesSettingsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WildberriesSettingsController extends Controller
{
    /**
     * Get WB account settings (tokens config)
     */
    public function show(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        return response()->json([
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'marketplace' => $account->marketplace,
                'is_active' => $account->is_active,
                // Token status (not the actual values for security)
                'tokens' => [
                    'api_key' => ! empty($account->api_key),
                    'content' => ! empty($account->wb_content_token),
                    'marketplace' => ! empty($account->wb_marketplace_token),
                    'prices' => ! empty($account->wb_prices_token),
                    'statistics' => ! empty($account->wb_statistics_token),
                ],
                // Warehouse settings
                'credentials_json' => [
                    'warehouse_id' => $account->credentials_json['warehouse_id'] ?? null,
                    'sync_mode' => $account->credentials_json['sync_mode'] ?? 'basic',
                    'source_warehouse_ids' => $account->credentials_json['source_warehouse_ids'] ?? [],
                ],
                'wb_tokens_valid' => $account->wb_tokens_valid,
                'wb_last_successful_call' => $account->wb_last_successful_call,
            ],
        ]);
    }

    /**
     * Update WB account tokens
     */
    public function update(Request $request, MarketplaceAccount $account): JsonResponse
    {
        \Log::info('WB Settings Update Request', [
            'account_id' => $account->id,
            'user_id' => $request->user()?->id,
            'request_data' => $request->except(['api_key', 'wb_content_token', 'wb_marketplace_token', 'wb_prices_token', 'wb_statistics_token']),
            'has_api_key' => $request->has('api_key'),
            'has_tokens' => [
                'content' => $request->has('wb_content_token'),
                'marketplace' => $request->has(' wb_marketplace_token'),
                'prices' => $request->has('wb_prices_token'),
                'statistics' => $request->has('wb_statistics_token'),
            ],
        ]);

        if (! $request->user()->isOwnerOf($account->company_id)) {
            return response()->json(['message' => 'Только владелец может изменять настройки токенов.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:4000'],
            'wb_content_token' => ['nullable', 'string', 'max:4000'],
            'wb_marketplace_token' => ['nullable', 'string', 'max:4000'],
            'wb_prices_token' => ['nullable', 'string', 'max:4000'],
            'wb_statistics_token' => ['nullable', 'string', 'max:4000'],
            'warehouse_id' => ['nullable', 'integer'], // для aggregated mode (WB склад)
            'sync_mode' => ['nullable', 'in:basic,aggregated'], // режим синхронизации
            'source_warehouse_ids' => ['nullable', 'array'], // для aggregated mode (внутренние склады)
            'source_warehouse_ids.*' => ['integer'],
        ]);

        // Update only provided tokens (null = don't update, empty string = clear)
        $updateData = [];

        foreach (['api_key', 'wb_content_token', 'wb_marketplace_token', 'wb_prices_token', 'wb_statistics_token'] as $field) {
            if (array_key_exists($field, $validated)) {
                $value = $validated[$field];
                // Empty string clears the token, null means don't update
                $updateData[$field] = $value === '' ? null : $value;
            }
        }

        // Handle warehouse_id and sync_mode update
        if (array_key_exists('warehouse_id', $validated) || array_key_exists('sync_mode', $validated)) {
            $credentialsJson = $account->credentials_json ?? [];

            if (array_key_exists('warehouse_id', $validated)) {
                $credentialsJson['warehouse_id'] = $validated['warehouse_id'];
                \Log::info('WB Settings: Updating warehouse_id', ['warehouse_id' => $validated['warehouse_id']]);
            }

            if (array_key_exists('sync_mode', $validated)) {
                $credentialsJson['sync_mode'] = $validated['sync_mode'];
                \Log::info('WB Settings: Updating sync_mode', ['sync_mode' => $validated['sync_mode']]);
            }

            if (array_key_exists('source_warehouse_ids', $validated)) {
                $credentialsJson['source_warehouse_ids'] = $validated['source_warehouse_ids'];
                \Log::info('WB Settings: Updating source_warehouse_ids', ['ids' => $validated['source_warehouse_ids']]);
            }

            $updateData['credentials_json'] = $credentialsJson;
        }

        if (! empty($updateData)) {
            \Log::info('WB Settings: Updating settings', ['fields' => array_keys($updateData)]);
            $account->update($updateData);
            // Reset token validity after update
            if (isset($updateData['api_key']) || isset($updateData['wb_content_token'])) {
                $account->update(['wb_tokens_valid' => true]);
            }
            \Log::info('WB Settings: Settings updated successfully', ['account_id' => $account->id]);
        } else {
            \Log::info('WB Settings: No settings to update');
        }

        return response()->json([
            'message' => 'Настройки обновлены.',
            'account' => [
                'id' => $account->id,
                'tokens' => [
                    'api_key' => ! empty($account->api_key),
                    'content' => ! empty($account->wb_content_token),
                    'marketplace' => ! empty($account->wb_marketplace_token),
                    'prices' => ! empty($account->wb_prices_token),
                    'statistics' => ! empty($account->wb_statistics_token),
                ],
                'credentials_json' => [
                    'warehouse_id' => $account->credentials_json['warehouse_id'] ?? null,
                ],
            ],
        ]);
    }

    /**
     * Test WB API connection for all categories
     */
    public function test(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $client = new WildberriesHttpClient($account);

        $categories = ['common', 'content', 'marketplace', 'prices', 'statistics'];
        $results = [];
        $allSuccess = true;

        foreach ($categories as $category) {
            $startTime = microtime(true);
            $pingResult = $client->ping($category);
            $duration = round((microtime(true) - $startTime) * 1000);

            $results[$category] = [
                'success' => $pingResult['success'],
                'message' => $pingResult['message'] ?? null,
                'duration_ms' => $duration,
            ];

            if (! $pingResult['success']) {
                $allSuccess = false;
            }
        }

        // Update token validity
        if ($allSuccess) {
            $account->markWbTokensValid();
        } else {
            // Check if it's auth error
            $hasAuthError = false;
            foreach ($results as $result) {
                if (! $result['success'] && isset($result['message']) && str_contains($result['message'], 'auth')) {
                    $hasAuthError = true;
                    break;
                }
            }
            if ($hasAuthError) {
                $account->markWbTokensInvalid();
            }
        }

        return response()->json([
            'success' => $allSuccess,
            'results' => $results,
            'tokens_valid' => $account->wb_tokens_valid,
        ]);
    }

    /**
     * Test single API category
     */
    public function testCategory(Request $request, MarketplaceAccount $account, string $category): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validCategories = ['common', 'content', 'marketplace', 'prices', 'statistics'];
        if (! in_array($category, $validCategories)) {
            return response()->json(['message' => 'Неизвестная категория API: '.$category], 400);
        }

        $client = new WildberriesHttpClient($account);

        $startTime = microtime(true);
        $pingResult = $client->ping($category);
        $duration = round((microtime(true) - $startTime) * 1000);

        if ($pingResult['success']) {
            $account->markWbTokensValid();
        }

        return response()->json([
            'category' => $category,
            'success' => $pingResult['success'],
            'message' => $pingResult['message'] ?? null,
            'duration_ms' => $duration,
        ]);
    }
}
