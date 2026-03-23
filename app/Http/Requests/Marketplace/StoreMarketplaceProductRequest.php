<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация привязки товара к маркетплейсу
 */
final class StoreMarketplaceProductRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
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
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'product_id' => ['required', 'exists:products,id'],
            'external_sku' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Сообщения об ошибках валидации
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'marketplace_account_id.required' => 'ID аккаунта маркетплейса обязателен.',
            'marketplace_account_id.exists' => 'Аккаунт маркетплейса не найден.',
            'product_id.required' => 'ID товара обязателен.',
            'product_id.exists' => 'Товар не найден.',
            'external_sku.max' => 'Внешний SKU не должен превышать 255 символов.',
        ];
    }
}
