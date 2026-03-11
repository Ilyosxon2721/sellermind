<?php

declare(strict_types=1);

namespace App\Services\Uzum;

use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class UzumAutoReviewService
{
    private string $aiApiKey;

    private string $aiModel;

    public function __construct()
    {
        $this->aiApiKey = config('services.anthropic.key', env('ANTHROPIC_API_KEY', ''));
        $this->aiModel = 'claude-haiku-4-5-20251001';
    }

    /**
     * Обработать все неотвеченные отзывы для аккаунта
     *
     * @return array{processed: int, replied: int, skipped: int, errors: int}
     */
    public function processAccount(MarketplaceAccount $account): array
    {
        $stats = ['processed' => 0, 'replied' => 0, 'skipped' => 0, 'errors' => 0];
        $token = $account->uzum_access_token ?? $account->oauth_token;

        if (! $token) {
            Log::warning("UzumAutoReview: нет токена для аккаунта #{$account->id}");

            return $stats;
        }

        $page = 0;
        do {
            $url = 'https://api-seller.uzum.uz/api/seller/product-reviews?'.http_build_query([
                'page' => $page,
                'size' => 20,
            ]);

            $response = Http::withToken($token)->timeout(30)->post($url, (object) []);

            if (! $response->successful()) {
                break;
            }

            $reviews = $response->json('payload', []);
            $unanswered = array_filter($reviews, fn ($r) => ($r['replyStatus'] ?? null) === null);

            if (empty($unanswered)) {
                break;
            }

            foreach ($unanswered as $review) {
                $stats['processed']++;
                $result = $this->processReview($review, $account, $token);
                $stats[$result]++;
                usleep(500_000); // Пауза 0.5 сек между запросами
            }

            $page++;
            if ($page > 10) {
                break;
            }
        } while (! empty($unanswered));

        return $stats;
    }

    /**
     * Обработать один отзыв: сгенерировать и отправить ответ
     */
    private function processReview(array $review, MarketplaceAccount $account, string $token): string
    {
        $reviewId = $review['reviewId'] ?? null;
        if (! $reviewId) {
            return 'skipped';
        }

        // Проверяем, не отвечали ли уже на этот отзыв
        $exists = DB::table('uzum_review_reply_logs')
            ->where('marketplace_account_id', $account->id)
            ->where('uzum_review_id', $reviewId)
            ->exists();

        if ($exists) {
            return 'skipped';
        }

        $rating = $review['rating'] ?? 0;
        $text = $review['content'] ?? '';
        $pros = $review['pros'] ?? '';
        $cons = $review['cons'] ?? '';
        $productName = $review['product']['productTitle'] ?? '';

        $fullText = collect([
            $text,
            $pros ? "Плюсы: $pros" : '',
            $cons ? "Минусы: $cons" : '',
        ])->filter()->implode(' | ');

        // Для пустых положительных отзывов используем шаблоны
        if (empty(trim($fullText)) && $rating >= 4) {
            $templates = [
                'Спасибо за оценку! Рады, что вам понравилось. Ждём вас снова! 🙏',
                'Благодарим за покупку и высокую оценку! Будем рады видеть вас снова.',
                'Спасибо за отзыв! Приятно знать, что всё понравилось. До новых покупок!',
            ];
            $replyText = $templates[array_rand($templates)];
        } else {
            $replyText = $this->generateAiReply(
                $rating,
                $fullText,
                $productName,
                $account->uzum_review_tone ?? 'friendly'
            );
        }

        if (! $replyText) {
            return 'error';
        }

        // Отправляем ответ на отзыв
        $sendResponse = Http::withToken($token)->timeout(30)->post(
            'https://api-seller.uzum.uz/api/seller/product-reviews/reply/create',
            [['reviewId' => $reviewId, 'content' => $replyText]]
        );

        $status = $sendResponse->successful() ? 'sent' : 'failed';

        DB::table('uzum_review_reply_logs')->insert([
            'marketplace_account_id' => $account->id,
            'uzum_review_id' => $reviewId,
            'rating' => $rating,
            'review_text' => mb_substr($fullText, 0, 1000),
            'reply_text' => $replyText,
            'product_name' => mb_substr($productName, 0, 255),
            'status' => $status,
            'error_message' => $sendResponse->successful() ? null : $sendResponse->body(),
            'replied_at' => $sendResponse->successful() ? now() : null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $sendResponse->successful() ? 'replied' : 'error';
    }

    /**
     * Сгенерировать ответ на отзыв через Claude AI
     */
    private function generateAiReply(int $rating, string $text, string $productName, string $tone): ?string
    {
        $toneInstructions = match ($tone) {
            'professional' => 'Пиши в профессиональном деловом тоне.',
            'casual' => 'Пиши в дружелюбном неформальном тоне, можно эмодзи.',
            default => 'Пиши дружелюбно и тепло, но профессионально.',
        };

        $ratingText = $rating >= 4 ? 'положительный' : ($rating >= 3 ? 'нейтральный' : 'негативный');

        $systemPrompt = "Ты — ассистент по ответам на отзывы на маркетплейсе Uzum Market. {$toneInstructions} Ответ 1-3 предложения, до 500 символов. Не используй 'уважаемый', не давай ложных обещаний.";

        $userPrompt = "Напиши ответ на {$ratingText} отзыв (оценка: {$rating}/5).".
            ($productName ? " Товар: {$productName}." : '').
            ($text ? " Текст: {$text}" : ' Без текста.');

        try {
            $response = Http::withHeaders([
                'x-api-key' => $this->aiApiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model' => $this->aiModel,
                'max_tokens' => 300,
                'system' => $systemPrompt,
                'messages' => [['role' => 'user', 'content' => $userPrompt]],
            ]);

            return $response->successful() ? $response->json('content.0.text') : null;
        } catch (\Throwable $e) {
            Log::error('UzumAutoReview AI error', ['error' => $e->getMessage()]);

            return null;
        }
    }
}
