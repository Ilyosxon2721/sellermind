<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления себестоимости строк складского документа
 */
final class UpdateDocumentCostsRequest extends FormRequest
{
    /**
     * Определяет авторизацию запроса
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
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer'],
            'lines.*.unit_cost' => ['required', 'numeric', 'min:0'],
            'lines.*.currency_code' => ['nullable', 'string', 'max:3'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
        ];
    }
}
