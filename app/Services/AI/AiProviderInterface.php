<?php

declare(strict_types=1);

namespace App\Services\AI;

/**
 * Интерфейс для AI провайдеров (OpenAI, Anthropic)
 */
interface AiProviderInterface
{
    /**
     * Получить название провайдера
     */
    public function getName(): string;

    /**
     * Отправить chat completion запрос
     *
     * @param  array  $messages  Массив сообщений [{role, content}, ...]
     * @param  array  $options  Дополнительные параметры (model, temperature, max_tokens)
     * @return array{content: string, usage: array{prompt_tokens: int, completion_tokens: int, total_tokens: int}, model: string}
     *
     * @throws \Exception При ошибке API
     */
    public function chatCompletion(array $messages, array $options = []): array;

    /**
     * Проверить доступность API
     */
    public function isAvailable(): bool;
}
