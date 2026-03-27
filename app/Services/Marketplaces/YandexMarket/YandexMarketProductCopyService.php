<?php

declare(strict_types=1);

namespace App\Services\Marketplaces\YandexMarket;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\OzonProduct;
use App\Models\WildberriesProduct;
use Illuminate\Support\Facades\Log;

/**
 * Копирование карточек товаров из других маркетплейсов (WB, Ozon) в Yandex Market
 */
final class YandexMarketProductCopyService
{
    public function __construct(
        private readonly YandexMarketClient $client,
    ) {}

    /**
     * Копировать карточки из указанного маркетплейса в YM-аккаунт
     *
     * @param  MarketplaceAccount  $ymAccount  Целевой YM-аккаунт
     * @param  MarketplaceAccount  $sourceAccount  Исходный аккаунт (WB/Ozon)
     * @param  array  $productIds  ID конкретных товаров-источников (пустой = все)
     * @return array{copied: int, skipped: int, errors: array}
     */
    public function copyFromAccount(
        MarketplaceAccount $ymAccount,
        MarketplaceAccount $sourceAccount,
        array $productIds = []
    ): array {
        return match ($sourceAccount->marketplace) {
            'wb' => $this->copyFromWb($ymAccount, $sourceAccount, $productIds),
            'ozon' => $this->copyFromOzon($ymAccount, $sourceAccount, $productIds),
            'uzum' => $this->copyFromUzum($ymAccount, $sourceAccount, $productIds),
            default => ['copied' => 0, 'skipped' => 0, 'errors' => ["Копирование из {$sourceAccount->marketplace} не поддерживается"]],
        };
    }

    /**
     * Копировать карточки из Wildberries
     */
    private function copyFromWb(MarketplaceAccount $ymAccount, MarketplaceAccount $wbAccount, array $productIds): array
    {
        $query = WildberriesProduct::where('marketplace_account_id', $wbAccount->id)
            ->where('is_active', true);

        if (! empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        $wbProducts = $query->get();

        $copied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($wbProducts as $wbProduct) {
            try {
                $offerId = $wbProduct->vendor_code ?? $wbProduct->supplier_article ?? 'WB-' . $wbProduct->nm_id;

                // Проверяем, не скопирован ли уже
                $existing = MarketplaceProduct::where('marketplace_account_id', $ymAccount->id)
                    ->where('external_offer_id', $offerId)
                    ->first();

                if ($existing && $existing->status === 'synced') {
                    $skipped++;

                    continue;
                }

                $mp = $this->createMarketplaceProductFromWb($ymAccount, $wbProduct, $offerId);

                $this->client->syncProducts($ymAccount, [$mp]);

                $copied++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'source_id' => $wbProduct->id,
                    'title' => $wbProduct->title,
                    'error' => $e->getMessage(),
                ];
                Log::warning('Ошибка копирования WB→YM', [
                    'wb_product_id' => $wbProduct->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('copied', 'skipped', 'errors');
    }

    /**
     * Копировать карточки из Ozon
     */
    private function copyFromOzon(MarketplaceAccount $ymAccount, MarketplaceAccount $ozonAccount, array $productIds): array
    {
        $query = OzonProduct::where('marketplace_account_id', $ozonAccount->id)
            ->where('visible', true);

        if (! empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        $ozonProducts = $query->get();

        $copied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($ozonProducts as $ozonProduct) {
            try {
                $offerId = $ozonProduct->external_offer_id ?? 'OZON-' . $ozonProduct->external_product_id;

                $existing = MarketplaceProduct::where('marketplace_account_id', $ymAccount->id)
                    ->where('external_offer_id', $offerId)
                    ->first();

                if ($existing && $existing->status === 'synced') {
                    $skipped++;

                    continue;
                }

                $mp = $this->createMarketplaceProductFromOzon($ymAccount, $ozonProduct, $offerId);

                $this->client->syncProducts($ymAccount, [$mp]);

                $copied++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'source_id' => $ozonProduct->id,
                    'title' => $ozonProduct->name,
                    'error' => $e->getMessage(),
                ];
                Log::warning('Ошибка копирования Ozon→YM', [
                    'ozon_product_id' => $ozonProduct->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('copied', 'skipped', 'errors');
    }

    /**
     * Создать MarketplaceProduct из данных WB-карточки
     */
    private function createMarketplaceProductFromWb(
        MarketplaceAccount $ymAccount,
        WildberriesProduct $wbProduct,
        string $offerId
    ): MarketplaceProduct {
        // Извлекаем изображения
        $pictures = [];
        if (! empty($wbProduct->photos) && is_array($wbProduct->photos)) {
            foreach ($wbProduct->photos as $photo) {
                $url = is_string($photo) ? $photo : ($photo['url'] ?? $photo['big'] ?? $photo['c246x328'] ?? null);
                if ($url) {
                    $pictures[] = $url;
                }
            }
        }

        // Извлекаем штрихкод
        $barcode = $wbProduct->barcode;

        // Формируем описание
        $description = $wbProduct->description ?? '';

        // Дополняем описание характеристиками
        if (! empty($wbProduct->characteristics) && is_array($wbProduct->characteristics)) {
            $charLines = [];
            foreach ($wbProduct->characteristics as $char) {
                $name = $char['name'] ?? $char['charcName'] ?? null;
                $value = $char['value'] ?? ($char['values'] ?? null);
                if ($name && $value) {
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $charLines[] = "{$name}: {$value}";
                }
            }
            if (! empty($charLines) && empty($description)) {
                $description = implode("\n", $charLines);
            }
        }

        // Собираем raw_payload с данными для маппинга в YM формат
        $rawPayload = [
            'source' => 'wb',
            'source_id' => $wbProduct->id,
            'nm_id' => $wbProduct->nm_id,
            'name' => $wbProduct->title,
            'description' => $description,
            'brand' => $wbProduct->brand,
            'vendor_code' => $wbProduct->vendor_code,
            'barcode' => $barcode,
            'pictures' => array_slice($pictures, 0, 10),
            'price' => (float) $wbProduct->price,
            'category' => $wbProduct->subject_name,
        ];

        return MarketplaceProduct::updateOrCreate(
            [
                'marketplace_account_id' => $ymAccount->id,
                'external_offer_id' => $offerId,
            ],
            [
                'product_id' => $wbProduct->local_product_id,
                'title' => $wbProduct->title,
                'category' => $wbProduct->subject_name,
                'preview_image' => $pictures[0] ?? null,
                'last_synced_price' => $wbProduct->price,
                'status' => MarketplaceProduct::STATUS_PENDING,
                'raw_payload' => $rawPayload,
            ]
        );
    }

    /**
     * Создать MarketplaceProduct из данных Ozon-карточки
     */
    private function createMarketplaceProductFromOzon(
        MarketplaceAccount $ymAccount,
        OzonProduct $ozonProduct,
        string $offerId
    ): MarketplaceProduct {
        $pictures = [];
        if (! empty($ozonProduct->images) && is_array($ozonProduct->images)) {
            foreach ($ozonProduct->images as $img) {
                $url = is_string($img) ? $img : ($img['url'] ?? $img['file_name'] ?? null);
                if ($url) {
                    $pictures[] = $url;
                }
            }
        }

        $rawPayload = [
            'source' => 'ozon',
            'source_id' => $ozonProduct->id,
            'ozon_product_id' => $ozonProduct->external_product_id,
            'name' => $ozonProduct->name,
            'description' => $ozonProduct->description ?? '',
            'barcode' => $ozonProduct->barcode,
            'pictures' => array_slice($pictures, 0, 10),
            'price' => (float) $ozonProduct->price,
            'weight_g' => $ozonProduct->weight,
            'width_mm' => $ozonProduct->width,
            'height_mm' => $ozonProduct->height,
            'depth_mm' => $ozonProduct->depth,
        ];

        return MarketplaceProduct::updateOrCreate(
            [
                'marketplace_account_id' => $ymAccount->id,
                'external_offer_id' => $offerId,
            ],
            [
                'product_id' => $ozonProduct->product_id,
                'title' => $ozonProduct->name,
                'preview_image' => $pictures[0] ?? null,
                'last_synced_price' => $ozonProduct->price,
                'status' => MarketplaceProduct::STATUS_PENDING,
                'raw_payload' => $rawPayload,
            ]
        );
    }

    /**
     * Копировать карточки из Uzum Market
     *
     * Uzum хранит товары в таблице marketplace_products с полными данными в raw_payload.
     */
    private function copyFromUzum(MarketplaceAccount $ymAccount, MarketplaceAccount $uzumAccount, array $productIds): array
    {
        $query = MarketplaceProduct::where('marketplace_account_id', $uzumAccount->id)
            ->where('status', MarketplaceProduct::STATUS_ACTIVE);

        if (! empty($productIds)) {
            $query->whereIn('id', $productIds);
        }

        $uzumProducts = $query->get();

        $copied = 0;
        $skipped = 0;
        $errors = [];

        foreach ($uzumProducts as $uzumProduct) {
            try {
                $payload = $uzumProduct->raw_payload ?? [];
                $skuList = $payload['skuList'] ?? [];
                $firstSku = $skuList[0] ?? [];

                $offerId = $firstSku['vendorCode']
                    ?? $firstSku['barcode']
                    ?? $uzumProduct->external_offer_id
                    ?? 'UZUM-' . $uzumProduct->external_product_id;

                $existing = MarketplaceProduct::where('marketplace_account_id', $ymAccount->id)
                    ->where('external_offer_id', $offerId)
                    ->first();

                if ($existing && $existing->status === 'synced') {
                    $skipped++;

                    continue;
                }

                $mp = $this->createMarketplaceProductFromUzum($ymAccount, $uzumProduct, $offerId);

                $this->client->syncProducts($ymAccount, [$mp]);

                $copied++;
            } catch (\Throwable $e) {
                $errors[] = [
                    'source_id' => $uzumProduct->id,
                    'title' => $uzumProduct->title,
                    'error' => $e->getMessage(),
                ];
                Log::warning('Ошибка копирования Uzum→YM', [
                    'uzum_product_id' => $uzumProduct->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return compact('copied', 'skipped', 'errors');
    }

    /**
     * Создать MarketplaceProduct из данных Uzum-карточки
     */
    private function createMarketplaceProductFromUzum(
        MarketplaceAccount $ymAccount,
        MarketplaceProduct $uzumProduct,
        string $offerId
    ): MarketplaceProduct {
        $payload = $uzumProduct->raw_payload ?? [];
        $skuList = $payload['skuList'] ?? [];
        $firstSku = $skuList[0] ?? [];

        // Извлекаем изображения из разных форматов Uzum API
        $pictures = [];
        $imageFields = ['photos', 'photoGallery', 'images', 'galleryImages'];
        foreach ($imageFields as $field) {
            if (! empty($payload[$field]) && is_array($payload[$field])) {
                foreach ($payload[$field] as $img) {
                    $url = is_string($img) ? $img : ($img['url'] ?? $img['photo'] ?? $img['link'] ?? null);
                    if ($url) {
                        $pictures[] = $url;
                    }
                }
                break; // Берём из первого непустого поля
            }
        }

        // Fallback на preview/main image
        if (empty($pictures)) {
            $previewFields = ['previewImg', 'image', 'photo', 'thumbnail', 'mainImage', 'coverImage', 'photoUrl', 'imageUrl'];
            foreach ($previewFields as $field) {
                if (! empty($payload[$field])) {
                    $pictures[] = $payload[$field];

                    break;
                }
            }
        }

        // Fallback на preview_image из MarketplaceProduct
        if (empty($pictures) && $uzumProduct->preview_image) {
            $pictures[] = $uzumProduct->preview_image;
        }

        // SKU-level изображения
        foreach ($skuList as $sku) {
            $skuImgFields = ['image', 'photo', 'skuImage', 'imageUrl'];
            foreach ($skuImgFields as $field) {
                if (! empty($sku[$field]) && ! in_array($sku[$field], $pictures)) {
                    $pictures[] = $sku[$field];

                    break;
                }
            }
        }

        // Штрихкод
        $barcode = $firstSku['barcode'] ?? $uzumProduct->external_sku ?? null;

        // Описание (Uzum не всегда хранит описание в raw_payload)
        $description = $payload['description'] ?? '';

        // Характеристики если есть
        if (! empty($payload['characteristics']) && is_array($payload['characteristics'])) {
            $charLines = [];
            foreach ($payload['characteristics'] as $char) {
                $title = $char['title'] ?? $char['name'] ?? null;
                $value = $char['value'] ?? null;
                if ($title && $value) {
                    $charLines[] = "{$title}: {$value}";
                }
            }
            if (! empty($charLines) && empty($description)) {
                $description = implode("\n", $charLines);
            }
        }

        $rawPayload = [
            'source' => 'uzum',
            'source_id' => $uzumProduct->id,
            'uzum_product_id' => $uzumProduct->external_product_id,
            'name' => $payload['title'] ?? $uzumProduct->title ?? '',
            'description' => $description,
            'brand' => $payload['brand'] ?? null,
            'vendor_code' => $firstSku['vendorCode'] ?? $offerId,
            'barcode' => $barcode,
            'pictures' => array_slice(array_unique($pictures), 0, 10),
            'price' => (float) ($uzumProduct->last_synced_price ?? $firstSku['price'] ?? 0),
        ];

        return MarketplaceProduct::updateOrCreate(
            [
                'marketplace_account_id' => $ymAccount->id,
                'external_offer_id' => $offerId,
            ],
            [
                'product_id' => $uzumProduct->product_id,
                'title' => $payload['title'] ?? $uzumProduct->title,
                'category' => $payload['category'] ?? $uzumProduct->category,
                'preview_image' => $pictures[0] ?? $uzumProduct->preview_image,
                'last_synced_price' => $uzumProduct->last_synced_price,
                'status' => MarketplaceProduct::STATUS_PENDING,
                'raw_payload' => $rawPayload,
            ]
        );
    }
}
