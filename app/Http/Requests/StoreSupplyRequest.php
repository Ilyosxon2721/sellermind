<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания поставки
 */
final class StoreSupplyRequest extends FormRequest
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
            'marketplace_account_id' => ['required', 'integer', 'exists:marketplace_accounts,id'],
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'external_supply_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
