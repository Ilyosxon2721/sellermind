<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Валидация запроса на подписку push уведомлений
 */
final class PushSubscribeRequest extends FormRequest
{
    /**
     * Определить, авторизован ли пользователь для этого запроса
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
        return [
            'endpoint' => ['required', 'string', 'url', 'max:2048'],
            'keys' => ['required', 'array'],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'max:50'],
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
            'endpoint.required' => 'Endpoint подписки обязателен',
            'endpoint.url' => 'Endpoint должен быть валидным URL',
            'keys.required' => 'Ключи подписки обязательны',
            'keys.p256dh.required' => 'Публичный ключ p256dh обязателен',
            'keys.auth.required' => 'Auth токен обязателен',
        ];
    }
}
