<?php

declare(strict_types=1);

namespace App\Http\Requests\Kpi;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления шкалы бонусов
 */
final class UpdateBonusScaleRequest extends FormRequest
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
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'is_default' => ['boolean'],
            'tiers' => ['sometimes', 'array', 'min:1'],
            'tiers.*.min_percent' => ['required_with:tiers', 'integer', 'min:0', 'max:300'],
            'tiers.*.max_percent' => ['nullable', 'integer', 'min:1', 'max:300'],
            'tiers.*.bonus_type' => ['required_with:tiers', 'string', 'in:fixed,percent_revenue,percent_margin'],
            'tiers.*.bonus_value' => ['required_with:tiers', 'numeric', 'min:0'],
        ];
    }

    /**
     * Кастомная валидация: max >= min, без пересечения диапазонов
     */
    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator): void {
                $tiers = $this->input('tiers', []);
                if (empty($tiers)) {
                    return;
                }

                foreach ($tiers as $i => $tier) {
                    $min = (int) ($tier['min_percent'] ?? 0);
                    $max = isset($tier['max_percent']) ? (int) $tier['max_percent'] : null;

                    if ($max !== null && $max < $min) {
                        $validator->errors()->add(
                            "tiers.{$i}.max_percent",
                            "Максимальный процент ({$max}) не может быть меньше минимального ({$min})"
                        );
                    }
                }

                $count = count($tiers);
                for ($i = 0; $i < $count; $i++) {
                    for ($j = $i + 1; $j < $count; $j++) {
                        $aMin = (int) ($tiers[$i]['min_percent'] ?? 0);
                        $aMax = isset($tiers[$i]['max_percent']) ? (int) $tiers[$i]['max_percent'] : PHP_INT_MAX;
                        $bMin = (int) ($tiers[$j]['min_percent'] ?? 0);
                        $bMax = isset($tiers[$j]['max_percent']) ? (int) $tiers[$j]['max_percent'] : PHP_INT_MAX;

                        if ($aMin <= $bMax && $bMin <= $aMax) {
                            $validator->errors()->add(
                                "tiers.{$j}.min_percent",
                                "Ступень #" . ($j + 1) . " пересекается со ступенью #" . ($i + 1)
                            );
                        }
                    }
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
            'name.max' => 'Название не может быть длиннее 255 символов',
            'tiers.min' => 'Необходимо указать хотя бы одну ступень',
            'tiers.*.min_percent.required_with' => 'Минимальный процент обязателен для каждой ступени',
            'tiers.*.min_percent.min' => 'Минимальный процент не может быть отрицательным',
            'tiers.*.min_percent.max' => 'Минимальный процент не может быть больше 300',
            'tiers.*.max_percent.min' => 'Максимальный процент должен быть не менее 1',
            'tiers.*.max_percent.max' => 'Максимальный процент не может быть больше 300',
            'tiers.*.bonus_type.required_with' => 'Тип бонуса обязателен для каждой ступени',
            'tiers.*.bonus_type.in' => 'Допустимые типы бонуса: fixed, percent_revenue, percent_margin',
            'tiers.*.bonus_value.required_with' => 'Значение бонуса обязательно для каждой ступени',
            'tiers.*.bonus_value.min' => 'Значение бонуса не может быть отрицательным',
        ];
    }
}
