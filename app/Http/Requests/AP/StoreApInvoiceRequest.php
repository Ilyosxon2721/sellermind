<?php

declare(strict_types=1);

namespace App\Http\Requests\AP;

use Illuminate\Foundation\Http\FormRequest;

final class StoreApInvoiceRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для создания накладной поставщика
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer'],
            'invoice_no' => ['required', 'string', 'max:100'],
            'status' => ['nullable', 'string', 'max:50'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'currency_code' => ['nullable', 'string', 'max:8'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0'],
            'amount_tax' => ['nullable', 'numeric', 'min:0'],
            'amount_total' => ['nullable', 'numeric'],
            'related_type' => ['nullable', 'string', 'max:50'],
            'related_id' => ['nullable', 'integer'],
            'notes' => ['nullable', 'string', 'max:1000'],
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
            'invoice_no.required' => 'Номер накладной обязателен для заполнения.',
            'invoice_no.max' => 'Номер накладной не должен превышать 100 символов.',
            'issue_date.date' => 'Некорректный формат даты выставления.',
            'due_date.date' => 'Некорректный формат даты оплаты.',
            'currency_code.max' => 'Код валюты не должен превышать 8 символов.',
            'exchange_rate.min' => 'Курс обмена не может быть отрицательным.',
            'amount_tax.min' => 'Сумма налога не может быть отрицательной.',
            'related_type.max' => 'Тип связанного документа не должен превышать 50 символов.',
            'notes.max' => 'Заметки не должны превышать 1000 символов.',
        ];
    }
}
