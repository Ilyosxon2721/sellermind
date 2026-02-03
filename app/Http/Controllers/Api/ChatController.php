<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DialogResource;
use App\Http\Resources\MessageResource;
use App\Models\Dialog;
use App\Models\Message;
use App\Services\AIService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    public function __construct(
        private AIService $aiService
    ) {}

    public function send(Request $request): JsonResponse
    {
        $request->validate([
            'dialog_id' => ['nullable', 'exists:dialogs,id'],
            'company_id' => ['nullable', 'integer'],
            'message' => ['required', 'string', 'max:10000'],
            'mode' => ['nullable', 'in:chat,cards,photos,reviews,analytics,seo'],
            'model' => ['nullable', 'in:fast,smart,premium'],
            'image_model' => ['nullable', 'in:dalle3,gpt4o'],
            'images' => ['nullable', 'array'],
            'images.*' => ['string'], // URLs or base64
            'is_private' => ['nullable', 'boolean'],
        ]);

        $user = $request->user();
        $mode = $request->mode ?? 'chat';
        $model = $request->model ?? 'fast';
        $imageModel = $request->image_model ?? 'dalle3';

        // Get or create dialog
        if ($request->dialog_id) {
            $dialog = Dialog::findOrFail($request->dialog_id);

            if (method_exists($user, 'hasCompanyAccess') && ! $user->hasCompanyAccess($dialog->company_id)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }

            if ($dialog->user_id !== $user->id) {
                return response()->json(['message' => 'Это не ваш диалог.'], 403);
            }
        } else {
            $companyId = $request->input('company_id', $user->company_id);
            if (! $companyId) {
                return response()->json(['message' => 'Компания не выбрана.'], 422);
            }

            if (method_exists($user, 'hasCompanyAccess') && ! $user->hasCompanyAccess($companyId)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }

            $dialog = Dialog::create([
                'company_id' => $companyId,
                'user_id' => $user->id,
                'category' => $this->mapModeToCategory($mode),
                'is_private' => $request->boolean('is_private'),
            ]);
        }

        // Save user message
        $userMessage = Message::create([
            'dialog_id' => $dialog->id,
            'sender' => 'user',
            'content' => $request->message,
            'meta' => array_filter([
                'images' => $request->images,
                'mode' => $mode,
            ]),
        ]);

        // Get conversation context
        $context = $dialog->getContext(20);

        // Generate AI response based on mode and model
        $aiResponse = $this->generateResponseByMode(
            $mode,
            $context,
            $request->message,
            [
                'company_id' => $dialog->company_id,
                'user_id' => $user->id,
                'images' => $request->images ?? [],
                'model' => $model,
                'image_model' => $imageModel,
                'is_private' => $dialog->is_private,
            ]
        );

        // Save assistant message
        $assistantMessage = Message::create([
            'dialog_id' => $dialog->id,
            'sender' => 'assistant',
            'content' => $aiResponse,
            'meta' => ['mode' => $mode],
        ]);

        // Update dialog title if it's the first message
        if ($dialog->messages()->count() <= 2 && ! $dialog->title) {
            $title = $this->generateDialogTitle($request->message, $mode, $dialog->is_private);
            $dialog->update(['title' => $title]);
        }

        $dialog->touch();

        return response()->json([
            'dialog' => new DialogResource($dialog),
            'user_message' => new MessageResource($userMessage),
            'assistant_message' => new MessageResource($assistantMessage),
        ]);
    }

    private function generateResponseByMode(string $mode, array $context, string $message, array $meta): string
    {
        // Handle image generation mode
        if ($mode === 'photos') {
            return $this->handlePhotoMode($message, $meta);
        }

        $isPrivate = $meta['is_private'] ?? false;

        // В приватном режиме - универсальный ассистент без ограничений темы
        if ($isPrivate) {
            $systemPrompt = 'Ты — умный и дружелюбный AI ассистент. Отвечай на любые вопросы пользователя, помогай с любыми задачами. Будь полезным, информативным и приятным в общении. Можешь обсуждать любые темы: программирование, наука, история, творчество, советы, развлечения и многое другое. Отвечай на языке пользователя.';

            return $this->aiService->generateChatResponse(
                $context,
                $message,
                array_merge($meta, ['system_prompt' => $systemPrompt])
            );
        }

        $systemPrompts = [
            'chat' => 'Ты — SellerMind AI, умный ассистент для селлеров маркетплейсов (Uzum, Wildberries, Ozon, Yandex Market). Помогай с любыми вопросами о продажах на маркетплейсах.',

            'cards' => 'Ты — эксперт по созданию карточек товаров для маркетплейсов. Создавай SEO-оптимизированные заголовки, описания и буллеты. Учитывай требования площадок Uzum, Wildberries, Ozon, Yandex Market. Отвечай структурированно с разделами: Заголовок, Краткое описание, Полное описание, Буллеты, Ключевые слова.',

            'reviews' => 'Ты — эксперт по работе с отзывами на маркетплейсах. Генерируй вежливые, профессиональные ответы на отзывы покупателей. Для негативных — предлагай решения. Для позитивных — благодари и приглашай вернуться. Давай 3 варианта ответа разной тональности.',

            'analytics' => 'Ты — аналитик продаж на маркетплейсах. Помогай анализировать метрики, конкурентов, тренды. Давай рекомендации по улучшению продаж, ценообразованию, ассортименту.',

            'seo' => 'Ты — SEO-специалист для маркетплейсов. Оптимизируй тексты карточек для поисковой выдачи. Подбирай ключевые слова, улучшай заголовки, анализируй конкурентность запросов.',
        ];

        $systemPrompt = $systemPrompts[$mode] ?? $systemPrompts['chat'];

        return $this->aiService->generateChatResponse(
            $context,
            $message,
            array_merge($meta, ['system_prompt' => $systemPrompt])
        );
    }

    private function handlePhotoMode(string $message, array $meta): string
    {
        $imageModel = $meta['image_model'] ?? 'dalle3';

        // First, translate and optimize the prompt using GPT
        try {
            $optimizedPrompt = $this->aiService->generateChatResponse(
                [],
                "Create image generation prompt for: {$message}",
                [
                    'model' => 'fast',
                    'system_prompt' => 'You are an image generation prompt creator. You MUST output ONLY the image generation prompt in English, nothing else. No explanations, no apologies, no refusals.

Transform any user request into a professional e-commerce product photo prompt.

Format your output as a single paragraph describing:
- The product
- Professional studio lighting (soft diffused light)
- Clean white or gradient background
- High-resolution product photography style
- Composition details

Example input: "красные кроссовки"
Example output: "Professional product photography of red athletic sneakers, studio lighting with soft diffused light, clean white background, high resolution, commercial e-commerce style, 45-degree angle view, sharp focus on product details"

IMPORTANT: Output ONLY the prompt text. Do not say you cannot generate images. Do not add any commentary.',
                ]
            );

            // Generate image using selected model
            $images = $this->aiService->generateImages(
                $optimizedPrompt,
                'medium',
                1,
                $meta['company_id'] ?? null,
                $meta['user_id'] ?? null,
                $imageModel
            );

            $modelName = $imageModel === 'gpt4o' ? 'GPT-4o' : 'DALL-E 3';

            if (! empty($images)) {
                $imageUrl = $images[0];

                return "Вот сгенерированное изображение:\n\n![Сгенерированное фото]({$imageUrl})\n\n[download:{$imageUrl}]";
            }

            return 'Не удалось сгенерировать изображение. Пожалуйста, попробуйте изменить описание.';
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();

            // Handle content policy violation
            if (str_contains($errorMessage, 'content_policy_violation') || str_contains($errorMessage, 'safety system')) {
                return "К сожалению, запрос не прошёл проверку безопасности OpenAI. Попробуйте:\n\n• Описать товар более нейтрально\n• Убрать названия брендов\n• Избегать упоминания людей\n\n**Ваш запрос:** {$message}";
            }

            return 'Ошибка при генерации изображения. Попробуйте переформулировать запрос.';
        }
    }

    private function mapModeToCategory(string $mode): string
    {
        return match ($mode) {
            'cards' => 'cards',
            'photos' => 'images',
            'reviews' => 'reviews',
            'analytics' => 'analytics',
            'seo' => 'seo',
            default => 'general',
        };
    }

    public function generateCard(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['nullable', 'integer'],
            'images' => ['required', 'array', 'min:1', 'max:5'],
            'images.*' => ['string'],
            'marketplace' => ['required', 'in:uzum,wb,ozon,ym,universal'],
            'language' => ['nullable', 'in:ru,uz'],
            'category' => ['nullable', 'string'],
            'brand' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $companyId = $request->input('company_id', $user->company_id);

        $cardData = $this->aiService->generateProductTexts(
            [
                'images' => $request->images,
                'category' => $request->category,
                'brand' => $request->brand,
            ],
            $request->marketplace,
            $request->language ?? 'ru',
            $companyId,
            $user->id
        );

        return response()->json([
            'card' => $cardData,
        ]);
    }

    public function generateReviewResponse(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => ['nullable', 'integer'],
            'review' => ['required', 'string', 'max:5000'],
            'rating' => ['nullable', 'integer', 'min:1', 'max:5'],
            'style' => ['nullable', 'in:formal,friendly,brief'],
            'product_context' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $companyId = $request->input('company_id', $user->company_id);

        $responses = $this->aiService->generateReviewResponses(
            $request->review,
            $request->rating,
            $request->style ?? 'friendly',
            $request->product_context,
            $companyId,
            $user->id
        );

        return response()->json([
            'responses' => $responses,
        ]);
    }

    private function generateDialogTitle(string $message, string $mode = 'chat', bool $isPrivate = false): string
    {
        $modePrefix = match ($mode) {
            'cards' => '📦 ',
            'photos' => '🖼️ ',
            'reviews' => '⭐ ',
            'analytics' => '📊 ',
            'seo' => '🔍 ',
            default => '',
        };

        if ($isPrivate) {
            $modePrefix = '🔒 '.$modePrefix;
        }

        $title = mb_substr($message, 0, 45);
        if (mb_strlen($message) > 45) {
            $title .= '...';
        }

        return $modePrefix.$title;
    }

    public function hideDialog(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $dialog = Dialog::where('user_id', $user->id)->findOrFail($id);

        $dialog->hide();

        return response()->json(['message' => 'Диалог скрыт.']);
    }
}
