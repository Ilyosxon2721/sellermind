<?php

namespace App\Services;

use App\Models\UzumShop;
use App\Models\ReviewReplyLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReviewAutoResponder
{
    protected UzumSellerApi $api;
    protected string $aiProvider;
    protected string $aiApiKey;
    protected string $aiModel;

    public function __construct(UzumSellerApi $api)
    {
        $this->api = $api;
        $this->aiProvider = config('uzum.review_ai_provider', 'anthropic'); // 'anthropic' или 'openai'
        $this->aiApiKey = config('uzum.review_ai_api_key', '');
        $this->aiModel = config('uzum.review_ai_model', 'claude-sonnet-4-20250514');
    }

    /**
     * Обработать неотвеченные отзывы магазина
     */
    public function processShop(UzumShop $shop): array
    {
        $stats = ['processed' => 0, 'replied' => 0, 'skipped' => 0, 'errors' => 0];

        $page = 0;

        do {
            $result = $this->api->getReviews(
                page: $page,
                size: 20
            );

            if (!$result['success']) {
                Log::error('ReviewAutoResponder: ошибка получения отзывов', [
                    'shop_id' => $shop->uzum_shop_id,
                    'error'   => $result['error'] ?? 'unknown',
                ]);
                break;
            }

            // Реальная структура: { "payload": [ { reviewId, rating, content, ... } ] }
            $reviews = $result['data']['payload'] ?? [];

            // Фильтруем: берём только неотвеченные (replyStatus === null)
            $reviews = array_filter($reviews, function ($review) {
                return ($review['replyStatus'] ?? null) === null;
            });

            if (empty($reviews)) break;

            foreach ($reviews as $review) {
                $stats['processed']++;

                $replyResult = $this->processReview($review, $shop);

                match ($replyResult) {
                    'replied' => $stats['replied']++,
                    'skipped' => $stats['skipped']++,
                    'error'   => $stats['errors']++,
                };

                // Пауза между ответами
                usleep(500_000); // 500ms
            }

            $page++;

            if ($page > 10) break;

        } while (!empty($reviews));

        return $stats;
    }

    /**
     * Обработать один отзыв
     */
    protected function processReview(array $review, UzumShop $shop): string
    {
        // Реальная структура полей из Uzum Seller API
        $reviewId    = $review['reviewId'] ?? null;
        $rating      = $review['rating'] ?? 0;
        $text        = $review['content'] ?? '';
        $pros        = $review['pros'] ?? '';
        $cons        = $review['cons'] ?? '';
        $authorName  = $review['customerName'] ?? 'Покупатель';
        $productName = $review['product']['productTitle'] ?? '';
        $skuTitle    = $review['product']['skuTitle'] ?? '';
        $anonymous   = $review['anonymous'] ?? false;

        // Объединяем текст отзыва из всех полей
        $fullReviewText = collect([$text, $pros ? "Плюсы: {$pros}" : '', $cons ? "Минусы: {$cons}" : ''])
            ->filter()
            ->implode(' | ');

        if (!$reviewId) {
            Log::warning('ReviewAutoResponder: нет ID отзыва', ['review' => $review]);
            return 'skipped';
        }

        // Проверяем, не отвечали ли уже
        $alreadyReplied = ReviewReplyLog::where('uzum_review_id', $reviewId)->exists();
        if ($alreadyReplied) {
            return 'skipped';
        }

        // Пустой отзыв без текста — используем краткий шаблон
        if (empty(trim($fullReviewText)) && $rating >= 4) {
            $replyText = $this->getQuickThankYou($rating, $authorName, $shop);
        } else {
            // Генерируем ответ через ИИ
            $replyText = $this->generateAiReply($review, $shop);
        }

        if (!$replyText) {
            return 'error';
        }

        // Отправляем ответ
        $sendResult = $this->api->replyToReview($reviewId, $replyText);

        // Логируем
        ReviewReplyLog::create([
            'uzum_shop_id'   => $shop->uzum_shop_id,
            'uzum_review_id' => $reviewId,
            'rating'         => $rating,
            'review_text'    => Str::limit($fullReviewText, 1000),
            'reply_text'     => $replyText,
            'product_name'   => Str::limit($productName ?: $skuTitle, 255),
            'status'         => $sendResult['success'] ? 'sent' : 'failed',
            'error_message'  => $sendResult['success'] ? null : ($sendResult['error'] ?? null),
            'replied_at'     => $sendResult['success'] ? now() : null,
        ]);

        return $sendResult['success'] ? 'replied' : 'error';
    }

    /**
     * Генерировать ответ через ИИ
     */
    protected function generateAiReply(array $review, UzumShop $shop): ?string
    {
        $rating      = $review['rating'] ?? 0;
        $text        = $review['content'] ?? '';
        $pros        = $review['pros'] ?? '';
        $cons        = $review['cons'] ?? '';
        $authorName  = $review['customerName'] ?? 'Покупатель';
        $productName = $review['product']['productTitle'] ?? '';
        $skuTitle    = $review['product']['skuTitle'] ?? '';
        $characteristics = $review['characteristics'] ?? [];

        $systemPrompt = $this->buildSystemPrompt($shop);
        $userPrompt = $this->buildUserPrompt($rating, $text, $pros, $cons, $authorName, $productName, $skuTitle, $characteristics);

        try {
            return match ($this->aiProvider) {
                'anthropic' => $this->callAnthropic($systemPrompt, $userPrompt),
                'openai'    => $this->callOpenAi($systemPrompt, $userPrompt),
                default     => $this->callAnthropic($systemPrompt, $userPrompt),
            };
        } catch (\Throwable $e) {
            Log::error('ReviewAutoResponder: ошибка ИИ', [
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function buildSystemPrompt(UzumShop $shop): string
    {
        $shopName = $shop->name ?? 'магазин';
        $tone = $shop->review_tone ?? 'friendly'; // friendly, professional, casual

        $toneInstructions = match ($tone) {
            'professional' => 'Пиши в профессиональном деловом тоне. Вежливо, но сдержанно.',
            'casual' => 'Пиши в дружелюбном неформальном тоне, как общение с другом. Можно эмодзи.',
            default => 'Пиши дружелюбно и тепло, но профессионально. Без чрезмерных эмодзи.',
        };

        return <<<PROMPT
Ты — ассистент по ответам на отзывы для магазина "{$shopName}" на маркетплейсе Uzum Market (Узбекистан).

ПРАВИЛА:
1. Отвечай на русском языке (или на языке отзыва, если он на узбекском)
2. {$toneInstructions}
3. Ответ должен быть 1-3 предложения, МАКСИМУМ 500 символов
4. Для положительных отзывов (4-5 звёзд): поблагодари за покупку и отзыв, вырази радость
5. Для нейтральных отзывов (3 звезды): поблагодари, предложи помощь если есть замечания
6. Для негативных отзывов (1-2 звезды): извинись, прояви эмпатию, предложи решение через поддержку
7. Упоминай товар если он указан в отзыве
8. НЕ используй шаблонные фразы типа "Ваше мнение очень важно для нас"
9. Каждый ответ должен быть уникальным и релевантным
10. НЕ обещай конкретные скидки или компенсации
11. Если в отзыве упомянута конкретная проблема — покажи что ты её понял

ЗАПРЕЩЕНО:
- Использовать слово "уважаемый"
- Давать ложные обещания
- Спорить с клиентом
- Использовать канцеляризмы
PROMPT;
    }

    protected function buildUserPrompt(
        int $rating,
        string $text,
        string $pros,
        string $cons,
        string $authorName,
        string $productName,
        string $skuTitle,
        array $characteristics = []
    ): string {
        $ratingText = match (true) {
            $rating >= 5 => '⭐⭐⭐⭐⭐ (отлично)',
            $rating >= 4 => '⭐⭐⭐⭐ (хорошо)',
            $rating >= 3 => '⭐⭐⭐ (нормально)',
            $rating >= 2 => '⭐⭐ (плохо)',
            default      => '⭐ (очень плохо)',
        };

        $prompt = "Напиши ответ на отзыв:\n";
        $prompt .= "Оценка: {$ratingText}\n";

        if ($productName) {
            $prompt .= "Товар: {$productName}";
            if ($skuTitle) $prompt .= " ({$skuTitle})";
            $prompt .= "\n";
        }

        if (!empty($characteristics)) {
            $chars = collect($characteristics)
                ->map(fn ($c) => ($c['characteristic'] ?? '') . ': ' . ($c['characteristicValue'] ?? ''))
                ->implode(', ');
            $prompt .= "Характеристики: {$chars}\n";
        }

        if (!empty(trim($text))) {
            $prompt .= "Текст отзыва: {$text}\n";
        }

        if (!empty(trim($pros))) {
            $prompt .= "Плюсы (указал покупатель): {$pros}\n";
        }

        if (!empty(trim($cons))) {
            $prompt .= "Минусы (указал покупатель): {$cons}\n";
        }

        if (empty(trim($text)) && empty(trim($pros)) && empty(trim($cons))) {
            $prompt .= "Текст отзыва: (без текста, только оценка)\n";
        }

        $prompt .= "\nОтветь кратко и по существу.";

        return $prompt;
    }

    /**
     * Вызов Anthropic Claude API
     */
    protected function callAnthropic(string $system, string $user): ?string
    {
        $response = Http::withHeaders([
            'x-api-key'         => $this->aiApiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type'      => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model'      => $this->aiModel,
            'max_tokens' => 300,
            'system'     => $system,
            'messages'   => [
                ['role' => 'user', 'content' => $user],
            ],
        ]);

        if ($response->successful()) {
            return $response->json('content.0.text');
        }

        Log::error('Anthropic API error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return null;
    }

    /**
     * Вызов OpenAI API
     */
    protected function callOpenAi(string $system, string $user): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->aiApiKey}",
            'Content-Type'  => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model'      => $this->aiModel,
            'max_tokens' => 300,
            'messages'   => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        Log::error('OpenAI API error', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return null;
    }

    /**
     * Быстрый ответ без ИИ для пустых положительных отзывов
     */
    protected function getQuickThankYou(int $rating, string $authorName, UzumShop $shop): string
    {
        $templates = [
            "Спасибо за оценку! Рады, что вам понравилось. Ждём вас снова! 🙏",
            "Благодарим за покупку и высокую оценку! Будем рады видеть вас снова.",
            "Спасибо за отзыв! Приятно знать, что всё понравилось. До новых покупок!",
            "Большое спасибо за оценку! Ваша поддержка очень мотивирует нас 💪",
            "Благодарим за отзыв! Надеемся, товар приносит радость. Ждём вас снова!",
        ];

        return $templates[array_rand($templates)];
    }
}
