<?php

declare(strict_types=1);

namespace App\Services\Marketplaces;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\WildberriesProduct;
use App\Models\OzonProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Мост: копирует данные из WB/Ozon нативных таблиц → marketplace_products
 * чтобы страница остатков могла показывать товары всех маркетплейсов.
 */
final class MarketplaceProductsBridgeService
{
    /**
     * Синхронизировать WB товары в marketplace_products.
     * Группирует по nm_id (одна карточка = одна строка).
     */
    public function syncFromWildberries(MarketplaceAccount $account): int
    {
        Log::info('Bridge: starting WB → marketplace_products sync', [
            'account_id' => $account->id,
        ]);

        // Загружаем остатки по складам с типом FBS/FBO
        $stocksByNmId = $this->loadWbStocksByNmId($account->id);

        // Загружаем все WB продукты для аккаунта, группируем по nm_id
        $products = WildberriesProduct::where('marketplace_account_id', $account->id)
            ->orderBy('nm_id')
            ->orderBy('id')
            ->get()
            ->groupBy('nm_id');

        $synced = 0;

        foreach ($products as $nmId => $variants) {
            /** @var WildberriesProduct $first */
            $first = $variants->first();

            $stocks = $stocksByNmId[$nmId] ?? ['fbs' => 0, 'fbo' => 0, 'total' => 0];

            // Берём лучшую цену: price_with_discount если есть, иначе price
            $price = (float) ($first->price_with_discount ?? $first->price ?? 0);

            // Превью — первое фото
            $previewImage = $first->getPrimaryPhotoUrl();

            // Формируем raw_payload для возможности показа вариантов
            $rawPayload = [
                'nm_id' => $nmId,
                'title' => $first->title,
                'brand' => $first->brand,
                'subject_name' => $first->subject_name,
                'variants' => $variants->map(fn (WildberriesProduct $v) => [
                    'chrt_id'  => $v->chrt_id,
                    'barcode'  => $v->barcode,
                    'tech_size' => $v->tech_size,
                    'price'    => $v->price,
                    'price_with_discount' => $v->price_with_discount,
                ])->values()->toArray(),
            ];

            MarketplaceProduct::updateOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'external_product_id'    => (string) $nmId,
                ],
                [
                    'external_offer_id'  => $first->chrt_id ? (string) $first->chrt_id : null,
                    'external_sku'       => $first->barcode,
                    'title'              => $first->title,
                    'category'           => $first->subject_name,
                    'preview_image'      => $previewImage,
                    'status'             => $first->is_active
                        ? MarketplaceProduct::STATUS_ACTIVE
                        : MarketplaceProduct::STATUS_ARCHIVED,
                    'last_synced_price'  => $price ?: null,
                    'stock_fbs'          => $stocks['fbs'],
                    'stock_fbo'          => $stocks['fbo'],
                    'last_synced_stock'  => $stocks['total'],
                    'raw_payload'        => $rawPayload,
                    'last_synced_at'     => now(),
                    'last_error'         => null,
                ]
            );

            $synced++;
        }

        Log::info('Bridge: WB → marketplace_products done', [
            'account_id' => $account->id,
            'synced'     => $synced,
        ]);

        return $synced;
    }

    /**
     * Синхронизировать Ozon товары в marketplace_products.
     */
    public function syncFromOzon(MarketplaceAccount $account): int
    {
        Log::info('Bridge: starting Ozon → marketplace_products sync', [
            'account_id' => $account->id,
        ]);

        $products = OzonProduct::where('marketplace_account_id', $account->id)->get();

        $synced = 0;

        foreach ($products as $product) {
            /** @var OzonProduct $product */
            $status = $this->mapOzonStatus($product->status ?? '');

            $previewImage = null;
            if (! empty($product->images) && is_array($product->images)) {
                $previewImage = $product->images[0] ?? null;
            }

            // Ozon stock — FBO (на складе Ozon)
            $stockFbo = (int) ($product->stock ?? 0);

            MarketplaceProduct::updateOrCreate(
                [
                    'marketplace_account_id' => $account->id,
                    'external_product_id'    => (string) $product->external_product_id,
                ],
                [
                    'external_offer_id'  => $product->external_offer_id,
                    'external_sku'       => $product->barcode,
                    'title'              => $product->name,
                    'category'           => null,
                    'preview_image'      => $previewImage,
                    'status'             => $status,
                    'last_synced_price'  => $product->price ? (float) $product->price : null,
                    'stock_fbs'          => 0,
                    'stock_fbo'          => $stockFbo,
                    'last_synced_stock'  => $stockFbo,
                    'raw_payload'        => [
                        'product_id' => $product->external_product_id,
                        'offer_id'   => $product->external_offer_id,
                        'name'       => $product->name,
                        'images'     => $product->images ?? [],
                        'status'     => $product->status,
                    ],
                    'last_synced_at'     => $product->last_synced_at ?? now(),
                    'last_error'         => null,
                ]
            );

            $synced++;
        }

        Log::info('Bridge: Ozon → marketplace_products done', [
            'account_id' => $account->id,
            'synced'     => $synced,
        ]);

        return $synced;
    }

    /**
     * Загрузить остатки WB, сгруппированные по nm_id с разбивкой FBS/FBO.
     *
     * @return array<int, array{fbs: int, fbo: int, total: int}>
     */
    private function loadWbStocksByNmId(int $accountId): array
    {
        $rows = DB::table('wildberries_stocks as ws')
            ->join('wildberries_products as wp', 'wp.id', '=', 'ws.wildberries_product_id')
            ->leftJoin('wildberries_warehouses as wh', 'wh.id', '=', 'ws.wildberries_warehouse_id')
            ->where('ws.marketplace_account_id', $accountId)
            ->select(
                'wp.nm_id',
                'wh.warehouse_type',
                DB::raw('SUM(ws.quantity) as qty')
            )
            ->groupBy('wp.nm_id', 'wh.warehouse_type')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $nmId = (int) $row->nm_id;

            if (! isset($result[$nmId])) {
                $result[$nmId] = ['fbs' => 0, 'fbo' => 0, 'total' => 0];
            }

            $qty = (int) $row->qty;
            $type = strtoupper($row->warehouse_type ?? '');

            if ($type === 'FBO') {
                $result[$nmId]['fbo'] += $qty;
            } else {
                // FBS или без типа — считаем как FBS (склад продавца)
                $result[$nmId]['fbs'] += $qty;
            }

            $result[$nmId]['total'] += $qty;
        }

        // Если warehouse данных нет — берём stock_total из wildberries_products
        if (empty($result)) {
            $totals = DB::table('wildberries_products')
                ->where('marketplace_account_id', $accountId)
                ->select('nm_id', DB::raw('SUM(stock_total) as total'))
                ->groupBy('nm_id')
                ->get();

            foreach ($totals as $row) {
                $nmId = (int) $row->nm_id;
                $total = (int) $row->total;
                $result[$nmId] = ['fbs' => $total, 'fbo' => 0, 'total' => $total];
            }
        }

        return $result;
    }

    /**
     * Маппинг Ozon статуса → MarketplaceProduct статус.
     */
    private function mapOzonStatus(string $status): string
    {
        return match (true) {
            str_contains(strtolower($status), 'sale') => MarketplaceProduct::STATUS_ACTIVE,
            str_contains(strtolower($status), 'active') => MarketplaceProduct::STATUS_ACTIVE,
            str_contains(strtolower($status), 'archive') => MarketplaceProduct::STATUS_ARCHIVED,
            str_contains(strtolower($status), 'error') => MarketplaceProduct::STATUS_ERROR,
            $status === '' => MarketplaceProduct::STATUS_PENDING,
            default => MarketplaceProduct::STATUS_ACTIVE,
        };
    }
}
