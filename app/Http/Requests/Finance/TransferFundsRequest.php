<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class TransferFundsRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для перевода между счетами
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'from_account_id' => ['required', 'exists:cash_accounts,id'],
            'to_account_id' => ['required', 'exists:cash_accounts,id', 'different:from_account_id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['nullable', 'string'],
            'transaction_date' => ['nullable', 'date'],
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
            'from_account_id.required' => 'Счёт списания обязателен для заполнения.',
            'from_account_id.exists' => 'Счёт списания не найден.',
            'to_account_id.required' => 'Счёт зачисления обязателен для заполнения.',
            'to_account_id.exists' => 'Счёт зачисления не найден.',
            'to_account_id.different' => 'Счёт зачисления должен отличаться от счёта списания.',
            'amount.required' => 'Сумма перевода обязательна для заполнения.',
            'amount.min' => 'Сумма перевода должна быть не менее 0.01.',
            'transaction_date.date' => 'Некорректный формат даты транзакции.',
        ];
    }
}
