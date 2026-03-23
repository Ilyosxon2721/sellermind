<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация добавления строк в складской документ
 */
final class AddDocumentLinesRequest extends FormRequest
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
            'lines.*.sku_id' => ['required', 'integer', 'exists:skus,id'],
            'lines.*.qty' => ['required', 'numeric', 'min:0.001'],
            'lines.*.unit_id' => ['required', 'integer'],
            'lines.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'lines.*.currency_code' => ['nullable', 'string', 'max:3'],
            'lines.*.exchange_rate' => ['nullable', 'numeric', 'min:0.0001'],
            'lines.*.counted_qty' => ['nullable', 'numeric'],
            'lines.*.location_id' => ['nullable', 'integer'],
            'lines.*.location_to_id' => ['nullable', 'integer'],
        ];
    }
}
