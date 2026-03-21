<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class CreateFinanceCategoryRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для создания финансовой категории
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:income,expense,both'],
            'parent_id' => ['nullable', 'integer', 'exists:finance_categories,id'],
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
            'name.required' => 'Название категории обязательно для заполнения.',
            'name.max' => 'Название категории не должно превышать 255 символов.',
            'type.required' => 'Тип категории обязателен для заполнения.',
            'type.in' => 'Тип категории должен быть: доход, расход или оба.',
            'parent_id.integer' => 'Идентификатор родительской категории должен быть целым числом.',
            'parent_id.exists' => 'Родительская категория не найдена.',
        ];
    }
}
