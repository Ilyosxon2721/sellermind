<?php

declare(strict_types=1);

namespace App\Http\Requests\Kpi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Валидация создания KPI-плана
 */
final class StoreKpiPlanRequest extends FormRequest
{
    /**
     * Авторизация через middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where('company_id', $companyId),
            ],
            'kpi_sales_sphere_id' => [
                'required',
                'integer',
                Rule::exists('kpi_sales_spheres', 'id')->where('company_id', $companyId),
            ],
            'kpi_bonus_scale_id' => [
                'required',
                'integer',
                Rule::exists('kpi_bonus_scales', 'id')->where('company_id', $companyId),
            ],
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'target_revenue' => ['required', 'numeric', 'min:0'],
            'target_margin' => ['required', 'numeric', 'min:0'],
            'target_orders' => ['required', 'integer', 'min:0'],
            'weight_revenue' => ['required', 'integer', 'min:0', 'max:100'],
            'weight_margin' => ['required', 'integer', 'min:0', 'max:100'],
            'weight_orders' => ['required', 'integer', 'min:0', 'max:100'],
            'currency' => ['sometimes', 'string', 'in:UZS,USD,RUB,EUR'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Кастомная валидация: сумма весов должна быть 100
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $weightRevenue = (int) $this->input('weight_revenue', 0);
                $weightMargin = (int) $this->input('weight_margin', 0);
                $weightOrders = (int) $this->input('weight_orders', 0);
                $weightSum = $weightRevenue + $weightMargin + $weightOrders;

                if ($weightSum !== 100) {
                    $validator->errors()->add(
                        'weight_revenue',
                        "Сумма весов должна быть 100 (сейчас: {$weightSum})"
                    );
                }
            },
        ];
    }

    /**
     * Сообщения об ошибках на русском
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'employee_id.required' => 'Сотрудник обязателен',
            'employee_id.exists' => 'Сотрудник не найден в вашей компании',
            'kpi_sales_sphere_id.required' => 'Сфера продаж обязательна',
            'kpi_sales_sphere_id.exists' => 'Сфера продаж не найдена в вашей компании',
            'kpi_bonus_scale_id.required' => 'Шкала бонусов обязательна',
            'kpi_bonus_scale_id.exists' => 'Шкала бонусов не найдена в вашей компании',
            'period_year.required' => 'Год периода обязателен',
            'period_year.min' => 'Год не может быть меньше 2020',
            'period_year.max' => 'Год не может быть больше 2100',
            'period_month.required' => 'Месяц периода обязателен',
            'period_month.min' => 'Месяц должен быть от 1 до 12',
            'period_month.max' => 'Месяц должен быть от 1 до 12',
            'target_revenue.required' => 'Целевая выручка обязательна',
            'target_revenue.min' => 'Целевая выручка не может быть отрицательной',
            'target_margin.required' => 'Целевая маржа обязательна',
            'target_margin.min' => 'Целевая маржа не может быть отрицательной',
            'target_orders.required' => 'Целевое количество заказов обязательно',
            'target_orders.min' => 'Целевое количество заказов не может быть отрицательным',
            'weight_revenue.required' => 'Вес выручки обязателен',
            'weight_revenue.min' => 'Вес выручки не может быть отрицательным',
            'weight_revenue.max' => 'Вес выручки не может быть больше 100',
            'weight_margin.required' => 'Вес маржи обязателен',
            'weight_margin.min' => 'Вес маржи не может быть отрицательным',
            'weight_margin.max' => 'Вес маржи не может быть больше 100',
            'weight_orders.required' => 'Вес заказов обязателен',
            'weight_orders.min' => 'Вес заказов не может быть отрицательным',
            'weight_orders.max' => 'Вес заказов не может быть больше 100',
            'notes.max' => 'Заметки не могут быть длиннее 1000 символов',
        ];
    }
}
