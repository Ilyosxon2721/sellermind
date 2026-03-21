<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация запроса метаданных заказов Wildberries
 */
final class GetOrderMetaRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:1'],
            'order_ids.*' => ['required', 'integer'],
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
            'order_ids.required' => 'Список ID заказов обязателен.',
            'order_ids.array' => 'ID заказов должны быть массивом.',
            'order_ids.min' => 'Необходим хотя бы один ID заказа.',
            'order_ids.*.required' => 'Каждый ID заказа обязателен.',
            'order_ids.*.integer' => 'ID заказа должен быть целым числом.',
        ];
    }
}
