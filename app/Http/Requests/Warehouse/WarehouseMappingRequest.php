<?php

declare(strict_types=1);

namespace App\Http\Requests\Warehouse;

use Illuminate\Foundation\Http\FormRequest;

final class WarehouseMappingRequest extends FormRequest
{
    /**
     * Авторизация запроса — доступ к аккаунту маркетплейса проверяется в контроллере
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации маппинга склада.
     * store — создание маппинга, update — частичное обновление.
     *
     * @return array<string, string>
     */
    public function rules(): array
    {
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return [
                'local_warehouse_id' => 'nullable|integer|exists:warehouses,id',
                'is_active' => 'nullable|boolean',
            ];
        }

        return [
            'marketplace_warehouse_id' => 'required|integer',
            'local_warehouse_id' => 'nullable|integer|exists:warehouses,id',
            'name' => 'nullable|string|max:255',
            'type' => 'nullable|string|max:20',
        ];
    }
}
