<?php

declare(strict_types=1);

namespace App\Services\Products\Extractors;

use App\Models\MarketplaceProduct;
use App\Services\Products\DTO\ProductCardDTO;
use Illuminate\Database\Eloquent\Model;

/**
 * Извлечение данных товара из Yandex Market
 *
 * YM хранит данные в MarketplaceProduct.raw_payload
 */
final class YandexMarketProductExtractor implements ProductExtractorInterface
{
    public function supports(string $marketplace): bool
    {
        return in_array($marketplace, ['ym', 'yandex_market']);
    }

    public function extract(Model $source): ProductCardDTO
    {
        /** @var MarketplaceProduct $source */
        $payload = $source->raw_payload ?? [];

        $pictures = $payload['pictures'] ?? [];
        if (empty($pictures) && $source->preview_image) {
            $pictures = [$source->preview_image];
        }

        $offerId = $source->external_offer_id
            ?? $payload['vendor_code']
            ?? 'YM-' . $source->external_product_id;

        return new ProductCardDTO(
            name: $payload['name'] ?? $source->title ?? '',
            offerId: $offerId,
            sourceType: 'ym',
            sourceId: $source->id,
            description: $payload['description'] ?? '',
            brand: $payload['brand'] ?? null,
            vendorCode: $payload['vendor_code'] ?? $offerId,
            barcode: $payload['barcode'] ?? null,
            pictures: array_slice($pictures, 0, 10),
            price: (float) ($source->last_synced_price ?? $payload['price'] ?? 0),
            category: $payload['category'] ?? $source->category ?? null,
            weightG: isset($payload['weight_g']) ? (int) $payload['weight_g'] : null,
            widthMm: isset($payload['width_mm']) ? (int) $payload['width_mm'] : null,
            heightMm: isset($payload['height_mm']) ? (int) $payload['height_mm'] : null,
            lengthMm: isset($payload['depth_mm']) ? (int) $payload['depth_mm'] : null,
            localProductId: $source->product_id,
            previewImage: $pictures[0] ?? $source->preview_image,
        );
    }
}
