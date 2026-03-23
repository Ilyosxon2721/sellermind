<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class CorrectDebtRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для корректировки суммы долга
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'original_amount' => ['required', 'numeric', 'min:0.01'],
            'currency_code' => ['required', 'string', 'max:8'],
            'correction_reason' => ['nullable', 'string', 'max:500'],
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
            'original_amount.required' => 'Сумма долга обязательна для заполнения.',
            'original_amount.min' => 'Сумма долга должна быть не менее 0.01.',
            'currency_code.required' => 'Код валюты обязателен для заполнения.',
            'currency_code.max' => 'Код валюты не должен превышать 8 символов.',
            'correction_reason.max' => 'Причина корректировки не должна превышать 500 символов.',
        ];
    }
}
