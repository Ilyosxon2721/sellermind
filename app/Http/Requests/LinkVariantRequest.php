<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация привязки варианта товара к маркетплейсу
 */
final class LinkVariantRequest extends FormRequest
{
    /**
     * Определяет авторизацию запроса
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'product_variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'external_sku_id' => ['nullable', 'string', 'max:255'],
            'marketplace_barcode' => ['nullable', 'string', 'max:100'],
            'sync_stock_enabled' => ['nullable', 'boolean'],
            'sync_price_enabled' => ['nullable', 'boolean'],
        ];
    }
}
