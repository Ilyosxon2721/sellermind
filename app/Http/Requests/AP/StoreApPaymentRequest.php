<?php

declare(strict_types=1);

namespace App\Http\Requests\AP;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApPaymentRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для создания платежа поставщику
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer'],
            'payment_no' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'paid_at' => ['required', 'date'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'amount_total' => ['required', 'numeric'],
            'method' => ['nullable', 'string', 'max:50'],
            'reference' => ['nullable', 'string', 'max:255'],
            'comment' => ['nullable', 'string', 'max:1000'],
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
            'supplier_id.required' => 'Поставщик обязателен для заполнения.',
            'supplier_id.integer' => 'Идентификатор поставщика должен быть целым числом.',
            'payment_no.required' => 'Номер платежа обязателен для заполнения.',
            'payment_no.max' => 'Номер платежа не должен превышать 100 символов.',
            'paid_at.required' => 'Дата оплаты обязательна для заполнения.',
            'paid_at.date' => 'Некорректный формат даты оплаты.',
            'currency_code.max' => 'Код валюты не должен превышать 8 символов.',
            'exchange_rate.min' => 'Курс обмена не может быть отрицательным.',
            'amount_total.required' => 'Сумма платежа обязательна для заполнения.',
            'amount_total.numeric' => 'Сумма платежа должна быть числом.',
            'method.max' => 'Метод оплаты не должен превышать 50 символов.',
            'reference.max' => 'Ссылка не должна превышать 255 символов.',
            'comment.max' => 'Комментарий не должен превышать 1000 символов.',
        ];
    }
}
