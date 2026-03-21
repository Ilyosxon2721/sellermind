<?php

declare(strict_types=1);

namespace App\Http\Requests\AP;

use Illuminate\Foundation\Http\FormRequest;

final class AllocatePaymentRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для распределения платежа по накладным
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'allocations' => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id' => ['required', 'integer'],
            'allocations.*.amount' => ['required', 'numeric', 'min:0.01'],
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
            'allocations.required' => 'Список распределений обязателен для заполнения.',
            'allocations.array' => 'Распределения должны быть массивом.',
            'allocations.min' => 'Необходимо указать хотя бы одно распределение.',
            'allocations.*.invoice_id.required' => 'Идентификатор накладной обязателен для заполнения.',
            'allocations.*.invoice_id.integer' => 'Идентификатор накладной должен быть целым числом.',
            'allocations.*.amount.required' => 'Сумма распределения обязательна для заполнения.',
            'allocations.*.amount.min' => 'Сумма распределения должна быть не менее 0.01.',
        ];
    }
}
