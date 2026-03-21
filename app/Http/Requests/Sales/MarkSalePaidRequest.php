<?php

declare(strict_types=1);

namespace App\Http\Requests\Sales;

use Illuminate\Foundation\Http\FormRequest;

final class MarkSalePaidRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для отметки оплаты продажи
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'paid_amount' => ['required', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
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
            'paid_amount.required' => 'Сумма оплаты обязательна для заполнения.',
            'paid_amount.numeric' => 'Сумма оплаты должна быть числом.',
            'paid_amount.min' => 'Сумма оплаты не может быть отрицательной.',
            'payment_method.max' => 'Метод оплаты не должен превышать 50 символов.',
        ];
    }
}
