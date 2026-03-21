<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class StoreDebtRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для создания долга
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', 'in:receivable,payable'],
            'purpose' => ['nullable', 'in:debt,prepayment,advance,loan,other'],
            'counterparty_id' => ['nullable', 'integer'],
            'counterparty_entity_id' => ['nullable', 'integer', 'exists:counterparties,id'],
            'employee_id' => ['nullable', 'integer'],
            'description' => ['required', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'debt_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
            'notes' => ['nullable', 'string'],
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
            'type.required' => 'Тип долга обязателен для заполнения.',
            'type.in' => 'Тип долга должен быть: дебиторская или кредиторская задолженность.',
            'purpose.in' => 'Недопустимое назначение долга.',
            'description.required' => 'Описание долга обязательно для заполнения.',
            'description.max' => 'Описание не должно превышать 255 символов.',
            'original_amount.required' => 'Сумма долга обязательна для заполнения.',
            'original_amount.min' => 'Сумма долга должна быть не менее 0.01.',
            'currency_code.max' => 'Код валюты не должен превышать 8 символов.',
            'debt_date.required' => 'Дата долга обязательна для заполнения.',
            'debt_date.date' => 'Некорректный формат даты долга.',
            'due_date.date' => 'Некорректный формат даты погашения.',
            'interest_rate.min' => 'Процентная ставка не может быть отрицательной.',
            'counterparty_entity_id.exists' => 'Выбранный контрагент не найден.',
            'cash_account_id.exists' => 'Выбранный денежный счёт не найден.',
        ];
    }
}
