<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class PenaltyDeductionRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для начисления штрафа
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:500'],
            'penalty_date' => ['nullable', 'date'],
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
            'amount.required' => 'Сумма штрафа обязательна для заполнения.',
            'amount.min' => 'Сумма штрафа должна быть не менее 0.01.',
            'reason.required' => 'Причина штрафа обязательна для заполнения.',
            'reason.max' => 'Причина штрафа не должна превышать 500 символов.',
            'penalty_date.date' => 'Некорректный формат даты штрафа.',
        ];
    }
}
