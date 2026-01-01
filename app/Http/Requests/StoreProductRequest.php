<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'product' => ['required', 'array'],
            'product.name' => ['required', 'string', 'max:255'],
            'product.article' => ['required', 'string', 'max:100'],
            'product.category_id' => ['required', 'integer'],
            'product.description_short' => ['nullable', 'string'],
            'product.description_full' => ['nullable', 'string'],
            'product.is_active' => ['sometimes', 'boolean'],
            'product.is_archived' => ['sometimes', 'boolean'],

            'options' => ['sometimes', 'array'],
            'options.*.id' => ['sometimes', 'integer'],
            'options.*.code' => ['required_with:options', 'string', 'max:100'],
            'options.*.name' => ['required_with:options', 'string', 'max:255'],
            'options.*.type' => ['required_with:options', 'string'],
            'options.*.is_variant_dimension' => ['sometimes', 'boolean'],
            'options.*.values' => ['sometimes', 'array'],
            'options.*.values.*.id' => ['sometimes', 'integer'],
            'options.*.values.*.value' => ['required_with:options.*.values', 'string', 'max:255'],

            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['sometimes', 'integer'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.barcode' => ['nullable', 'string', 'max:20'],
            'variants.*.is_active' => ['sometimes', 'boolean'],
            'variants.*.option_value_ids' => ['sometimes', 'array'],
            'variants.*.option_value_ids.*' => ['integer'],

            'images' => ['sometimes', 'array'],
            'images.*.id' => ['sometimes', 'integer'],
            'images.*.file_path' => ['required_with:images', 'string', 'max:255'],
            'images.*.variant_id' => ['nullable', 'integer'],
            'images.*.is_main' => ['sometimes', 'boolean'],
            'images.*.sort_order' => ['sometimes', 'integer'],

            'attributes' => ['sometimes', 'array'],
            'attributes.*.id' => ['sometimes', 'integer'],
            'attributes.*.attribute_id' => ['required_with:attributes', 'integer'],
            'attributes.*.product_variant_id' => ['nullable', 'integer'],
            'attributes.*.value_string' => ['nullable', 'string'],
            'attributes.*.value_number' => ['nullable', 'numeric'],
            'attributes.*.value_json' => ['nullable', 'array'],

            'channel_settings' => ['sometimes', 'array'],
            'channel_settings.*.id' => ['sometimes', 'integer'],
            'channel_settings.*.channel_id' => ['sometimes', 'integer'],
            'channel_settings.*.channel_code' => ['sometimes', 'string'],
            'channel_settings.*.status' => ['sometimes', 'string'],
            'channel_settings.*.is_enabled' => ['sometimes', 'boolean'],
            'channel_settings.*.extra' => ['sometimes', 'array'],

            'channel_variants' => ['sometimes', 'array'],
            'channel_variants.*.id' => ['sometimes', 'integer'],
            'channel_variants.*.product_variant_id' => ['sometimes', 'integer'],
            'channel_variants.*.variant_sku' => ['sometimes', 'string'],
            'channel_variants.*.channel_id' => ['sometimes', 'integer'],
            'channel_variants.*.channel_code' => ['sometimes', 'string'],
            'channel_variants.*.status' => ['sometimes', 'string'],
            'channel_variants.*.extra' => ['sometimes', 'array'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $variants = collect($this->input('variants', []));
            $hasActive = $variants->isEmpty()
                ? false
                : $variants->contains(function ($variant): bool {
                    return !array_key_exists('is_active', (array) $variant) || (bool) $variant['is_active'] === true;
                });

            if (!$hasActive) {
                $validator->errors()->add('variants', 'At least one active variant is required.');
            }
        });
    }
}
