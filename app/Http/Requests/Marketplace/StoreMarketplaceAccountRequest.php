<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания аккаунта маркетплейса
 */
final class StoreMarketplaceAccountRequest extends FormRequest
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
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'marketplace' => ['required', 'string', 'in:uzum,wb,ozon,ym'],
            'name' => ['nullable', 'string', 'max:255'],
            'credentials' => ['required', 'array'],
            'account_id' => ['nullable', 'integer', 'exists:marketplace_accounts,id'],
        ];
    }
}
