<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

final class StoreSaleRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для создания продажи
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'counterparty_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'sale_number' => ['nullable', 'string', 'max:100'],
            'sale_type' => ['required', 'in:retail,wholesale,direct'],
            'status' => ['nullable', 'in:draft,confirmed,shipped,delivered,cancelled,returned'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'currency_code' => ['nullable', 'string', 'max:3'],
            'payment_status' => ['nullable', 'in:unpaid,partial,paid'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'sale_date' => ['required', 'date'],
            'shipped_date' => ['nullable', 'date'],
            'delivered_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['nullable', 'array'],
            'items.*.sku_id' => ['nullable', 'integer'],
            'items.*.product_id' => ['nullable', 'integer'],
            'items.*.sku_code' => ['nullable', 'string'],
            'items.*.product_name' => ['nullable', 'string'],
            'items.*.quantity' => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
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
            'sale_type.required' => 'Тип продажи обязателен для заполнения.',
            'sale_type.in' => 'Тип продажи должен быть: розничная, оптовая или прямая.',
            'status.in' => 'Недопустимый статус продажи.',
            'customer_email.email' => 'Некорректный формат email клиента.',
            'discount_amount.min' => 'Сумма скидки не может быть отрицательной.',
            'currency_code.max' => 'Код валюты не должен превышать 3 символа.',
            'payment_status.in' => 'Статус оплаты должен быть: не оплачен, частично или оплачен.',
            'paid_amount.min' => 'Сумма оплаты не может быть отрицательной.',
            'sale_date.required' => 'Дата продажи обязательна для заполнения.',
            'sale_date.date' => 'Некорректный формат даты продажи.',
            'items.*.quantity.required' => 'Количество товара обязательно для заполнения.',
            'items.*.quantity.min' => 'Количество товара должно быть больше нуля.',
            'items.*.unit_price.required' => 'Цена за единицу обязательна для заполнения.',
            'items.*.unit_price.min' => 'Цена за единицу не может быть отрицательной.',
            'items.*.discount_percent.max' => 'Скидка не может превышать 100%.',
        ];
    }
}
