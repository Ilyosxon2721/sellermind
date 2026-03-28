<?php

declare(strict_types=1);

namespace App\Services\Products\Extractors;

use App\Models\OzonProduct;
use App\Services\Products\DTO\ProductCardDTO;
use Illuminate\Database\Eloquent\Model;

/**
 * Извлечение данных товара из Ozon
 */
final class OzonProductExtractor implements ProductExtractorInterface
{
    public function supports(string $marketplace): bool
    {
        return $marketplace === 'ozon';
    }

    public function extract(Model $source): ProductCardDTO
    {
        /** @var OzonProduct $source */
        $pictures = $this->extractPictures($source);
        $offerId = $source->external_offer_id ?? 'OZON-' . $source->external_product_id;

        return new ProductCardDTO(
            name: $source->name ?? '',
            offerId: $offerId,
            sourceType: 'ozon',
            sourceId: $source->id,
            description: $source->description ?? '',
            barcode: $source->barcode,
            pictures: array_slice($pictures, 0, 10),
            price: (float) ($source->price ?? 0),
            weightG: $source->weight ? (int) $source->weight : null,
            widthMm: $source->width ? (int) $source->width : null,
            heightMm: $source->height ? (int) $source->height : null,
            lengthMm: $source->depth ? (int) $source->depth : null,
            localProductId: $source->product_id,
            previewImage: $pictures[0] ?? null,
        );
    }

    private function extractPictures(OzonProduct $product): array
    {
        $pictures = [];
        if (! empty($product->images) && is_array($product->images)) {
            foreach ($product->images as $img) {
                $url = is_string($img) ? $img : ($img['url'] ?? $img['file_name'] ?? null);
                if ($url) {
                    $pictures[] = $url;
                }
            }
        }

        return $pictures;
    }
}
