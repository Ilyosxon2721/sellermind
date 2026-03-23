<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления настроек Wildberries
 */
final class UpdateWildberriesSettingsRequest extends FormRequest
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
            'api_key' => ['nullable', 'string', 'max:4000'],
            'wb_content_token' => ['nullable', 'string', 'max:4000'],
            'wb_marketplace_token' => ['nullable', 'string', 'max:4000'],
            'wb_prices_token' => ['nullable', 'string', 'max:4000'],
            'wb_statistics_token' => ['nullable', 'string', 'max:4000'],
            'warehouse_id' => ['nullable', 'integer'],
            'sync_mode' => ['nullable', 'in:basic,aggregated'],
            'source_warehouse_ids' => ['nullable', 'array'],
            'source_warehouse_ids.*' => ['integer'],
        ];
    }
}
