<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class CashAccountRequest extends FormRequest
{
    /**
     * Авторизация запроса — доступ проверяется в контроллере через company_id
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации счёта (store/update).
     * При обновлении (PUT/PATCH) поля name/type становятся «sometimes».
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');
        $nameRule = $isUpdate ? 'sometimes' : 'required';
        $typeRule = $isUpdate ? 'sometimes' : 'required';

        return [
            'name' => [$nameRule, 'string', 'max:255'],
            'type' => [$typeRule, 'in:cash,bank,card,ewallet,marketplace,other'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'initial_balance' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'bik' => ['nullable', 'string', 'max:50'],
            'card_number' => ['nullable', 'string', 'max:4'],
            'marketplace_account_id' => ['nullable', 'exists:marketplace_accounts,id'],
            'marketplace' => ['nullable', 'string', 'max:32'],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
