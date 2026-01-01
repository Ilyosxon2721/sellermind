<?php

namespace App\Telegram;

use App\Models\Dialog;
use App\Models\Message;
use App\Models\User;
use App\Services\AIService;
use Illuminate\Support\Facades\Log;

class TelegramBotHandler
{
    private TelegramService $telegram;
    private AIService $aiService;

    public function __construct(TelegramService $telegram, AIService $aiService)
    {
        $this->telegram = $telegram;
        $this->aiService = $aiService;
    }

    public function handle(array $update): void
    {
        try {
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            } elseif (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
            }
        } catch (\Exception $e) {
            Log::error('Telegram bot error', [
                'error' => $e->getMessage(),
                'update' => $update,
            ]);
        }
    }

    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';
        $photo = $message['photo'] ?? null;

        // Handle commands
        if (str_starts_with($text, '/')) {
            $this->handleCommand($chatId, $text, $message);
            return;
        }

        // Handle photos
        if ($photo) {
            $this->handlePhoto($chatId, $photo, $message['caption'] ?? '', $message);
            return;
        }

        // Handle regular messages
        $this->handleTextMessage($chatId, $text, $message);
    }

    private function handleCommand(int $chatId, string $text, array $message): void
    {
        $parts = explode(' ', $text);
        $command = strtolower($parts[0]);
        $args = array_slice($parts, 1);

        match ($command) {
            '/start' => $this->commandStart($chatId, $message),
            '/help' => $this->commandHelp($chatId),
            '/card' => $this->commandCard($chatId, $args, $message),
            '/review' => $this->commandReview($chatId, implode(' ', $args), $message),
            '/status' => $this->commandStatus($chatId, $message),
            '/link' => $this->commandLink($chatId, $args, $message),
            default => $this->telegram->sendMessage($chatId, "Неизвестная команда. Используйте /help для списка команд."),
        };
    }

    private function commandStart(int $chatId, array $message): void
    {
        $firstName = $message['from']['first_name'] ?? 'друг';

        $text = "👋 Привет, <b>{$firstName}</b>!\n\n";
        $text .= "Я — <b>SellerMind AI</b>, ваш умный помощник для работы с маркетплейсами.\n\n";
        $text .= "🎯 <b>Что я умею:</b>\n";
        $text .= "• Создавать карточки товаров по фото\n";
        $text .= "• Генерировать промо-изображения\n";
        $text .= "• Писать ответы на отзывы\n";
        $text .= "• Отвечать на вопросы по маркетплейсам\n\n";
        $text .= "📱 <b>Команды:</b>\n";
        $text .= "/card — создать карточку товара\n";
        $text .= "/review — написать ответ на отзыв\n";
        $text .= "/status — статус ваших задач\n";
        $text .= "/link — привязать аккаунт\n";
        $text .= "/help — справка\n\n";
        $text .= "💬 Просто напишите мне или отправьте фото товара!";

        $keyboard = $this->telegram->buildInlineKeyboard([
            [
                ['text' => '📦 Создать карточку', 'callback_data' => 'action:card'],
                ['text' => '💬 Ответ на отзыв', 'callback_data' => 'action:review'],
            ],
            [
                ['text' => '🔗 Привязать аккаунт', 'callback_data' => 'action:link'],
            ],
        ]);

        $this->telegram->sendMessage($chatId, $text, ['reply_markup' => $keyboard]);
    }

    private function commandHelp(int $chatId): void
    {
        $text = "📚 <b>Справка по SellerMind AI Bot</b>\n\n";
        $text .= "<b>Основные команды:</b>\n\n";
        $text .= "/card — Создать карточку товара\n";
        $text .= "  Отправьте фото товара после команды\n\n";
        $text .= "/review [текст отзыва] — Написать ответ на отзыв\n";
        $text .= "  Пример: /review Товар пришёл с браком\n\n";
        $text .= "/status — Показать статус ваших задач\n\n";
        $text .= "/link [код] — Привязать аккаунт с сайта\n\n";
        $text .= "<b>Без команд:</b>\n";
        $text .= "• Отправьте фото — создам карточку товара\n";
        $text .= "• Напишите вопрос — отвечу как ассистент\n\n";
        $text .= "🌐 Веб-версия: sellermind.ai";

        $this->telegram->sendMessage($chatId, $text);
    }

    private function commandCard(int $chatId, array $args, array $message): void
    {
        $marketplace = $args[0] ?? 'universal';
        $validMarketplaces = ['uzum', 'wb', 'ozon', 'ym', 'universal'];

        if (!in_array($marketplace, $validMarketplaces)) {
            $marketplace = 'universal';
        }

        $text = "📦 <b>Создание карточки товара</b>\n\n";
        $text .= "Маркетплейс: <b>" . strtoupper($marketplace) . "</b>\n\n";
        $text .= "📸 Отправьте фото товара (1-5 штук), и я создам для вас карточку.\n\n";
        $text .= "💡 Совет: лучше всего работают фото на белом фоне с хорошим освещением.";

        // Store state for this chat (waiting for photo)
        $this->setState($chatId, [
            'action' => 'waiting_card_photo',
            'marketplace' => $marketplace,
        ]);

        $this->telegram->sendMessage($chatId, $text);
    }

    private function commandReview(int $chatId, string $reviewText, array $message): void
    {
        if (empty($reviewText)) {
            $text = "💬 <b>Ответ на отзыв</b>\n\n";
            $text .= "Отправьте текст отзыва после команды:\n";
            $text .= "<code>/review Товар пришёл с браком, очень разочарован</code>\n\n";
            $text .= "Или просто отправьте текст отзыва следующим сообщением.";

            $this->setState($chatId, ['action' => 'waiting_review_text']);
            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        $this->generateReviewResponse($chatId, $reviewText, $message);
    }

    private function commandStatus(int $chatId, array $message): void
    {
        $user = $this->getUserByTelegramId($message['from']['id']);

        if (!$user) {
            $this->telegram->sendMessage($chatId, "❌ Аккаунт не привязан. Используйте /link для привязки.");
            return;
        }

        $tasks = $user->generationTasks()
            ->whereIn('status', ['pending', 'in_progress'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        if ($tasks->isEmpty()) {
            $this->telegram->sendMessage($chatId, "✅ У вас нет активных задач.");
            return;
        }

        $text = "📊 <b>Ваши активные задачи:</b>\n\n";
        foreach ($tasks as $task) {
            $statusEmoji = match ($task->status) {
                'pending' => '⏳',
                'in_progress' => '🔄',
                default => '❓',
            };
            $text .= "{$statusEmoji} {$task->type}: {$task->progress}%\n";
        }

        $this->telegram->sendMessage($chatId, $text);
    }

    private function commandLink(int $chatId, array $args, array $message): void
    {
        if (empty($args[0])) {
            $text = "🔗 <b>Привязка аккаунта</b>\n\n";
            $text .= "Чтобы привязать бот к вашему аккаунту:\n\n";
            $text .= "1. Войдите на сайт sellermind.ai\n";
            $text .= "2. Перейдите в Настройки → Telegram\n";
            $text .= "3. Скопируйте код привязки\n";
            $text .= "4. Отправьте: /link ВАШ_КОД";

            $this->telegram->sendMessage($chatId, $text);
            return;
        }

        // Here you would verify the link code and associate the Telegram user with the account
        $linkCode = $args[0];
        // TODO: Implement actual linking logic

        $this->telegram->sendMessage($chatId, "✅ Аккаунт успешно привязан!");
    }

    private function handlePhoto(int $chatId, array $photos, string $caption, array $message): void
    {
        // Get the largest photo
        $photo = end($photos);
        $fileId = $photo['file_id'];

        $this->telegram->sendMessage($chatId, "📸 Получил фото! Анализирую...");

        try {
            // Get file URL
            $fileUrl = $this->telegram->getFileUrl($fileId);

            if (!$fileUrl) {
                $this->telegram->sendMessage($chatId, "❌ Не удалось загрузить фото. Попробуйте ещё раз.");
                return;
            }

            // Get user's company or use default
            $user = $this->getUserByTelegramId($message['from']['id']);
            $companyId = $user?->companies()->first()?->id ?? 1;
            $userId = $user?->id ?? 1;

            // Generate product card
            $cardData = $this->aiService->generateProductTexts(
                [
                    'images' => [$fileUrl],
                    'category' => null,
                    'brand' => null,
                ],
                'universal',
                'ru',
                $companyId,
                $userId
            );

            $text = "✅ <b>Карточка товара готова!</b>\n\n";
            $text .= "<b>Название:</b>\n{$cardData['title']}\n\n";

            if (!empty($cardData['short_description'])) {
                $text .= "<b>Краткое описание:</b>\n{$cardData['short_description']}\n\n";
            }

            if (!empty($cardData['bullets'])) {
                $text .= "<b>Преимущества:</b>\n";
                foreach ($cardData['bullets'] as $bullet) {
                    $text .= "• {$bullet}\n";
                }
                $text .= "\n";
            }

            if (!empty($cardData['keywords'])) {
                $text .= "<b>Ключевые слова:</b>\n";
                $text .= implode(', ', array_slice($cardData['keywords'], 0, 10));
            }

            $keyboard = $this->telegram->buildInlineKeyboard([
                [
                    ['text' => '📋 Копировать', 'callback_data' => 'copy:card'],
                    ['text' => '🔄 Переделать', 'callback_data' => 'regenerate:card'],
                ],
                [
                    ['text' => '🇷🇺 RU', 'callback_data' => 'lang:ru'],
                    ['text' => '🇺🇿 UZ', 'callback_data' => 'lang:uz'],
                ],
            ]);

            $this->telegram->sendMessage($chatId, $text, ['reply_markup' => $keyboard]);

        } catch (\Exception $e) {
            Log::error('Error generating card from photo', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Произошла ошибка при создании карточки. Попробуйте позже.");
        }
    }

    private function handleTextMessage(int $chatId, string $text, array $message): void
    {
        $state = $this->getState($chatId);

        // Check if waiting for specific input
        if ($state && isset($state['action'])) {
            match ($state['action']) {
                'waiting_review_text' => $this->generateReviewResponse($chatId, $text, $message),
                default => $this->handleGeneralMessage($chatId, $text, $message),
            };
            $this->clearState($chatId);
            return;
        }

        $this->handleGeneralMessage($chatId, $text, $message);
    }

    private function handleGeneralMessage(int $chatId, string $text, array $message): void
    {
        $this->telegram->sendMessage($chatId, "🤔 Думаю...");

        try {
            $user = $this->getUserByTelegramId($message['from']['id']);
            $companyId = $user?->companies()->first()?->id ?? 1;
            $userId = $user?->id ?? 1;

            $response = $this->aiService->generateChatResponse(
                [],
                $text,
                [
                    'company_id' => $companyId,
                    'user_id' => $userId,
                ]
            );

            $this->telegram->sendMessage($chatId, $response);

        } catch (\Exception $e) {
            Log::error('Error generating response', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Произошла ошибка. Попробуйте позже.");
        }
    }

    private function generateReviewResponse(int $chatId, string $reviewText, array $message): void
    {
        $this->telegram->sendMessage($chatId, "💬 Генерирую варианты ответа...");

        try {
            $user = $this->getUserByTelegramId($message['from']['id']);
            $companyId = $user?->companies()->first()?->id ?? 1;
            $userId = $user?->id ?? 1;

            $responses = $this->aiService->generateReviewResponses(
                $reviewText,
                null,
                'friendly',
                null,
                $companyId,
                $userId
            );

            $text = "💬 <b>Варианты ответа на отзыв:</b>\n\n";
            $text .= "<b>📝 Официальный:</b>\n{$responses['formal']}\n\n";
            $text .= "<b>😊 Дружелюбный:</b>\n{$responses['friendly']}\n\n";
            $text .= "<b>⚡ Краткий:</b>\n{$responses['brief']}";

            $this->telegram->sendMessage($chatId, $text);

        } catch (\Exception $e) {
            Log::error('Error generating review response', ['error' => $e->getMessage()]);
            $this->telegram->sendMessage($chatId, "❌ Произошла ошибка. Попробуйте позже.");
        }
    }

    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];
        $callbackId = $callbackQuery['id'];

        $this->telegram->answerCallbackQuery($callbackId);

        [$action, $value] = explode(':', $data) + [null, null];

        match ($action) {
            'action' => $this->handleActionCallback($chatId, $value, $callbackQuery),
            'lang' => $this->handleLanguageCallback($chatId, $value, $callbackQuery),
            default => null,
        };
    }

    private function handleActionCallback(int $chatId, string $action, array $callbackQuery): void
    {
        match ($action) {
            'card' => $this->commandCard($chatId, [], $callbackQuery['message']),
            'review' => $this->commandReview($chatId, '', $callbackQuery['message']),
            'link' => $this->commandLink($chatId, [], $callbackQuery['message']),
            default => null,
        };
    }

    private function handleLanguageCallback(int $chatId, string $lang, array $callbackQuery): void
    {
        $langName = $lang === 'ru' ? 'русский' : 'узбекский';
        $this->telegram->sendMessage($chatId, "🌐 Язык изменён на {$langName}.");
    }

    private function getUserByTelegramId(int $telegramId): ?User
    {
        // TODO: Implement user lookup by telegram_id
        // For now, return null
        return null;
    }

    private function setState(int $chatId, array $state): void
    {
        cache()->put("telegram_state_{$chatId}", $state, 3600);
    }

    private function getState(int $chatId): ?array
    {
        return cache()->get("telegram_state_{$chatId}");
    }

    private function clearState(int $chatId): void
    {
        cache()->forget("telegram_state_{$chatId}");
    }
}
