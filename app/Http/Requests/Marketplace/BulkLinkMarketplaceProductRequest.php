<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация массовой привязки товаров к маркетплейсу
 */
final class BulkLinkMarketplaceProductRequest extends FormRequest
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
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['exists:products,id'],
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
            'product_ids.required' => 'Список ID товаров обязателен.',
            'product_ids.array' => 'ID товаров должны быть массивом.',
            'product_ids.*.exists' => 'Один из товаров не найден.',
        ];
    }
}
