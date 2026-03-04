<?php

declare(strict_types=1);

namespace App\Http\Requests\Pricing;

use Illuminate\Foundation\Http\FormRequest;

final class CalculatorRequest extends FormRequest
{
    /**
     * Авторизация запроса
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации калькулятора цен.
     * Набор правил зависит от action-метода: calculate, calculateForMargin или compare.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        $action = $this->route()->getActionMethod();

        // Сравнение маркетплейсов не требует marketplace/fulfillment_type
        if ($action === 'compare') {
            return [
                'cost_price' => 'required|numeric|min:0',
                'packaging_cost' => 'nullable|numeric|min:0',
                'delivery_to_warehouse' => 'nullable|numeric|min:0',
                'other_costs' => 'nullable|numeric|min:0',
                'length_cm' => 'nullable|numeric|min:0',
                'width_cm' => 'nullable|numeric|min:0',
                'height_cm' => 'nullable|numeric|min:0',
                'weight_kg' => 'nullable|numeric|min:0',
                'storage_cost' => 'nullable|numeric|min:0',
                'target_margin_percent' => 'nullable|numeric|min:0|max:99',
                'marketplaces' => 'nullable|array',
                'marketplaces.*' => 'string|in:wildberries,ozon,yandex,uzum',
            ];
        }

        // Общие поля для calculate и calculateForMargin
        $rules = [
            'marketplace' => 'required|string|in:wildberries,ozon,yandex,uzum',
            'fulfillment_type' => 'required|string|in:fbo,fbs,dbs,express',
            'category_id' => 'nullable|integer|exists:marketplace_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'delivery_to_warehouse' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'storage_cost' => 'nullable|numeric|min:0',
        ];

        if ($action === 'calculateForMargin') {
            // Целевая маржа обязательна при обратном расчёте
            $rules['target_margin_percent'] = 'required|numeric|min:0|max:99';
        } else {
            // При обычном расчёте целевая маржа и цена опциональны
            $rules['target_margin_percent'] = 'nullable|numeric|min:0|max:99';
            $rules['price'] = 'nullable|numeric|min:0';
        }

        return $rules;
    }
}
