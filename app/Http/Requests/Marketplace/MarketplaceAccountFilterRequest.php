<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация фильтрации по аккаунту маркетплейса.
 * Используется для методов unlinkedProducts, availableProducts.
 */
final class MarketplaceAccountFilterRequest extends FormRequest
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
        ];
    }
}
