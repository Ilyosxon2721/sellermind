<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class SalaryRequest extends FormRequest
{
    /**
     * Авторизация запроса — доступ проверяется в контроллере через company_id
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации расчёта зарплаты (calculate) и обновления позиции (updateItem).
     * Набор правил определяется по наличию параметров маршрута.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        // Обновление позиции расчёта зарплаты
        if ($this->route('itemId') || $this->route('item')) {
            return [
                'bonuses' => ['nullable', 'numeric', 'min:0'],
                'overtime' => ['nullable', 'numeric', 'min:0'],
                'other_deductions' => ['nullable', 'numeric', 'min:0'],
                'notes' => ['nullable', 'string'],
            ];
        }

        // Запуск расчёта зарплаты за период
        return [
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }
}
