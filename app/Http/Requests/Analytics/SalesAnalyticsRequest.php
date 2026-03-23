<?php

declare(strict_types=1);

namespace App\Http\Requests\Analytics;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация параметров фильтрации аналитики продаж.
 * Используется во всех методах SalesAnalyticsController.
 */
final class SalesAnalyticsRequest extends FormRequest
{
    /**
     * Авторизация обрабатывается middleware
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
            'period' => ['nullable', 'string', 'in:7days,30days,90days,365days,all'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Получить период с значением по умолчанию
     */
    public function getPeriod(): string
    {
        return $this->validated('period') ?? '30days';
    }

    /**
     * Получить лимит с значением по умолчанию
     */
    public function getLimit(int $default = 10): int
    {
        return (int) ($this->validated('limit') ?? $default);
    }

    /**
     * Сообщения об ошибках валидации
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'period.in' => 'Допустимые значения периода: 7days, 30days, 90days, 365days, all.',
            'limit.integer' => 'Лимит должен быть целым числом.',
            'limit.min' => 'Лимит должен быть не менее 1.',
            'limit.max' => 'Лимит не может превышать 100.',
        ];
    }
}
