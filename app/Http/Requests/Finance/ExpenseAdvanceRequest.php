<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class ExpenseAdvanceRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для добавления расхода на сотрудника
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'description' => ['required', 'string', 'max:500'],
            'expense_date' => ['nullable', 'date'],
            'expense_type' => ['nullable', 'string', 'in:advance,equipment,training,travel,other'],
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
            'amount.required' => 'Сумма расхода обязательна для заполнения.',
            'amount.min' => 'Сумма расхода должна быть не менее 0.01.',
            'description.required' => 'Описание расхода обязательно для заполнения.',
            'description.max' => 'Описание расхода не должно превышать 500 символов.',
            'expense_date.date' => 'Некорректный формат даты расхода.',
            'expense_type.in' => 'Тип расхода должен быть: аванс, оборудование, обучение, командировка или прочее.',
        ];
    }
}
