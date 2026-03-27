<?php

declare(strict_types=1);

namespace App\Services\Products\Extractors;

use App\Models\Product;
use App\Services\Products\DTO\ProductCardDTO;
use Illuminate\Database\Eloquent\Model;

/**
 * Извлечение данных из локальной карточки товара (Product)
 */
final class LocalProductExtractor implements ProductExtractorInterface
{
    public function supports(string $marketplace): bool
    {
        return $marketplace === 'local';
    }

    public function extract(Model $source): ProductCardDTO
    {
        /** @var Product $source */
        $source->loadMissing(['images', 'variants', 'attributeValues']);

        $pictures = $source->images
            ->sortByDesc('is_main')
            ->pluck('url')
            ->filter()
            ->values()
            ->toArray();

        $barcode = null;
        $vendorCode = $source->article;
        if ($source->variants->isNotEmpty()) {
            $firstVariant = $source->variants->first();
            $barcode = $firstVariant->barcode ?? $firstVariant->ean ?? null;
            $vendorCode = $firstVariant->sku ?? $source->article;
        }

        $characteristics = [];
        foreach ($source->attributeValues as $av) {
            if ($av->attribute && $av->value) {
                $characteristics[$av->attribute->name] = $av->value;
            }
        }

        return new ProductCardDTO(
            name: $source->name,
            offerId: $source->article ?? 'LOCAL-' . $source->id,
            sourceType: 'local',
            sourceId: $source->id,
            description: $source->description_full ?? $source->description_short ?? '',
            brand: $source->brand_name,
            vendorCode: $vendorCode,
            barcode: $barcode,
            pictures: array_slice($pictures, 0, 10),
            price: 0,
            category: $source->category?->name,
            characteristics: $characteristics,
            weightG: $source->package_weight_g,
            lengthMm: $source->package_length_mm,
            widthMm: $source->package_width_mm,
            heightMm: $source->package_height_mm,
            localProductId: $source->id,
            previewImage: $pictures[0] ?? null,
        );
    }
}
