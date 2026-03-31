<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
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
            $response = $uzum->stocks()->get();

            // API возвращает { payload: { skuAmountList: [...] } }
            $payload = $response['payload'] ?? $response;
            $items = $payload['skuAmountList']
                ?? $payload['shopSkuList']
                ?? [];

            // Если items — плоский список SKU с skuId
            if (! empty($items) && isset($items[0]['skuId'])) {
                return response()->json([
                    'success' => true,
                    'items' => $items,
                    'total' => count($items),
                ]);
            }

            // Если items — группировка по магазинам (shopId + skuList)
            $allItems = [];
            foreach ($items as $shopGroup) {
                if (! is_array($shopGroup) || ! isset($shopGroup['shopId'])) {
                    continue;
                }
                $shopId = $shopGroup['shopId'];
                $shopName = $shopGroup['shopName'] ?? null;
                foreach ($shopGroup['skuList'] ?? $shopGroup['skuAmountList'] ?? [] as $sku) {
                    $sku['shopId'] = $shopId;
                    $sku['shopName'] = $shopName;
                    $allItems[] = $sku;
                }
            }

            return response()->json([
                'success' => true,
                'items' => ! empty($allItems) ? $allItems : $items,
                'total' => ! empty($allItems) ? count($allItems) : count($items),
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
