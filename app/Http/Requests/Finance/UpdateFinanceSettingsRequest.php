<?php

declare(strict_types=1);

namespace App\Http\Requests\Finance;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateFinanceSettingsRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации для обновления финансовых настроек
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'base_currency_code' => ['nullable', 'string', 'max:8'],
            'usd_rate' => ['nullable', 'numeric', 'min:0'],
            'rub_rate' => ['nullable', 'numeric', 'min:0'],
            'eur_rate' => ['nullable', 'numeric', 'min:0'],
            'tax_system' => ['nullable', 'in:simplified,general,both'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'income_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'social_tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'auto_import_marketplace_fees' => ['nullable', 'boolean'],
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
            'base_currency_code.max' => 'Код базовой валюты не должен превышать 8 символов.',
            'usd_rate.min' => 'Курс доллара не может быть отрицательным.',
            'rub_rate.min' => 'Курс рубля не может быть отрицательным.',
            'eur_rate.min' => 'Курс евро не может быть отрицательным.',
            'tax_system.in' => 'Система налогообложения должна быть: упрощённая, общая или обе.',
            'vat_rate.min' => 'Ставка НДС не может быть отрицательной.',
            'vat_rate.max' => 'Ставка НДС не может превышать 100%.',
            'income_tax_rate.min' => 'Ставка налога на прибыль не может быть отрицательной.',
            'income_tax_rate.max' => 'Ставка налога на прибыль не может превышать 100%.',
            'social_tax_rate.min' => 'Ставка социального налога не может быть отрицательной.',
            'social_tax_rate.max' => 'Ставка социального налога не может превышать 100%.',
        ];
    }
}
