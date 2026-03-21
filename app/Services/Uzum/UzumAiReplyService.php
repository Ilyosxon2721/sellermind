<?php

declare(strict_types=1);

namespace App\Services\Uzum;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Сервис генерации ответов на отзывы через Claude AI
 */
final class UzumAiReplyService
{
    private string $apiKey;

    private string $model;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.key', env('ANTHROPIC_API_KEY', ''));
        $this->model = 'claude-haiku-4-5-20251001';
    }

    /**
     * Сгенерировать ответ на отзыв
     *
     * @param  string  $tone  friendly|professional|casual
     */
    public function generate(int $rating, string $reviewText, string $productName = '', string $tone = 'friendly'): ?string
    {
        // Для пустых положительных отзывов — шаблон
        if (empty(trim($reviewText)) && $rating >= 4) {
            return $this->randomTemplate();
        }

        if (! $this->apiKey) {
            Log::warning('UzumAiReply: ANTHROPIC_API_KEY не настроен');

            return null;
        }

        $toneInstructions = match ($tone) {
            'professional' => 'Пиши в профессиональном деловом тоне.',
            'casual' => 'Пиши в дружелюбном неформальном тоне, можно эмодзи.',
            default => 'Пиши дружелюбно и тепло, но профессионально.',
        };

        $ratingText = $rating >= 4 ? 'положительный' : ($rating >= 3 ? 'нейтральный' : 'негативный');

        $systemPrompt = "Ты — ассистент по ответам на отзывы на маркетплейсе Uzum Market. {$toneInstructions} Ответ 1-3 предложения, до 500 символов. Не используй 'уважаемый', не давай ложных обещаний.";

        $userPrompt = "Напиши ответ на {$ratingText} отзыв (оценка: {$rating}/5)."
            .($productName ? " Товар: {$productName}." : '')
            .($reviewText ? " Текст: {$reviewText}" : ' Без текста.');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->model,
                'max_tokens' => 300,
                'system' => $systemPrompt,
                'messages' => [['role' => 'user', 'content' => $userPrompt]],
            ]);

            return $response->successful() ? $response->json('content.0.text') : null;
        } catch (\Throwable $e) {
            Log::error('UzumAiReply error', ['error' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Случайный шаблон для пустых положительных отзывов
     */
    private function randomTemplate(): string
    {
        $templates = [
            'Спасибо за оценку! Рады, что вам понравилось. Ждём вас снова! 🙏',
            'Благодарим за покупку и высокую оценку! Будем рады видеть вас снова.',
            'Спасибо за отзыв! Приятно знать, что всё понравилось. До новых покупок!',
        ];

        return $templates[array_rand($templates)];
    }

    /**
     * Проверить доступность AI
     */
    public function isConfigured(): bool
    {
        return ! empty($this->apiKey);
    }
}
