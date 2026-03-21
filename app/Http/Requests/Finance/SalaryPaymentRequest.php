<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class SalaryPaymentRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для выплаты зарплаты
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string', 'max:500'],
            'payment_method' => ['nullable', 'string', 'max:50'],
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
            'amount.required' => 'Сумма зарплаты обязательна для заполнения.',
            'amount.min' => 'Сумма зарплаты должна быть не менее 0.01.',
            'payment_date.date' => 'Некорректный формат даты выплаты.',
            'description.max' => 'Описание не должно превышать 500 символов.',
            'payment_method.max' => 'Метод оплаты не должен превышать 50 символов.',
        ];
    }
}
