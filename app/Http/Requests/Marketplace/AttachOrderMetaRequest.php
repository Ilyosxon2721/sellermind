<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация прикрепления метаданных к заказу Wildberries.
 * Поддерживает типы: sgtin, uin, imei, gtin, expiration_date.
 */
final class AttachOrderMetaRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации определяются динамически в зависимости от маршрута
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        $routeName = $this->route()->getName() ?? '';

        // Определяем правила на основе имени маршрута
        if (str_contains($routeName, 'expiration')) {
            return [
                'expiration_date' => ['required', 'date_format:Y-m-d'],
            ];
        }

        // Определяем поле по имени маршрута (sgtin, uin, imei, gtin)
        $field = $this->resolveMetaField($routeName);

        return [
            $field => ['required', 'string', 'max:100'],
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
            'sgtin.required' => 'SGTIN код обязателен.',
            'sgtin.max' => 'SGTIN код не должен превышать 100 символов.',
            'uin.required' => 'UIN код обязателен.',
            'uin.max' => 'UIN код не должен превышать 100 символов.',
            'imei.required' => 'IMEI код обязателен.',
            'imei.max' => 'IMEI код не должен превышать 100 символов.',
            'gtin.required' => 'GTIN код обязателен.',
            'gtin.max' => 'GTIN код не должен превышать 100 символов.',
            'expiration_date.required' => 'Срок годности обязателен.',
            'expiration_date.date_format' => 'Срок годности должен быть в формате YYYY-MM-DD.',
        ];
    }

    /**
     * Определить поле метаданных по имени маршрута
     */
    private function resolveMetaField(string $routeName): string
    {
        $fields = ['sgtin', 'uin', 'imei', 'gtin'];

        foreach ($fields as $field) {
            if (str_contains(strtolower($routeName), $field)) {
                return $field;
            }
        }

        // Запасной вариант — попытка определить по наличию данных в запросе
        foreach ($fields as $field) {
            if ($this->has($field)) {
                return $field;
            }
        }

        return 'value';
    }
}
