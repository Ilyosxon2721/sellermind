<?php

declare(strict_types=1);

namespace App\Services\Products\Extractors;

use App\Models\MarketplaceProduct;
use App\Services\Products\DTO\ProductCardDTO;
use Illuminate\Database\Eloquent\Model;

/**
 * Извлечение данных товара из Uzum Market
 *
 * Uzum хранит данные в MarketplaceProduct.raw_payload
 */
final class UzumProductExtractor implements ProductExtractorInterface
{
    public function supports(string $marketplace): bool
    {
        return $marketplace === 'uzum';
    }

    public function extract(Model $source): ProductCardDTO
    {
        /** @var MarketplaceProduct $source */
        $payload = $source->raw_payload ?? [];
        $skuList = $payload['skuList'] ?? [];
        $firstSku = $skuList[0] ?? [];

        $pictures = $this->extractPictures($payload, $skuList, $source);
        $offerId = $firstSku['vendorCode']
            ?? $firstSku['barcode']
            ?? $source->external_offer_id
            ?? 'UZUM-' . $source->external_product_id;

        return new ProductCardDTO(
            name: $payload['title'] ?? $source->title ?? '',
            offerId: $offerId,
            sourceType: 'uzum',
            sourceId: $source->id,
            description: $this->extractDescription($payload),
            brand: $payload['brand'] ?? null,
            vendorCode: $firstSku['vendorCode'] ?? $offerId,
            barcode: $firstSku['barcode'] ?? $source->external_sku ?? null,
            pictures: array_slice(array_unique($pictures), 0, 10),
            price: (float) ($source->last_synced_price ?? $firstSku['price'] ?? 0),
            category: $payload['category'] ?? $source->category ?? null,
            characteristics: $this->extractCharacteristics($payload),
            localProductId: $source->product_id,
            previewImage: $pictures[0] ?? $source->preview_image,
        );
    }

    private function extractPictures(array $payload, array $skuList, MarketplaceProduct $source): array
    {
        $pictures = [];

        // Галерея из разных полей
        $imageFields = ['photos', 'photoGallery', 'images', 'galleryImages'];
        foreach ($imageFields as $field) {
            if (! empty($payload[$field]) && is_array($payload[$field])) {
                foreach ($payload[$field] as $img) {
                    $url = is_string($img) ? $img : ($img['url'] ?? $img['photo'] ?? $img['link'] ?? null);
                    if ($url) {
                        $pictures[] = $url;
                    }
                }
                break;
            }
        }

        // Fallback на превью
        if (empty($pictures)) {
            $previewFields = ['previewImg', 'image', 'photo', 'thumbnail', 'mainImage', 'coverImage', 'photoUrl', 'imageUrl'];
            foreach ($previewFields as $field) {
                if (! empty($payload[$field])) {
                    $pictures[] = $payload[$field];
                    break;
                }
            }
        }

        if (empty($pictures) && $source->preview_image) {
            $pictures[] = $source->preview_image;
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

        return $pictures;
    }

    private function extractDescription(array $payload): string
    {
        $description = $payload['description'] ?? '';

        if (empty($description) && ! empty($payload['characteristics']) && is_array($payload['characteristics'])) {
            $charLines = [];
            foreach ($payload['characteristics'] as $char) {
                $title = $char['title'] ?? $char['name'] ?? null;
                $value = $char['value'] ?? null;
                if ($title && $value) {
                    $charLines[] = "{$title}: {$value}";
                }
            }
            if (! empty($charLines)) {
                $description = implode("\n", $charLines);
            }
        }

        return $description;
    }

    private function extractCharacteristics(array $payload): array
    {
        $chars = [];
        if (! empty($payload['characteristics']) && is_array($payload['characteristics'])) {
            foreach ($payload['characteristics'] as $char) {
                $title = $char['title'] ?? $char['name'] ?? null;
                $value = $char['value'] ?? null;
                if ($title && $value) {
                    $chars[$title] = $value;
                }
            }
        }

        return $chars;
    }
}
