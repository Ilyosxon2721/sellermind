<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация поиска товаров для привязки к маркетплейсу
 */
final class SearchMarketplaceProductRequest extends FormRequest
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
            'query' => ['required', 'string', 'min:1', 'max:255'],
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
            'query.required' => 'Поисковый запрос обязателен.',
            'query.min' => 'Поисковый запрос должен содержать хотя бы 1 символ.',
            'query.max' => 'Поисковый запрос не должен превышать 255 символов.',
        ];
    }
}
