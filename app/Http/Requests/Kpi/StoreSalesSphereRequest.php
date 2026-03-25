<?php

declare(strict_types=1);

namespace App\Http\Requests\Kpi;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Валидация создания сферы продаж
 */
final class StoreSalesSphereRequest extends FormRequest
{
    /**
     * Авторизация через middleware
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Правила валидации
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $companyId = auth()->user()->company_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:500'],
            'color' => ['nullable', 'string', 'max:7'],
            'icon' => ['nullable', 'string', 'max:50'],
            'marketplace_account_id' => [
                'nullable',
                'integer',
                Rule::exists('marketplace_accounts', 'id')->where('company_id', $companyId),
            ],
            'marketplace_account_ids' => ['nullable', 'array'],
            'marketplace_account_ids.*' => [
                'integer',
                Rule::exists('marketplace_accounts', 'id')->where('company_id', $companyId),
            ],
            'offline_sale_types' => ['nullable', 'array'],
            'offline_sale_types.*' => ['string', 'in:retail,wholesale,direct'],
            'store_ids' => ['nullable', 'array'],
            'store_ids.*' => ['integer'],
            'sale_sources' => ['nullable', 'array'],
            'sale_sources.*' => ['string', 'in:manual,pos'],
            'is_active' => ['boolean'],
            'is_manual' => ['boolean'],
            'label_metric1' => ['nullable', 'string', 'max:100'],
            'label_metric2' => ['nullable', 'string', 'max:100'],
            'label_metric3' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['integer', 'min:0'],
        ];
    }

    /**
     * Сообщения об ошибках на русском
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Название сферы обязательно',
            'name.max' => 'Название не может быть длиннее 255 символов',
            'code.max' => 'Код не может быть длиннее 50 символов',
            'description.max' => 'Описание не может быть длиннее 500 символов',
            'color.max' => 'Цвет должен быть в формате HEX (#FFFFFF)',
            'marketplace_account_id.exists' => 'Маркетплейс-аккаунт не найден в вашей компании',
            'marketplace_account_ids.*.exists' => 'Один из маркетплейс-аккаунтов не найден в вашей компании',
            'offline_sale_types.*.in' => 'Допустимые типы офлайн-продаж: retail, wholesale, direct',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
        ];
    }
}
