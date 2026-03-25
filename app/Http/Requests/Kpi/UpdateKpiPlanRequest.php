<?php

declare(strict_types=1);

namespace App\Http\Requests\Kpi;

use App\Models\Kpi\KpiPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Валидация обновления KPI-плана
 */
final class UpdateKpiPlanRequest extends FormRequest
{
    /**
     * Авторизация: запрещаем редактирование утверждённых планов
     */
    public function authorize(): bool
    {
        $companyId = auth()->user()->company_id;

        $plan = KpiPlan::byCompany($companyId)->find($this->route('id'));

        if (! $plan) {
            return true; // Пусть контроллер обработает 404
        }

        return $plan->status !== KpiPlan::STATUS_APPROVED;
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
            'kpi_bonus_scale_id' => [
                'sometimes',
                'integer',
                Rule::exists('kpi_bonus_scales', 'id')->where('company_id', $companyId),
            ],
            'target_revenue' => ['sometimes', 'numeric', 'min:0'],
            'target_margin' => ['sometimes', 'numeric', 'min:0'],
            'target_orders' => ['sometimes', 'integer', 'min:0'],
            'weight_revenue' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'weight_margin' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'weight_orders' => ['sometimes', 'integer', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Кастомная валидация: сумма весов должна быть 100 (с учётом текущих значений плана)
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $companyId = auth()->user()->company_id;
                $plan = KpiPlan::byCompany($companyId)->find($this->route('id'));

                if (! $plan) {
                    return;
                }

                $weightRevenue = $this->has('weight_revenue')
                    ? (int) $this->input('weight_revenue')
                    : $plan->weight_revenue;
                $weightMargin = $this->has('weight_margin')
                    ? (int) $this->input('weight_margin')
                    : $plan->weight_margin;
                $weightOrders = $this->has('weight_orders')
                    ? (int) $this->input('weight_orders')
                    : $plan->weight_orders;

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
            'kpi_bonus_scale_id.exists' => 'Шкала бонусов не найдена в вашей компании',
            'target_revenue.min' => 'Целевая выручка не может быть отрицательной',
            'target_margin.min' => 'Целевая маржа не может быть отрицательной',
            'target_orders.min' => 'Целевое количество заказов не может быть отрицательным',
            'weight_revenue.min' => 'Вес выручки не может быть отрицательным',
            'weight_revenue.max' => 'Вес выручки не может быть больше 100',
            'weight_margin.min' => 'Вес маржи не может быть отрицательным',
            'weight_margin.max' => 'Вес маржи не может быть больше 100',
            'weight_orders.min' => 'Вес заказов не может быть отрицательным',
            'weight_orders.max' => 'Вес заказов не может быть больше 100',
            'notes.max' => 'Заметки не могут быть длиннее 1000 символов',
        ];
    }

    /**
     * Сообщение при неудачной авторизации
     */
    protected function failedAuthorization(): void
    {
        abort(422, 'Нельзя редактировать утверждённый план');
    }
}
