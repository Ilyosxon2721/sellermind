<?php

declare(strict_types=1);

namespace App\Services\Products\Extractors;

use App\Models\WildberriesProduct;
use App\Services\Products\DTO\ProductCardDTO;
use Illuminate\Database\Eloquent\Model;

/**
 * Извлечение данных товара из Wildberries
 */
final class WildberriesProductExtractor implements ProductExtractorInterface
{
    public function supports(string $marketplace): bool
    {
        return in_array($marketplace, ['wb', 'wildberries']);
    }

    public function extract(Model $source): ProductCardDTO
    {
        /** @var WildberriesProduct $source */
        $pictures = $this->extractPictures($source);
        $description = $this->extractDescription($source);
        $offerId = $source->vendor_code ?? $source->supplier_article ?? 'WB-' . $source->nm_id;

        return new ProductCardDTO(
            name: $source->title ?? '',
            offerId: $offerId,
            sourceType: 'wb',
            sourceId: $source->id,
            description: $description,
            brand: $source->brand,
            vendorCode: $source->vendor_code,
            barcode: $source->barcode,
            pictures: array_slice($pictures, 0, 10),
            price: (float) ($source->price ?? 0),
            category: $source->subject_name,
            characteristics: $this->extractCharacteristics($source),
            localProductId: $source->local_product_id,
            previewImage: $pictures[0] ?? null,
        );
    }

    private function extractPictures(WildberriesProduct $product): array
    {
        $pictures = [];
        if (! empty($product->photos) && is_array($product->photos)) {
            foreach ($product->photos as $photo) {
                $url = is_string($photo) ? $photo : ($photo['url'] ?? $photo['big'] ?? $photo['c246x328'] ?? null);
                if ($url) {
                    $pictures[] = $url;
                }
            }
        }

        return $pictures;
    }

    private function extractDescription(WildberriesProduct $product): string
    {
        $description = $product->description ?? '';

        if (empty($description) && ! empty($product->characteristics) && is_array($product->characteristics)) {
            $charLines = [];
            foreach ($product->characteristics as $char) {
                $name = $char['name'] ?? $char['charcName'] ?? null;
                $value = $char['value'] ?? ($char['values'] ?? null);
                if ($name && $value) {
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $charLines[] = "{$name}: {$value}";
                }
            }
            if (! empty($charLines)) {
                $description = implode("\n", $charLines);
            }
        }

        return $description;
    }

    private function extractCharacteristics(WildberriesProduct $product): array
    {
        $chars = [];
        if (! empty($product->characteristics) && is_array($product->characteristics)) {
            foreach ($product->characteristics as $char) {
                $name = $char['name'] ?? $char['charcName'] ?? null;
                $value = $char['value'] ?? ($char['values'] ?? null);
                if ($name && $value) {
                    if (is_array($value)) {
                        $value = implode(', ', $value);
                    }
                    $chars[$name] = $value;
                }
            }
        }

        return $chars;
    }
}
