<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация массового прикрепления метаданных к заказам Wildberries
 */
final class BatchAttachOrderMetaRequest extends FormRequest
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
            'metadata' => ['required', 'array', 'min:1'],
            'metadata.*.order_id' => ['required', 'integer'],
            'metadata.*.type' => ['required', 'string', 'in:sgtin,uin,imei,gtin,expiration'],
            'metadata.*.value' => ['required', 'string', 'max:100'],
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
            'metadata.required' => 'Массив метаданных обязателен.',
            'metadata.array' => 'Метаданные должны быть массивом.',
            'metadata.min' => 'Необходим хотя бы один элемент метаданных.',
            'metadata.*.order_id.required' => 'ID заказа обязателен для каждого элемента.',
            'metadata.*.order_id.integer' => 'ID заказа должен быть целым числом.',
            'metadata.*.type.required' => 'Тип метаданных обязателен.',
            'metadata.*.type.in' => 'Допустимые типы: sgtin, uin, imei, gtin, expiration.',
            'metadata.*.value.required' => 'Значение обязательно для каждого элемента.',
            'metadata.*.value.max' => 'Значение не должно превышать 100 символов.',
        ];
    }
}
