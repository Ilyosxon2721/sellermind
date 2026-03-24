<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация создания складского документа
 */
final class CreateDocumentRequest extends FormRequest
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
            'doc_no' => ['nullable', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:IN,OUT,MOVE,WRITE_OFF,INVENTORY,REVERSAL'],
            'warehouse_id' => ['required', 'integer'],
            'warehouse_to_id' => ['nullable', 'integer'],
            'comment' => ['nullable', 'string', 'max:1000'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'source_doc_no' => ['nullable', 'string', 'max:100'],
        ];
    }
}
