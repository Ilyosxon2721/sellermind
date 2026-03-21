<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateDebtRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для обновления долга
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:64'],
            'due_date' => ['nullable', 'date'],
            'interest_rate' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'purpose' => ['nullable', 'in:debt,prepayment,advance,loan,other'],
            'counterparty_entity_id' => ['nullable', 'integer', 'exists:counterparties,id'],
            'cash_account_id' => ['nullable', 'integer', 'exists:cash_accounts,id'],
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
            'description.max' => 'Описание не должно превышать 255 символов.',
            'reference.max' => 'Ссылка не должна превышать 64 символа.',
            'due_date.date' => 'Некорректный формат даты погашения.',
            'interest_rate.min' => 'Процентная ставка не может быть отрицательной.',
            'purpose.in' => 'Недопустимое назначение долга.',
            'counterparty_entity_id.exists' => 'Выбранный контрагент не найден.',
            'cash_account_id.exists' => 'Выбранный денежный счёт не найден.',
        ];
    }
}
