<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания контрагента
 */
final class StoreCounterpartyRequest extends FormRequest
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
            'type' => ['required', 'in:individual,legal'],
            'name' => ['required', 'string', 'max:255'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'inn' => ['nullable', 'string', 'max:20'],
            'kpp' => ['nullable', 'string', 'max:20'],
            'ogrn' => ['nullable', 'string', 'max:20'],
            'okpo' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:100'],
            'website' => ['nullable', 'url', 'max:255'],
            'legal_address' => ['nullable', 'string', 'max:500'],
            'actual_address' => ['nullable', 'string', 'max:500'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_bik' => ['nullable', 'string', 'max:20'],
            'bank_account' => ['nullable', 'string', 'max:30'],
            'bank_corr_account' => ['nullable', 'string', 'max:30'],
            'contact_person' => ['nullable', 'string', 'max:100'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'contact_position' => ['nullable', 'string', 'max:100'],
            'is_supplier' => ['boolean'],
            'is_customer' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
