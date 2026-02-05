<?php

namespace App\Services;

use App\Models\Review;
use App\Models\ReviewTemplate;
use App\Services\AI\AIService;
use Illuminate\Support\Collection;

class ReviewResponseService
{
    public function __construct(
        protected AIService $aiService
    ) {}

    /**
     * Generate AI response for a review.
     */
    public function generateResponse(Review $review, array $options = []): string
    {
        $tone = $options['tone'] ?? 'professional';
        $length = $options['length'] ?? 'medium';
        $language = $options['language'] ?? 'ru';

        $prompt = $this->buildPrompt($review, $tone, $length, $language);

        try {
            $response = $this->aiService->generateText($prompt, [
                'max_tokens' => $this->getMaxTokens($length),
                'temperature' => 0.7,
            ]);

            return trim($response);
        } catch (\Exception $e) {
            \Log::error('Failed to generate review response', [
                'review_id' => $review->id,
                'error' => $e->getMessage(),
            ]);

            // Fallback to template
            return $this->getTemplateResponse($review);
        }
    }

    /**
     * Build AI prompt for review response.
     */
    protected function buildPrompt(Review $review, string $tone, string $length, string $language): string
    {
        $ratingText = $this->getRatingDescription($review->rating);
        $sentiment = $review->sentiment ?? $review->determineSentiment();

        $prompt = "Ты - профессиональный менеджер по работе с клиентами маркетплейса.\n\n";
        $prompt .= "Напиши ответ на отзыв клиента.\n\n";
        $prompt .= "**Отзыв клиента:**\n";
        $prompt .= "Оценка: {$review->rating}/5 ({$ratingText})\n";
        if ($review->customer_name) {
            $prompt .= "Клиент: {$review->customer_name}\n";
        }
        $prompt .= "Текст: {$review->review_text}\n\n";

        $prompt .= "**Требования к ответу:**\n";
        $prompt .= '- Тон: '.$this->getToneDescription($tone)."\n";
        $prompt .= '- Длина: '.$this->getLengthDescription($length)."\n";
        $prompt .= '- Язык: '.($language === 'ru' ? 'русский' : 'английский')."\n";

        if ($sentiment === 'negative') {
            $prompt .= "- Признай проблему и извинись\n";
            $prompt .= "- Предложи решение или компенсацию\n";
            $prompt .= "- Покажи заботу о клиенте\n";
        } elseif ($sentiment === 'positive') {
            $prompt .= "- Поблагодари за отзыв\n";
            $prompt .= "- Вырази радость от довольного клиента\n";
            $prompt .= "- Пригласи вернуться снова\n";
        } else {
            $prompt .= "- Поблагодари за обратную связь\n";
            $prompt .= "- Предложи помощь если нужно\n";
        }

        $prompt .= "\n**Важно:**\n";
        $prompt .= "- НЕ используй формулировки типа 'уважаемый клиент'\n";
        $prompt .= "- Будь искренним и человечным\n";
        $prompt .= "- НЕ повторяй текст отзыва\n";
        $prompt .= "- Обращайся по имени если оно указано\n";
        $prompt .= "- Пиши от первого лица (мы, наш магазин)\n\n";

        $prompt .= 'Напиши только текст ответа, без дополнительных комментариев.';

        return $prompt;
    }

    /**
     * Get template-based response as fallback.
     */
    protected function getTemplateResponse(Review $review): string
    {
        $category = $this->determineCategory($review);

        $template = ReviewTemplate::active()
            ->where('category', $category)
            ->where(function ($query) use ($review) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $review->company_id);
            })
            ->first();

        if (! $template) {
            return $this->getDefaultResponse($review);
        }

        $variables = [
            'customer_name' => $review->customer_name ?? 'покупатель',
            'product_name' => $review->product?->name ?? 'товар',
            'rating' => $review->rating,
        ];

        return $template->apply($variables);
    }

    /**
     * Determine template category from review.
     */
    protected function determineCategory(Review $review): string
    {
        if ($review->rating >= 4) {
            return 'positive';
        }

        if ($review->rating <= 2) {
            $text = strtolower($review->review_text);

            if (str_contains($text, 'качество') || str_contains($text, 'брак')) {
                return 'negative_quality';
            }

            if (str_contains($text, 'доставка') || str_contains($text, 'пришло')) {
                return 'negative_delivery';
            }

            if (str_contains($text, 'размер') || str_contains($text, 'маленький') || str_contains($text, 'большой')) {
                return 'negative_size';
            }

            return 'complaint';
        }

        return 'neutral';
    }

    /**
     * Get default response.
     */
    protected function getDefaultResponse(Review $review): string
    {
        $name = $review->customer_name ?? 'покупатель';

        if ($review->rating >= 4) {
            return 'Спасибо за ваш отзыв! Мы очень рады, что вам понравился наш товар. Будем рады видеть вас снова!';
        }

        if ($review->rating <= 2) {
            return 'Здравствуйте! Благодарим за обратную связь. Нам очень жаль, что возникла такая ситуация. Мы обязательно разберемся и примем меры. Пожалуйста, свяжитесь с нами для решения вопроса.';
        }

        return 'Спасибо за ваш отзыв! Мы ценим любую обратную связь и постоянно работаем над улучшением качества.';
    }

    /**
     * Suggest templates for review.
     */
    public function suggestTemplates(Review $review, int $limit = 3): Collection
    {
        $category = $this->determineCategory($review);

        return ReviewTemplate::active()
            ->where('category', $category)
            ->where(function ($query) use ($review) {
                $query->whereNull('company_id')
                    ->orWhere('company_id', $review->company_id);
            })
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Bulk generate responses for multiple reviews.
     */
    public function bulkGenerate(array $reviewIds, array $options = []): array
    {
        $results = [];

        foreach ($reviewIds as $reviewId) {
            $review = Review::find($reviewId);
            if (! $review) {
                continue;
            }

            try {
                $response = $this->generateResponse($review, $options);
                $results[$reviewId] = [
                    'success' => true,
                    'response' => $response,
                ];
            } catch (\Exception $e) {
                $results[$reviewId] = [
                    'success' => false,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    /**
     * Get rating description.
     */
    protected function getRatingDescription(int $rating): string
    {
        return match ($rating) {
            5 => 'отлично',
            4 => 'хорошо',
            3 => 'нейтрально',
            2 => 'плохо',
            1 => 'очень плохо',
            default => 'неизвестно',
        };
    }

    /**
     * Get tone description.
     */
    protected function getToneDescription(string $tone): string
    {
        return match ($tone) {
            'professional' => 'профессиональный, вежливый',
            'friendly' => 'дружелюбный, неформальный',
            'formal' => 'официальный, сдержанный',
            default => 'профессиональный',
        };
    }

    /**
     * Get length description.
     */
    protected function getLengthDescription(string $length): string
    {
        return match ($length) {
            'short' => '1-2 предложения',
            'medium' => '3-4 предложения',
            'long' => '5-6 предложений',
            default => '3-4 предложения',
        };
    }

    /**
     * Get max tokens for length.
     */
    protected function getMaxTokens(string $length): int
    {
        return match ($length) {
            'short' => 100,
            'medium' => 200,
            'long' => 300,
            default => 200,
        };
    }

    /**
     * Analyze review sentiment using AI.
     */
    public function analyzeSentiment(Review $review): string
    {
        $prompt = "Определи эмоциональную окраску отзыва (positive/neutral/negative):\n\n";
        $prompt .= "Оценка: {$review->rating}/5\n";
        $prompt .= "Текст: {$review->review_text}\n\n";
        $prompt .= 'Ответь одним словом: positive, neutral или negative';

        try {
            $sentiment = $this->aiService->generateText($prompt, ['max_tokens' => 10]);
            $sentiment = strtolower(trim($sentiment));

            if (in_array($sentiment, ['positive', 'neutral', 'negative'])) {
                return $sentiment;
            }
        } catch (\Exception $e) {
            \Log::error('Failed to analyze sentiment', ['review_id' => $review->id]);
        }

        // Fallback to rating-based sentiment
        return $review->determineSentiment();
    }

    /**
     * Extract keywords from review.
     */
    public function extractKeywords(Review $review): array
    {
        $prompt = "Извлеки ключевые слова из отзыва (максимум 5 слов через запятую):\n\n";
        $prompt .= $review->review_text."\n\n";
        $prompt .= 'Ответь только ключевыми словами через запятую, без дополнительного текста.';

        try {
            $keywords = $this->aiService->generateText($prompt, ['max_tokens' => 50]);

            return array_map('trim', explode(',', $keywords));
        } catch (\Exception $e) {
            return [];
        }
    }
}
