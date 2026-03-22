<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления товара маркетплейса.
 * Нормализует пустые строки в null и числовые ID в строки.
 */
final class UpdateMarketplaceProductRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Подготовка данных перед валидацией.
     * Нормализуем пустые строки в null и числовые значения в строки.
     */
    protected function prepareForValidation(): void
    {
        // Нормализуем пустые значения в null
        foreach (['product_id', 'external_product_id', 'external_offer_id', 'external_sku'] as $field) {
            if ($this->has($field) && $this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }

        // Приводим числовые внешние идентификаторы к строке
        foreach (['external_product_id', 'external_offer_id', 'external_sku'] as $field) {
            if ($this->has($field) && is_numeric($this->input($field))) {
                $this->merge([$field => (string) $this->input($field)]);
            }
        }
    }

    /**
     * Правила валидации
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'external_sku' => ['nullable', 'string', 'max:255'],
            'external_offer_id' => ['nullable', 'string', 'max:255'],
            'external_product_id' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,active,paused,failed'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
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
            'external_sku.max' => 'Внешний SKU не должен превышать 255 символов.',
            'external_offer_id.max' => 'Внешний offer ID не должен превышать 255 символов.',
            'external_product_id.max' => 'Внешний product ID не должен превышать 255 символов.',
            'status.in' => 'Допустимые статусы: pending, active, paused, failed.',
            'product_id.integer' => 'ID товара должен быть целым числом.',
            'product_id.exists' => 'Товар не найден.',
        ];
    }
}
