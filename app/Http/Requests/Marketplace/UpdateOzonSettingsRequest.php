<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления настроек Ozon
 */
final class UpdateOzonSettingsRequest extends FormRequest
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
            'client_id' => ['nullable', 'string', 'max:255'],
            'api_key' => ['nullable', 'string', 'max:4000'],
            'stock_sync_mode' => ['nullable', 'in:basic,aggregated'],
            'warehouse_id' => ['nullable', 'string', 'max:100'],
            'source_warehouse_ids' => ['nullable', 'array'],
            'source_warehouse_ids.*' => ['integer'],
        ];
    }
}
