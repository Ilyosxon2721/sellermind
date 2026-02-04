<?php

// file: app/Http/Controllers/Api/UzumSettingsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceShop;
use App\Services\Marketplaces\UzumClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UzumSettingsController extends Controller
{
    public function show(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isUzum()) {
            return response()->json(['message' => 'Аккаунт не является Uzum.'], 400);
        }

        $preview = $this->maskToken(
            $account->api_key ?? $account->uzum_api_key ?? $account->uzum_access_token
        );

        return response()->json([
            'account' => [
                'id' => $account->id,
                'name' => $account->name,
                'marketplace' => $account->marketplace,
                'is_active' => $account->is_active,
                'shop_id' => $account->shop_id,
                'shop_ids' => $account->credentials_json['shop_ids'] ?? ($account->shop_id ? [$account->shop_id] : []),
                'tokens' => [
                    'api_key' => ! empty($account->api_key) || ! empty($account->uzum_api_key) || ! empty($account->uzum_access_token),
                ],
                'api_key_preview' => $preview,
                'last_successful_call' => $account->wb_last_successful_call, // reuse field for now
                'credentials_json' => $account->credentials_json,
            ],
        ]);
    }

    public function update(Request $request, MarketplaceAccount $account, UzumClient $client): JsonResponse
    {
        if (! $request->user()->isOwnerOf($account->company_id)) {
            return response()->json(['message' => 'Только владелец может изменять настройки токенов.'], 403);
        }

        if (! $account->isUzum()) {
            return response()->json(['message' => 'Аккаунт не является Uzum.'], 400);
        }

        $validated = $request->validate([
            'api_key' => ['nullable', 'string', 'max:4000'],
            'shop_id' => ['nullable', 'string', 'max:100'],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['string'],
            'stock_sync_mode' => ['nullable', 'string', 'in:basic,aggregated'],
            'warehouse_id' => ['nullable', 'integer'],
            'source_warehouse_ids' => ['nullable', 'array'],
            'source_warehouse_ids.*' => ['integer'],
        ]);

        $updateData = [];
        if (array_key_exists('api_key', $validated)) {
            $value = $validated['api_key'];
            $updateData['api_key'] = $value === '' ? null : $value;
        }
        if (array_key_exists('shop_id', $validated)) {
            $updateData['shop_id'] = $validated['shop_id'];
        }

        // Handle shop_ids and stock sync settings in credentials_json
        $credentialsJson = $account->credentials_json ?? [];
        $needsCredentialsUpdate = false;

        if ($request->has('shop_ids')) {
            $credentialsJson['shop_ids'] = $validated['shop_ids'] ?? [];
            $needsCredentialsUpdate = true;
            // Also set first shop_id for backwards compatibility
            if (! empty($validated['shop_ids'])) {
                $updateData['shop_id'] = $validated['shop_ids'][0];
            }
        }
        if ($request->has('stock_sync_mode')) {
            $credentialsJson['stock_sync_mode'] = $validated['stock_sync_mode'];
            $needsCredentialsUpdate = true;
        }
        if ($request->has('warehouse_id')) {
            $credentialsJson['warehouse_id'] = $validated['warehouse_id'];
            $needsCredentialsUpdate = true;
        }
        if ($request->has('source_warehouse_ids')) {
            $credentialsJson['source_warehouse_ids'] = $validated['source_warehouse_ids'];
            $needsCredentialsUpdate = true;
        }

        if ($needsCredentialsUpdate) {
            $updateData['credentials_json'] = $credentialsJson;
        }

        if (! empty($updateData)) {
            $account->update($updateData);
        }

        // При первом добавлении токена — подтягиваем магазины и сохраняем в БД
        try {
            $shops = $client->fetchShops($account);
            $this->storeShops($account, $shops);
        } catch (\Throwable $e) {
            // Не блокируем сохранение токена, просто логируем
            \Log::warning('Failed to fetch Uzum shops after token update', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Настройки обновлены.',
            'account' => [
                'id' => $account->id,
                'shop_id' => $account->shop_id,
                'shop_ids' => $account->credentials_json['shop_ids'] ?? ($account->shop_id ? [$account->shop_id] : []),
                'tokens' => [
                    'api_key' => ! empty($account->api_key),
                ],
                'api_key_preview' => $this->maskToken(
                    $account->api_key ?? $account->uzum_api_key ?? $account->uzum_access_token
                ),
            ],
        ]);
    }

    public function test(Request $request, MarketplaceAccount $account, UzumClient $client): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isUzum()) {
            return response()->json(['message' => 'Аккаунт не является Uzum.'], 400);
        }

        $result = $client->ping($account);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? null,
            'data' => $result['data'] ?? null,
        ], $result['success'] ? 200 : 400);
    }

    /**
     * Получить список магазинов Uzum (id/name)
     */
    public function shops(Request $request, MarketplaceAccount $account, UzumClient $client): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isUzum()) {
            return response()->json(['message' => 'Аккаунт не является Uzum.'], 400);
        }

        try {
            $shops = MarketplaceShop::where('marketplace_account_id', $account->id)->get();
            // Если в БД пусто, попробуем подтянуть из API и сохранить
            if ($shops->isEmpty()) {
                $apiShops = $client->fetchShops($account);
                $this->storeShops($account, $apiShops);
                $shops = MarketplaceShop::where('marketplace_account_id', $account->id)->get();
            }
            $payload = $shops->map(fn ($s) => [
                'id' => $s->external_id,
                'name' => $s->name,
                'raw_payload' => $s->raw_payload,
            ]);

            return response()->json(['shops' => $payload]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ошибка получения магазинов: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Сохранить магазины в БД
     */
    protected function storeShops(MarketplaceAccount $account, array $shops): void
    {
        foreach ($shops as $shop) {
            if (! isset($shop['id'])) {
                continue;
            }
            MarketplaceShop::updateOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'external_id' => (string) $shop['id'],
                ],
                [
                    'name' => $shop['name'] ?? null,
                    'raw_payload' => $shop,
                ]
            );
        }
    }

    protected function maskToken(?string $token): ?string
    {
        if (! $token) {
            return null;
        }

        $len = mb_strlen($token);
        if ($len <= 8) {
            return $token;
        }

        return mb_substr($token, 0, 4).'...'.mb_substr($token, -4);
    }
}
