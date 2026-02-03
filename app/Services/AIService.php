<?php

namespace App\Services;

use App\Models\AIUsageLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIService
{
    private string $apiKey;

    private array $models;

    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('openai.api_key');
        $this->models = config('openai.models');
        $this->baseUrl = rtrim(config('openai.api_url', 'https://api.openai.com/v1'), '/');
    }

    private function getModelByKey(string $key): string
    {
        return match ($key) {
            'fast' => 'gpt-4o-mini',       // GPT-4o mini - быстрый и экономичный
            'smart' => 'gpt-4o',           // GPT-4o - оптимальный баланс
            'premium' => 'o1-mini',        // o1-mini - максимальное качество (reasoning)
            default => 'gpt-4o-mini',
        };
    }

    public function generateChatResponse(array $context, string $prompt, array $meta = []): string
    {
        $messages = $this->buildMessages($context, $prompt, $meta);

        // Select model based on user preference
        $modelKey = $meta['model'] ?? 'fast';
        $model = $this->getModelByKey($modelKey);

        // Build request parameters based on model type
        $requestParams = [
            'model' => $model,
            'messages' => $messages,
        ];

        $this->applyTokenLimit($requestParams, config('openai.defaults.max_tokens'));

        // Reasoning models don't support temperature
        if ($modelKey !== 'premium') {
            $requestParams['temperature'] = config('openai.defaults.temperature');
        }

        $response = $this->callOpenAI('/chat/completions', $requestParams);

        $content = $response['choices'][0]['message']['content'] ?? '';

        // Log usage
        if (isset($meta['company_id'], $meta['user_id'])) {
            AIUsageLog::log(
                $meta['company_id'],
                $meta['user_id'],
                'gpt5-mini',
                $response['usage']['prompt_tokens'] ?? 0,
                $response['usage']['completion_tokens'] ?? 0
            );
        }

        return $content;
    }

    public function analyzeImage(string $imageUrl, array $productContext = [], ?int $companyId = null, ?int $userId = null): array
    {
        $prompt = $this->buildImageAnalysisPrompt($productContext);

        $payload = [
            'model' => $this->models['vision'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Ты — эксперт по анализу товаров для маркетплейсов. Анализируй изображения и определяй характеристики товара.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $prompt],
                        ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]],
                    ],
                ],
            ],
        ];

        $this->applyTokenLimit($payload, 2000);

        $response = $this->callOpenAI('/chat/completions', $payload);

        $content = $response['choices'][0]['message']['content'] ?? '';

        // Log usage
        if ($companyId && $userId) {
            AIUsageLog::log(
                $companyId,
                $userId,
                'gpt-vision',
                $response['usage']['prompt_tokens'] ?? 0,
                $response['usage']['completion_tokens'] ?? 0
            );
        }

        return $this->parseImageAnalysis($content);
    }

    public function generateProductTexts(
        array $productContext,
        string $marketplace,
        string $language,
        ?int $companyId = null,
        ?int $userId = null
    ): array {
        $prompt = $this->buildProductTextPrompt($productContext, $marketplace, $language);

        $payload = [
            'model' => $this->models['text'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $this->getProductTextSystemPrompt($marketplace, $language),
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
        ];

        $this->applyTokenLimit($payload, 4000);

        $response = $this->callOpenAI('/chat/completions', $payload);

        $content = $response['choices'][0]['message']['content'] ?? '';

        // Log usage
        if ($companyId && $userId) {
            AIUsageLog::log(
                $companyId,
                $userId,
                'gpt5-mini',
                $response['usage']['prompt_tokens'] ?? 0,
                $response['usage']['completion_tokens'] ?? 0
            );
        }

        return $this->parseProductTexts($content);
    }

    public function generateImages(
        string $prompt,
        string $quality = 'medium',
        int $count = 1,
        ?int $companyId = null,
        ?int $userId = null,
        string $imageModel = 'dalle3'
    ): array {
        if ($imageModel === 'gpt4o') {
            return $this->generateImagesWithGpt4o($prompt, $companyId, $userId);
        }

        return $this->generateImagesWithDalle($prompt, $quality, $count, $companyId, $userId);
    }

    private function generateImagesWithDalle(
        string $prompt,
        string $quality = 'medium',
        int $count = 1,
        ?int $companyId = null,
        ?int $userId = null
    ): array {
        $size = config("openai.image.quality.{$quality}", '1024x1024');

        $images = [];
        for ($i = 0; $i < $count; $i++) {
            $response = $this->callOpenAI('/images/generations', [
                'model' => $this->models['image'],
                'prompt' => $this->sanitizeImagePrompt($prompt),
                'n' => 1,
                'size' => $size,
                'quality' => $quality === 'high' ? 'hd' : 'standard',
            ]);

            if (! empty($response['data'][0]['url'])) {
                $images[] = $response['data'][0]['url'];
            }
        }

        // Log usage
        if ($companyId && $userId) {
            AIUsageLog::log(
                $companyId,
                $userId,
                'dalle-3',
                0,
                0,
                count($images)
            );
        }

        return $images;
    }

    private function generateImagesWithGpt4o(
        string $prompt,
        ?int $companyId = null,
        ?int $userId = null
    ): array {
        // GPT-4o image generation uses chat completions with image output
        $payload = [
            'model' => 'gpt-4o',
            'messages' => [
                [
                    'role' => 'user',
                    'content' => "Generate a photorealistic product image: {$prompt}. Make it look like a professional e-commerce photo with clean white background, studio lighting, high resolution.",
                ],
            ],
        ];

        $this->applyTokenLimit($payload, 4096);

        $response = $this->callOpenAI('/chat/completions', $payload);

        $images = [];

        // Check if response contains image
        $content = $response['choices'][0]['message']['content'] ?? '';

        // GPT-4o with image generation returns image in the response
        // For now, if GPT-4o doesn't support native image gen, we fall back to DALL-E with enhanced prompt
        if (empty($images)) {
            // Use DALL-E with photorealistic prompt enhancement
            $enhancedPrompt = "Photorealistic product photography, {$prompt}, professional studio lighting, clean white background, high resolution, commercial e-commerce style, sharp focus, no artistic stylization, real photo look";

            $dalleResponse = $this->callOpenAI('/images/generations', [
                'model' => 'dall-e-3',
                'prompt' => $this->sanitizeImagePrompt($enhancedPrompt),
                'n' => 1,
                'size' => '1024x1024',
                'quality' => 'hd',
                'style' => 'natural', // More photorealistic
            ]);

            if (! empty($dalleResponse['data'][0]['url'])) {
                $images[] = $dalleResponse['data'][0]['url'];
            }
        }

        // Log usage
        if ($companyId && $userId) {
            AIUsageLog::log(
                $companyId,
                $userId,
                'gpt4o-image',
                $response['usage']['prompt_tokens'] ?? 0,
                $response['usage']['completion_tokens'] ?? 0,
                count($images)
            );
        }

        return $images;
    }

    public function generateReviewResponses(
        string $review,
        ?int $rating,
        string $style,
        ?string $productContext,
        int $companyId,
        int $userId
    ): array {
        $styles = [
            'formal' => 'официальный, формальный тон',
            'friendly' => 'дружелюбный, тёплый тон',
            'brief' => 'краткий, лаконичный ответ',
        ];

        $prompt = "Отзыв клиента:\n\"{$review}\"\n\n";
        if ($rating) {
            $prompt .= "Оценка: {$rating} из 5 звёзд\n\n";
        }
        if ($productContext) {
            $prompt .= "Контекст товара: {$productContext}\n\n";
        }
        $prompt .= "Напиши 3 варианта ответа на отзыв в разных стилях:\n";
        $prompt .= "1. Официальный/формальный\n";
        $prompt .= "2. Дружелюбный\n";
        $prompt .= "3. Краткий\n\n";
        $prompt .= "Формат ответа:\n";
        $prompt .= "---FORMAL---\n[ответ]\n---FRIENDLY---\n[ответ]\n---BRIEF---\n[ответ]";

        $payload = [
            'model' => $this->models['text'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Ты — специалист по работе с клиентами маркетплейса. Пиши ответы на отзывы, которые помогают улучшить репутацию продавца и удовлетворённость клиентов.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        $this->applyTokenLimit($payload, 2000);

        $response = $this->callOpenAI('/chat/completions', $payload);

        $content = $response['choices'][0]['message']['content'] ?? '';

        AIUsageLog::log(
            $companyId,
            $userId,
            'gpt5-mini',
            $response['usage']['prompt_tokens'] ?? 0,
            $response['usage']['completion_tokens'] ?? 0
        );

        return $this->parseReviewResponses($content);
    }

    private function callOpenAI(string $endpoint, array $data): array
    {
        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type' => 'application/json',
        ])->timeout(120)->post("{$this->baseUrl}{$endpoint}", $data);

        if (! $response->successful()) {
            Log::error('OpenAI API Error', [
                'endpoint' => $endpoint,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new \Exception('Ошибка при обращении к AI: '.$response->body());
        }

        return $response->json();
    }

    private function applyTokenLimit(array &$params, int $maxTokens): void
    {
        // Newer OpenAI chat models expect max_completion_tokens instead of max_tokens
        $params['max_completion_tokens'] = $maxTokens;
    }

    private function buildMessages(array $context, string $prompt, array $meta): array
    {
        // Use custom system prompt if provided, otherwise use default
        $systemPrompt = $meta['system_prompt'] ?? $this->getSystemPrompt();

        $messages = [
            [
                'role' => 'system',
                'content' => $systemPrompt,
            ],
        ];

        foreach ($context as $msg) {
            $messages[] = $msg;
        }

        // Handle images in the prompt
        if (! empty($meta['images'])) {
            $content = [
                ['type' => 'text', 'text' => $prompt],
            ];
            foreach ($meta['images'] as $imageUrl) {
                $content[] = ['type' => 'image_url', 'image_url' => ['url' => $imageUrl]];
            }
            $messages[] = ['role' => 'user', 'content' => $content];
        } else {
            $messages[] = ['role' => 'user', 'content' => $prompt];
        }

        return $messages;
    }

    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
Ты — SellerMind AI, умный ассистент для селлеров маркетплейсов (Uzum, Wildberries, Ozon, Yandex Market).

Твои возможности:
- Создание и улучшение карточек товаров (названия, описания, характеристики)
- Анализ фотографий товаров
- Помощь с ответами на отзывы клиентов
- Консультации по SEO и оптимизации карточек
- Советы по ценообразованию и продвижению

Правила:
- Отвечай конкретно и по делу
- Учитывай специфику каждого маркетплейса
- Предлагай варианты на выбор, когда это уместно
- Используй русский язык по умолчанию, узбекский — по запросу
PROMPT;
    }

    private function getProductTextSystemPrompt(string $marketplace, string $language): string
    {
        $marketplaceRules = [
            'uzum' => 'Uzum Market: максимум 200 символов в названии, обязательно указывать размеры и материал',
            'wb' => 'Wildberries: SEO-оптимизированные названия, ключевые слова в начале',
            'ozon' => 'Ozon: структурированные характеристики, подробные описания',
            'ym' => 'Yandex Market: точные характеристики, соответствие категориям',
            'universal' => 'Универсальный формат, подходящий для любого маркетплейса',
        ];

        $lang = $language === 'uz' ? 'узбекском' : 'русском';

        return <<<PROMPT
Ты — эксперт по созданию карточек товаров для маркетплейсов.

Маркетплейс: {$marketplaceRules[$marketplace]}

Создай карточку товара на {$lang} языке.

Формат ответа (JSON):
{
    "title": "Название товара",
    "short_description": "Краткое описание (1-2 предложения)",
    "full_description": "Полное описание товара",
    "bullets": ["Преимущество 1", "Преимущество 2", ...],
    "attributes": {"Материал": "...", "Размер": "...", ...},
    "keywords": ["ключевое слово 1", "ключевое слово 2", ...]
}
PROMPT;
    }

    private function buildImageAnalysisPrompt(array $context): string
    {
        $prompt = "Проанализируй это изображение товара и определи:\n";
        $prompt .= "1. Тип товара и категорию\n";
        $prompt .= "2. Материал и цвет\n";
        $prompt .= "3. Особенности (карманы, молнии, декор и т.п.)\n";
        $prompt .= "4. Примерный ценовой сегмент\n";
        $prompt .= "5. Целевую аудиторию\n\n";

        if (! empty($context['category'])) {
            $prompt .= "Предполагаемая категория: {$context['category']}\n";
        }
        if (! empty($context['brand'])) {
            $prompt .= "Бренд: {$context['brand']}\n";
        }

        $prompt .= "\nФормат ответа (JSON):\n";
        $prompt .= '{"type": "...", "category": "...", "material": "...", "color": "...", "features": [...], "price_segment": "...", "target_audience": "..."}';

        return $prompt;
    }

    private function buildProductTextPrompt(array $context, string $marketplace, string $language): string
    {
        $prompt = "Создай карточку товара для маркетплейса.\n\n";

        if (! empty($context['images'])) {
            $prompt .= "Изображения товара приложены.\n";
        }
        if (! empty($context['category'])) {
            $prompt .= "Категория: {$context['category']}\n";
        }
        if (! empty($context['brand'])) {
            $prompt .= "Бренд: {$context['brand']}\n";
        }
        if (! empty($context['analysis'])) {
            $prompt .= 'Анализ изображения: '.json_encode($context['analysis'], JSON_UNESCAPED_UNICODE)."\n";
        }

        return $prompt;
    }

    private function parseImageAnalysis(string $content): array
    {
        // Try to extract JSON from the response
        if (preg_match('/\{[^{}]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded) {
                return $decoded;
            }
        }

        return [
            'raw_analysis' => $content,
        ];
    }

    private function parseProductTexts(string $content): array
    {
        // Try to extract JSON from the response
        if (preg_match('/\{[\s\S]*\}/', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if ($decoded) {
                return $decoded;
            }
        }

        return [
            'title' => '',
            'short_description' => '',
            'full_description' => $content,
            'bullets' => [],
            'attributes' => [],
            'keywords' => [],
        ];
    }

    private function parseReviewResponses(string $content): array
    {
        $responses = [
            'formal' => '',
            'friendly' => '',
            'brief' => '',
        ];

        if (preg_match('/---FORMAL---\s*([\s\S]*?)(?=---FRIENDLY---|$)/i', $content, $m)) {
            $responses['formal'] = trim($m[1]);
        }
        if (preg_match('/---FRIENDLY---\s*([\s\S]*?)(?=---BRIEF---|$)/i', $content, $m)) {
            $responses['friendly'] = trim($m[1]);
        }
        if (preg_match('/---BRIEF---\s*([\s\S]*?)$/i', $content, $m)) {
            $responses['brief'] = trim($m[1]);
        }

        return $responses;
    }

    private function sanitizeImagePrompt(string $prompt): string
    {
        // Remove any potentially problematic content
        $prompt = preg_replace('/[^\p{L}\p{N}\s\-.,!?()]/u', '', $prompt);

        return mb_substr($prompt, 0, 1000);
    }
}
