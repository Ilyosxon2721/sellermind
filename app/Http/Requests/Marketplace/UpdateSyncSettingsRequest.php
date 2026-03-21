<?php

declare(strict_types=1);

namespace App\Http\Requests\Marketplace;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация обновления настроек синхронизации маркетплейса
 */
final class UpdateSyncSettingsRequest extends FormRequest
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
            'sync_settings' => ['required', 'array'],
            'sync_settings.stock_sync_enabled' => ['nullable', 'boolean'],
            'sync_settings.auto_sync_stock_on_link' => ['nullable', 'boolean'],
            'sync_settings.auto_sync_stock_on_change' => ['nullable', 'boolean'],
        ];
    }
}
