<?php

// file: app/Http/Controllers/Api/UzumSettingsController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceShop;
use App\Services\Marketplaces\UzumClient;
use App\Services\Uzum\Api\UzumApiManager;
use App\Services\Uzum\Api\UzumEndpoints;
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
            $account->api_key ?? $account->uzum_access_token
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
                    'api_key' => ! empty($account->api_key) || ! empty($account->uzum_access_token),
                ],
                'api_key_preview' => $preview,
                'last_successful_call' => $account->wb_last_successful_call, // reuse field for now
                'credentials_json' => $account->credentials_json,
                'uzum_auto_confirm' => (bool) $account->uzum_auto_confirm,
                'uzum_auto_reply' => (bool) $account->uzum_auto_reply,
                'uzum_review_tone' => $account->uzum_review_tone ?? 'friendly',
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
            'uzum_auto_confirm' => ['nullable', 'boolean'],
            'uzum_auto_reply' => ['nullable', 'boolean'],
            'uzum_review_tone' => ['nullable', 'string', 'in:friendly,professional,casual'],
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

        if ($request->has('uzum_auto_confirm')) {
            $updateData['uzum_auto_confirm'] = (bool) $validated['uzum_auto_confirm'];
        }
        if ($request->has('uzum_auto_reply')) {
            $updateData['uzum_auto_reply'] = (bool) $validated['uzum_auto_reply'];
        }
        if ($request->has('uzum_review_tone')) {
            $updateData['uzum_review_tone'] = $validated['uzum_review_tone'];
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
                    $account->api_key ?? $account->uzum_access_token
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

    /**
     * Получить схемы FBS/DBS для всех SKU аккаунта
     */
    public function skuSchemes(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isUzum()) {
            return response()->json(['message' => 'Аккаунт не является Uzum.'], 400);
        }

        try {
            $uzum = new UzumApiManager($account);

            // Принудительное обновление кэша при ?refresh=1
            if ($request->boolean('refresh')) {
                $uzum->api()->flushCache();
            }

            $response = $request->boolean('refresh')
                ? $uzum->api()->call(UzumEndpoints::FBS_STOCKS_GET)
                : $uzum->stocks()->get();
            $skuList = $response['skuAmountList'] ?? $response['payload']['skuAmountList'] ?? [];

            $schemes = [];
            foreach ($skuList as $sku) {
                $skuId = (string) ($sku['skuId'] ?? '');
                if (! $skuId) {
                    continue;
                }
                $schemes[$skuId] = [
                    'fbsAllowed' => (bool) ($sku['fbsAllowed'] ?? false),
                    'dbsAllowed' => (bool) ($sku['dbsAllowed'] ?? false),
                    'fbsLinked'  => (bool) ($sku['fbsLinked'] ?? false),
                    'dbsLinked'  => (bool) ($sku['dbsLinked'] ?? false),
                    'amount'     => (int) ($sku['amount'] ?? 0),
                    'barcode'    => (string) ($sku['barcode'] ?? ''),
                    'skuTitle'   => (string) ($sku['skuTitle'] ?? ''),
                    'productTitle' => (string) ($sku['productTitle'] ?? ''),
                ];
            }

            return response()->json(['schemes' => $schemes]);
        } catch (\Throwable $e) {
            return response()->json(['schemes' => [], 'error' => $e->getMessage()]);
        }
    }

    /**
     * Включить/выключить FBS или DBS для конкретного SKU
     */
    public function updateSkuScheme(Request $request, MarketplaceAccount $account, int $skuId): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isUzum()) {
            return response()->json(['message' => 'Аккаунт не является Uzum.'], 400);
        }

        $validated = $request->validate([
            'fbsLinked' => ['required', 'boolean'],
            'dbsLinked' => ['required', 'boolean'],
        ]);

        try {
            $uzum = new UzumApiManager($account);

            // Получаем текущие данные SKU (barcode, title, amount)
            $response = $uzum->stocks()->get();
            $skuList = $response['skuAmountList'] ?? $response['payload']['skuAmountList'] ?? [];

            $skuData = null;
            foreach ($skuList as $sku) {
                if ((int) ($sku['skuId'] ?? 0) === $skuId) {
                    $skuData = $sku;
                    break;
                }
            }

            if (! $skuData) {
                return response()->json(['message' => "SKU {$skuId} не найден в системе Uzum."], 422);
            }

            $fbsAllowed = (bool) ($skuData['fbsAllowed'] ?? false);
            $dbsAllowed = (bool) ($skuData['dbsAllowed'] ?? false);

            // Нельзя включить схему если Uzum не разрешает
            if ($validated['fbsLinked'] && ! $fbsAllowed) {
                return response()->json(['message' => 'FBS не разрешён для этого SKU в Uzum.'], 422);
            }
            if ($validated['dbsLinked'] && ! $dbsAllowed) {
                return response()->json(['message' => 'DBS не разрешён для этого SKU в Uzum.'], 422);
            }

            $result = $uzum->stocks()->updateOne(
                skuId: $skuId,
                amount: (int) ($skuData['amount'] ?? 0),
                barcode: (string) ($skuData['barcode'] ?? ''),
                skuTitle: (string) ($skuData['skuTitle'] ?? ''),
                productTitle: (string) ($skuData['productTitle'] ?? ''),
                fbs: $validated['fbsLinked'],
                dbs: $validated['dbsLinked'],
            );

            $updatedRecords = $result['payload']['updatedRecords'] ?? $result['updatedRecords'] ?? 0;

            // updatedRecords=0 не ошибка — SKU может быть уже в нужном состоянии
            return response()->json([
                'success' => true,
                'updated_records' => $updatedRecords,
                'fbs_linked' => $validated['fbsLinked'],
                'dbs_linked' => $validated['dbsLinked'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Массовое включение/выключение DBS (и/или FBS) для нескольких SKU
     * POST /api/uzum/accounts/{account}/sku-schemes/bulk
     * Body: {dbs: bool, fbs?: bool, sku_ids?: int[]}
     */
    public function bulkUpdateSkuSchemes(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isUzum()) {
            return response()->json(['message' => 'Аккаунт не является Uzum.'], 400);
        }

        $validated = $request->validate([
            'dbs'     => ['required', 'boolean'],
            'fbs'     => ['sometimes', 'boolean'],
            'sku_ids' => ['sometimes', 'array'],
            'sku_ids.*' => ['integer'],
        ]);

        $targetDbs = $validated['dbs'];
        $targetFbs = $validated['fbs'] ?? null; // null = не менять
        $filterIds = isset($validated['sku_ids']) ? array_map('intval', $validated['sku_ids']) : null;

        try {
            $uzum = new UzumApiManager($account);

            // Получаем все SKU один раз
            $response = $uzum->stocks()->get();
            $skuList = $response['skuAmountList'] ?? $response['payload']['skuAmountList'] ?? [];

            $skuAmountList = [];
            $skipped = 0;

            foreach ($skuList as $sku) {
                $skuId = (int) ($sku['skuId'] ?? 0);
                if (! $skuId) {
                    continue;
                }

                // Если задан список — пропускаем не входящие
                if ($filterIds !== null && ! in_array($skuId, $filterIds)) {
                    continue;
                }

                $dbsAllowed = (bool) ($sku['dbsAllowed'] ?? false);
                $fbsAllowed = (bool) ($sku['fbsAllowed'] ?? false);

                // Если пытаемся включить DBS, а Uzum не разрешает — пропускаем
                if ($targetDbs && ! $dbsAllowed) {
                    $skipped++;
                    continue;
                }

                // Если FBS не передан — сохраняем текущее состояние
                $newFbs = $targetFbs !== null ? $targetFbs : (bool) ($sku['fbsLinked'] ?? false);
                // Проверка разрешения FBS
                if ($newFbs && ! $fbsAllowed) {
                    $newFbs = (bool) ($sku['fbsLinked'] ?? false);
                }

                $skuAmountList[] = [
                    'skuId' => $skuId,
                    'amount' => (int) ($sku['amount'] ?? 0),
                    'barcode' => (string) ($sku['barcode'] ?? ''),
                    'skuTitle' => (string) ($sku['skuTitle'] ?? ''),
                    'productTitle' => (string) ($sku['productTitle'] ?? ''),
                    'fbsLinked' => $newFbs,
                    'dbsLinked' => $targetDbs,
                ];
            }

            $updated = 0;
            $failed = 0;

            if (! empty($skuAmountList)) {
                // Батч-обновление: отправляем пачками по 100 SKU
                $chunks = array_chunk($skuAmountList, 100);
                foreach ($chunks as $chunk) {
                    try {
                        $result = $uzum->stocks()->update($chunk);
                        $updated += $result['payload']['updatedRecords']
                            ?? $result['updatedRecords']
                            ?? count($chunk);
                    } catch (\Throwable $e) {
                        Log::error('UzumSettings: bulk scheme update chunk failed', [
                            'account_id' => $account->id,
                            'chunk_size' => count($chunk),
                            'error' => $e->getMessage(),
                        ]);
                        $failed += count($chunk);
                    }
                }
            }

            // Сбрасываем кэш остатков для актуальных данных при перезагрузке
            $uzum->stocks()->clearCache();

            return response()->json([
                'success' => true,
                'updated' => $updated,
                'skipped' => $skipped,
                'failed'  => $failed,
                'dbs'     => $targetDbs,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
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
