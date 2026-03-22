<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

/**
 * Оркестратор для работы с AI провайдерами
 * Автоматически переключается на fallback при ошибках
 */
final class AiProviderService
{
    private AiProviderInterface $primaryProvider;

    private ?AiProviderInterface $fallbackProvider;

    private int $maxRetries;

    public function __construct()
    {
        $this->primaryProvider = $this->createProvider(config('ai.provider', 'openai'));
        $fallbackName = config('ai.fallback_provider');
        $this->fallbackProvider = $fallbackName ? $this->createProvider($fallbackName) : null;
        $this->maxRetries = config('ai.retry.max_attempts', 2);
    }

    /**
     * Отправить chat completion с автоматическим fallback
     *
     * @return array{content: string, usage: array, model: string, provider: string}
     *
     * @throws \Exception Если все провайдеры недоступны
     */
    public function chatCompletion(array $messages, array $options = []): array
    {
        // Попытка с основным провайдером
        try {
            if ($this->primaryProvider->isAvailable()) {
                $result = $this->primaryProvider->chatCompletion($messages, $options);
                $result['provider'] = $this->primaryProvider->getName();

                Log::info('AI Request Success', [
                    'provider' => $result['provider'],
                    'model' => $result['model'],
                    'tokens' => $result['usage']['total_tokens'] ?? 0,
                ]);

                return $result;
            }

            Log::warning('Primary AI provider not available', [
                'provider' => $this->primaryProvider->getName(),
            ]);
        } catch (\Exception $e) {
            Log::warning('Primary AI provider failed', [
                'provider' => $this->primaryProvider->getName(),
                'error' => $e->getMessage(),
            ]);
        }

        // Fallback на резервный провайдер
        if ($this->fallbackProvider && $this->fallbackProvider->isAvailable()) {
            try {
                Log::info('Switching to fallback AI provider', [
                    'from' => $this->primaryProvider->getName(),
                    'to' => $this->fallbackProvider->getName(),
                ]);

                $result = $this->fallbackProvider->chatCompletion($messages, $options);
                $result['provider'] = $this->fallbackProvider->getName();

                Log::info('AI Fallback Success', [
                    'provider' => $result['provider'],
                    'model' => $result['model'],
                    'tokens' => $result['usage']['total_tokens'] ?? 0,
                ]);

                return $result;
            } catch (\Exception $e) {
                Log::error('Fallback AI provider also failed', [
                    'provider' => $this->fallbackProvider->getName(),
                    'error' => $e->getMessage(),
                ]);

                throw new \Exception('Все AI провайдеры недоступны: '.$e->getMessage());
            }
        }

        throw new \Exception('Нет доступных AI провайдеров. Проверьте API ключи в .env');
    }

    /**
     * Получить основной провайдер
     */
    public function getPrimaryProvider(): AiProviderInterface
    {
        return $this->primaryProvider;
    }

    /**
     * Получить резервный провайдер
     */
    public function getFallbackProvider(): ?AiProviderInterface
    {
        return $this->fallbackProvider;
    }

    /**
     * Создать экземпляр провайдера по имени
     */
    private function createProvider(string $name): AiProviderInterface
    {
        return match ($name) {
            'openai' => new OpenAiProvider,
            'anthropic' => new AnthropicProvider,
            default => throw new \InvalidArgumentException("Unknown AI provider: {$name}"),
        };
    }
}
