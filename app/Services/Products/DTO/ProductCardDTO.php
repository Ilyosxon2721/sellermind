<?php

declare(strict_types=1);

namespace App\Services\Products\DTO;

/**
 * Универсальный DTO карточки товара для копирования между маркетплейсами
 */
final class ProductCardDTO
{
    public function __construct(
        public readonly string $name,
        public readonly string $offerId,
        public readonly string $sourceType,
        public readonly int $sourceId,
        public readonly ?string $description = null,
        public readonly ?string $brand = null,
        public readonly ?string $vendorCode = null,
        public readonly ?string $barcode = null,
        public readonly array $pictures = [],
        public readonly float $price = 0,
        public readonly ?float $oldPrice = null,
        public readonly ?string $category = null,
        public readonly array $characteristics = [],
        public readonly ?int $weightG = null,
        public readonly ?int $lengthMm = null,
        public readonly ?int $widthMm = null,
        public readonly ?int $heightMm = null,
        public readonly ?int $localProductId = null,
        public readonly ?string $previewImage = null,
    ) {}

    /**
     * Конвертация в raw_payload для MarketplaceProduct
     */
    public function toRawPayload(): array
    {
        return array_filter([
            'source' => $this->sourceType,
            'source_id' => $this->sourceId,
            'name' => $this->name,
            'description' => $this->description,
            'brand' => $this->brand,
            'vendor_code' => $this->vendorCode,
            'barcode' => $this->barcode,
            'pictures' => $this->pictures,
            'price' => $this->price,
            'old_price' => $this->oldPrice,
            'category' => $this->category,
            'characteristics' => $this->characteristics,
            'weight_g' => $this->weightG,
            'length_mm' => $this->lengthMm,
            'width_mm' => $this->widthMm,
            'height_mm' => $this->heightMm,
        ], fn ($v) => $v !== null && $v !== '' && $v !== []);
    }
}
