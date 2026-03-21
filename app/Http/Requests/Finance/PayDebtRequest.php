<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class PayDebtRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для оплаты долга
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['nullable', 'in:cash,bank,card'],
            'reference' => ['nullable', 'string', 'max:64'],
            'notes' => ['nullable', 'string'],
            'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
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
            'amount.required' => 'Сумма платежа обязательна для заполнения.',
            'amount.min' => 'Сумма платежа должна быть не менее 0.01.',
            'payment_date.required' => 'Дата платежа обязательна для заполнения.',
            'payment_date.date' => 'Некорректный формат даты платежа.',
            'payment_method.in' => 'Метод оплаты должен быть: наличные, банк или карта.',
            'reference.max' => 'Ссылка не должна превышать 64 символа.',
            'cash_account_id.exists' => 'Выбранный денежный счёт не найден.',
        ];
    }
}
