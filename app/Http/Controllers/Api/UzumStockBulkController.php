<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Services\Uzum\Api\UzumApiManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Массовое управление остатками и FBS/DBS привязкой товаров Uzum Market
 */
final class UzumStockBulkController extends Controller
{
    /**
     * Получить все остатки FBS/DBS с информацией о привязке
     */
    public function listStocks(Request $request, MarketplaceAccount $account): JsonResponse
    {
        try {
            $uzum = new UzumApiManager($account);
            $fresh = $request->boolean('fresh');
            $response = $fresh ? $uzum->stocks()->getFresh() : $uzum->stocks()->get();

            // API возвращает { payload: { skuAmountList: [...] } }
            $payload = $response['payload'] ?? $response;
            $items = $payload['skuAmountList']
                ?? $payload['shopSkuList']
                ?? [];

            // Если items — группировка по магазинам (shopId + skuList)
            if (! empty($items) && ! isset($items[0]['skuId'])) {
                $grouped = [];
                foreach ($items as $shopGroup) {
                    if (! is_array($shopGroup) || ! isset($shopGroup['shopId'])) {
                        continue;
                    }
                    $shopId = $shopGroup['shopId'];
                    $shopName = $shopGroup['shopName'] ?? null;
                    foreach ($shopGroup['skuList'] ?? $shopGroup['skuAmountList'] ?? [] as $sku) {
                        $sku['shopId'] = $shopId;
                        $sku['shopName'] = $shopName;
                        $grouped[] = $sku;
                    }
                }
                if (! empty($grouped)) {
                    $items = $grouped;
                }
            }

            // Обогащаем shopId из БД (по skuId из raw_payload.skuList)
            $skuShopMap = $this->buildSkuShopMap($account);
            foreach ($items as &$item) {
                if (empty($item['shopId']) && isset($item['skuId'])) {
                    $item['shopId'] = $skuShopMap[(int) $item['skuId']] ?? null;
                }
            }
            unset($item);

            // Собираем список уникальных магазинов из items
            $shops = [];
            $seenShops = [];
            foreach ($items as $item) {
                $sid = $item['shopId'] ?? null;
                if ($sid && ! isset($seenShops[$sid])) {
                    $seenShops[$sid] = true;
                    $shops[] = [
                        'id' => $sid,
                        'name' => $item['shopName'] ?? $this->getShopName($account, $sid) ?? (string) $sid,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'items' => array_values($items),
                'total' => count($items),
                'shops' => $shops,
            ]);
        } catch (\Throwable $e) {
            Log::error('UzumStockBulk: ошибка получения остатков', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения остатков: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Построить карту skuId -> shopId из raw_payload товаров
     */
    private function buildSkuShopMap(MarketplaceAccount $account): array
    {
        $map = [];
        $products = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('shop_id')
            ->select('shop_id', 'raw_payload')
            ->get();

        foreach ($products as $product) {
            $payload = $product->raw_payload;
            if (! is_array($payload)) {
                continue;
            }
            $skuList = $payload['skuList'] ?? $payload['characteristics'] ?? [];
            foreach ($skuList as $sku) {
                if (isset($sku['skuId'])) {
                    $map[(int) $sku['skuId']] = (string) $product->shop_id;
                }
            }
        }

        return $map;
    }

    /**
     * Получить название магазина по shopId из БД
     */
    private function getShopName(MarketplaceAccount $account, string $shopId): ?string
    {
        $product = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->where('shop_id', $shopId)
            ->whereNotNull('raw_payload')
            ->first();

        if (! $product) {
            return null;
        }

        $payload = $product->raw_payload;

        return $payload['shopTitle'] ?? $payload['shopName'] ?? null;
    }

    /**
     * Массовое отключение товаров из FBS/DBS
     *
     * Режимы:
     * - zero_stock: обнулить остатки (товар становится RUN_OUT)
     * - fbs: отключить FBS привязку (fbsLinked=false)
     * - dbs: отключить DBS привязку (dbsLinked=false)
     * - both: отключить FBS и DBS привязку
     */
    public function bulkDisable(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $request->validate([
            'sku_ids' => 'required|array|min:1',
            'sku_ids.*' => 'required|integer',
            'mode' => 'required|string|in:zero_stock,fbs,dbs,both',
            'items_data' => 'sometimes|array',
        ]);

        $skuIds = $request->input('sku_ids');
        $mode = $request->input('mode');
        $itemsData = collect($request->input('items_data', []))->keyBy('skuId');

        try {
            $uzum = new UzumApiManager($account);

            $skuAmountList = [];
            foreach ($skuIds as $skuId) {
                $existing = $itemsData->get($skuId, []);

                $item = [
                    'skuId' => (int) $skuId,
                    'amount' => $mode === 'zero_stock' ? 0 : (int) ($existing['amount'] ?? 0),
                    'barcode' => (string) ($existing['barcode'] ?? ''),
                    'skuTitle' => (string) ($existing['skuTitle'] ?? ''),
                    'productTitle' => (string) ($existing['productTitle'] ?? ''),
                    'fbsLinked' => match ($mode) {
                        'fbs', 'both' => false,
                        default => (bool) ($existing['fbsLinked'] ?? true),
                    },
                    'dbsLinked' => match ($mode) {
                        'dbs', 'both' => false,
                        default => (bool) ($existing['dbsLinked'] ?? false),
                    },
                ];

                $skuAmountList[] = $item;
            }

            $response = $uzum->stocks()->update($skuAmountList);
            $updatedRecords = $response['payload']['updatedRecords'] ?? 0;

            $modeLabels = [
                'zero_stock' => 'обнуление остатков',
                'fbs' => 'отключение FBS',
                'dbs' => 'отключение DBS',
                'both' => 'отключение FBS и DBS',
            ];

            Log::info('UzumStockBulk: массовое отключение', [
                'account_id' => $account->id,
                'mode' => $mode,
                'requested' => count($skuIds),
                'updated' => $updatedRecords,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Выполнено: {$modeLabels[$mode]}. Обновлено: {$updatedRecords} из " . count($skuIds),
                'updated' => $updatedRecords,
                'requested' => count($skuIds),
            ]);
        } catch (\Throwable $e) {
            Log::error('UzumStockBulk: ошибка массового отключения', [
                'account_id' => $account->id,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Массовое включение товаров в FBS/DBS
     */
    public function bulkEnable(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $request->validate([
            'sku_ids' => 'required|array|min:1',
            'sku_ids.*' => 'required|integer',
            'mode' => 'required|string|in:fbs,dbs,both',
            'items_data' => 'sometimes|array',
        ]);

        $skuIds = $request->input('sku_ids');
        $mode = $request->input('mode');
        $itemsData = collect($request->input('items_data', []))->keyBy('skuId');

        try {
            $uzum = new UzumApiManager($account);

            $skuAmountList = [];
            foreach ($skuIds as $skuId) {
                $existing = $itemsData->get($skuId, []);

                $item = [
                    'skuId' => (int) $skuId,
                    'amount' => (int) ($existing['amount'] ?? 0),
                    'barcode' => (string) ($existing['barcode'] ?? ''),
                    'skuTitle' => (string) ($existing['skuTitle'] ?? ''),
                    'productTitle' => (string) ($existing['productTitle'] ?? ''),
                    'fbsLinked' => match ($mode) {
                        'fbs', 'both' => true,
                        default => (bool) ($existing['fbsLinked'] ?? true),
                    },
                    'dbsLinked' => match ($mode) {
                        'dbs', 'both' => true,
                        default => (bool) ($existing['dbsLinked'] ?? false),
                    },
                ];

                $skuAmountList[] = $item;
            }

            $response = $uzum->stocks()->update($skuAmountList);
            $updatedRecords = $response['payload']['updatedRecords'] ?? 0;

            $modeLabels = [
                'fbs' => 'подключение FBS',
                'dbs' => 'подключение DBS',
                'both' => 'подключение FBS и DBS',
            ];

            Log::info('UzumStockBulk: массовое включение', [
                'account_id' => $account->id,
                'mode' => $mode,
                'requested' => count($skuIds),
                'updated' => $updatedRecords,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Выполнено: {$modeLabels[$mode]}. Обновлено: {$updatedRecords} из " . count($skuIds),
                'updated' => $updatedRecords,
                'requested' => count($skuIds),
            ]);
        } catch (\Throwable $e) {
            Log::error('UzumStockBulk: ошибка массового включения', [
                'account_id' => $account->id,
                'mode' => $mode,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка: ' . $e->getMessage(),
            ], 500);
        }
    }
}
