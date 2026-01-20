<?php

namespace App\Services\Products;

use App\Models\Channel;
use App\Models\Product;
use App\Models\ProductAttributeValue;
use App\Models\ProductChannelSetting;
use App\Models\ProductChannelVariantSetting;
use App\Models\ProductImage;
use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use App\Models\ProductVariant;
use App\Models\ProductVariantOptionValue;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function createProductFromDto(array $data): Product
    {
        return DB::transaction(function () use ($data): Product {
            $productData = $data['product'] ?? [];
            $product = Product::create($productData);

            $options = $this->syncOptions($product, $data['options'] ?? []);
            $variantMap = $this->syncVariants($product, $data['variants'] ?? []);
            $this->syncImages($product, $data['images'] ?? []);
            $this->syncAttributes($product, $data['attributes'] ?? []);
            $this->syncChannelSettings($product, $data['channel_settings'] ?? []);
            $this->syncChannelVariantSettings($product, $variantMap, $data['channel_variants'] ?? []);

            return $product->load([
                'options.values',
                'variants.optionValues',
                'images',
                'attributeValues.attribute',
                'channelSettings.channel',
            ]);
        });
    }

    public function updateProductFromDto(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $productData = $data['product'] ?? [];
            $product->update($productData);

            $options = $this->syncOptions($product, $data['options'] ?? []);
            $variantMap = $this->syncVariants($product, $data['variants'] ?? []);
            $this->syncImages($product, $data['images'] ?? []);
            $this->syncAttributes($product, $data['attributes'] ?? []);
            $this->syncChannelSettings($product, $data['channel_settings'] ?? []);
            $this->syncChannelVariantSettings($product, $variantMap, $data['channel_variants'] ?? []);

            return $product->load([
                'options.values',
                'variants.optionValues',
                'images',
                'attributeValues.attribute',
                'channelSettings.channel',
            ]);
        });
    }

    /**
    * @return array{byId: array<int, ProductVariant>, bySku: array<string, ProductVariant>}
    */
    protected function syncVariants(Product $product, array $variants): array
    {
        /** @var Collection<int, ProductVariant> $existing */
        $existing = $product->variants()->get()->keyBy('id');
        $keptIds = [];
        $mapById = [];
        $mapBySku = [];

        foreach ($variants as $variantData) {
            $variantId = $variantData['id'] ?? null;
            $payload = Arr::only($variantData, [
                'sku',
                'barcode',
                'article_suffix',
                'option_values_summary',
                'purchase_price',
                'price_default',
                'old_price_default',
                'stock_default',
                'weight_g',
                'length_mm',
                'width_mm',
                'height_mm',
                'main_image_id',
                'is_active',
                'is_deleted',
            ]);
            $payload['company_id'] = $product->company_id;

            if ($variantId && $existing->has($variantId)) {
                $variant = $existing->get($variantId);
                $variant->update($payload);
            } else {
                // Check if variant with this SKU already exists for this product
                $existingBySku = $product->variants()
                    ->where('sku', $payload['sku'] ?? null)
                    ->first();

                if ($existingBySku) {
                    $existingBySku->update($payload);
                    $variant = $existingBySku;
                } else {
                    $variant = $product->variants()->create($payload);
                }
            }

            $keptIds[] = $variant->id;
            $mapById[$variant->id] = $variant;
            $mapBySku[strtolower($variant->sku)] = $variant;

            $this->syncVariantOptionValues($variant, $variantData['option_value_ids'] ?? []);
        }

        if ($existing->isNotEmpty()) {
            $product->variants()
                ->whereNotIn('id', $keptIds)
                ->update([
                    'is_deleted' => true,
                    'is_active' => false,
                ]);
        }

        return ['byId' => $mapById, 'bySku' => $mapBySku];
    }

    protected function syncOptions(Product $product, array $options): array
    {
        /** @var Collection<int, ProductOption> $existing */
        $existing = $product->options()->with('values')->get()->keyBy('id');
        $keptIds = [];

        foreach ($options as $optionData) {
            $optionId = $optionData['id'] ?? null;
            $payload = Arr::only($optionData, ['code', 'name', 'type', 'is_variant_dimension']);
            $payload['company_id'] = $product->company_id;

            if ($optionId && $existing->has($optionId)) {
                $option = $existing->get($optionId);
                $option->update($payload);
            } else {
                $option = $product->options()->create($payload);
            }

            $keptIds[] = $option->id;
            $this->syncOptionValues($option, $optionData['values'] ?? []);
        }

        if ($existing->isNotEmpty()) {
            $product->options()->whereNotIn('id', $keptIds)->delete();
        }

        return $options;
    }

    protected function syncOptionValues(ProductOption $option, array $values): void
    {
        /** @var Collection<int, ProductOptionValue> $existing */
        $existing = $option->values()->get()->keyBy('id');
        $keptIds = [];

        foreach ($values as $valueData) {
            $valueId = $valueData['id'] ?? null;
            $payload = Arr::only($valueData, ['value', 'code', 'color_hex', 'sort_order']);
            $payload['company_id'] = $option->company_id;

            if ($valueId && $existing->has($valueId)) {
                $value = $existing->get($valueId);
                $value->update($payload);
            } else {
                $value = $option->values()->create($payload);
            }

            $keptIds[] = $value->id;
        }

        if ($existing->isNotEmpty()) {
            $option->values()->whereNotIn('id', $keptIds)->delete();
        }
    }

    protected function syncVariantOptionValues(ProductVariant $variant, array $optionValueIds): void
    {
        $optionValueIds = collect($optionValueIds)->filter()->unique()->values();

        $existing = ProductVariantOptionValue::query()
            ->where('product_variant_id', $variant->id)
            ->get()
            ->keyBy('product_option_value_id');

        $toDelete = $existing->keys()->diff($optionValueIds);
        if ($toDelete->isNotEmpty()) {
            ProductVariantOptionValue::query()
                ->where('product_variant_id', $variant->id)
                ->whereIn('product_option_value_id', $toDelete->all())
                ->delete();
        }

        foreach ($optionValueIds as $optionValueId) {
            if (!$existing->has($optionValueId)) {
                ProductVariantOptionValue::create([
                    'company_id' => $variant->company_id,
                    'product_variant_id' => $variant->id,
                    'product_option_value_id' => $optionValueId,
                ]);
            }
        }
    }

    protected function syncImages(Product $product, array $images): void
    {
        /** @var Collection<int, ProductImage> $existing */
        $existing = $product->images()->get()->keyBy('id');

        foreach ($images as $imageData) {
            $imageId = $imageData['id'] ?? null;
            $payload = Arr::only($imageData, ['variant_id', 'file_path', 'alt_text', 'is_main', 'sort_order']);
            $payload['company_id'] = $product->company_id;
            
            // Fix: Convert empty string to null for variant_id
            if (array_key_exists('variant_id', $payload) && ($payload['variant_id'] === '' || $payload['variant_id'] === 0)) {
                $payload['variant_id'] = null;
            }

            if ($imageId && $existing->has($imageId)) {
                $existing->get($imageId)->update($payload);
            } else {
                $product->images()->create($payload);
            }
        }
    }

    protected function syncAttributes(Product $product, array $attributes): void
    {
        foreach ($attributes as $attributeData) {
            $valueId = $attributeData['id'] ?? null;
            $payload = Arr::only($attributeData, [
                'product_id',
                'product_variant_id',
                'attribute_id',
                'value_string',
                'value_number',
                'value_json',
            ]);
            $payload['company_id'] = $product->company_id;
            $payload['product_id'] = $payload['product_id'] ?? ($payload['product_variant_id'] ? null : $product->id);

            if ($valueId) {
                ProductAttributeValue::query()->updateOrCreate(
                    ['id' => $valueId],
                    $payload
                );
            } else {
                ProductAttributeValue::create($payload);
            }
        }
    }

    protected function syncChannelSettings(Product $product, array $settings): void
    {
        foreach ($settings as $setting) {
            $channelId = $this->resolveChannelId($setting);
            if (!$channelId) {
                continue;
            }

            $payload = Arr::only($setting, [
                'external_product_id',
                'category_external_id',
                'name_override',
                'description_override',
                'brand_external_id',
                'brand_external_name',
                'is_enabled',
                'status',
                'last_synced_at',
                'last_sync_status_message',
                'extra',
            ]);
            $payload['company_id'] = $product->company_id;
            $payload['product_id'] = $product->id;
            $payload['channel_id'] = $channelId;

            ProductChannelSetting::updateOrCreate(
                [
                    'product_id' => $product->id,
                    'channel_id' => $channelId,
                ],
                $payload
            );
        }
    }

    protected function syncChannelVariantSettings(Product $product, array $variantMap, array $settings): void
    {
        /** @var array<int, ProductVariant> $byId */
        $byId = $variantMap['byId'] ?? [];
        /** @var array<string, ProductVariant> $bySku */
        $bySku = $variantMap['bySku'] ?? [];

        foreach ($settings as $setting) {
            $variant = null;
            if (!empty($setting['product_variant_id']) && isset($byId[$setting['product_variant_id']])) {
                $variant = $byId[$setting['product_variant_id']];
            } elseif (!empty($setting['variant_sku'])) {
                $key = strtolower($setting['variant_sku']);
                $variant = $bySku[$key] ?? null;
            }

            $channelId = $this->resolveChannelId($setting);
            if (!$variant || !$channelId) {
                continue;
            }

            $payload = Arr::only($setting, [
                'external_offer_id',
                'price',
                'old_price',
                'stock',
                'status',
                'last_synced_at',
                'extra',
            ]);
            $payload['company_id'] = $product->company_id;
            $payload['product_variant_id'] = $variant->id;
            $payload['channel_id'] = $channelId;

            ProductChannelVariantSetting::updateOrCreate(
                [
                    'product_variant_id' => $variant->id,
                    'channel_id' => $channelId,
                ],
                $payload
            );
        }
    }

    protected function resolveChannelId(array $input): ?int
    {
        if (!empty($input['channel_id'])) {
            return (int) $input['channel_id'];
        }

        if (!empty($input['channel_code'])) {
            $channel = Channel::query()->where('code', $input['channel_code'])->first();
            return $channel?->id;
        }

        return null;
    }
}
