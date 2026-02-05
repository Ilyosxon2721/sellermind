<?php

namespace App\Telegram;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private string $token;

    private string $baseUrl = 'https://api.telegram.org/bot';

    public function __construct()
    {
        $this->token = config('telegram.bot_token');
    }

    public function sendMessage(int|string $chatId, string $text, array $options = []): array
    {
        return $this->request('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options));
    }

    public function sendPhoto(int|string $chatId, string $photo, ?string $caption = null, array $options = []): array
    {
        return $this->request('sendPhoto', array_merge([
            'chat_id' => $chatId,
            'photo' => $photo,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ], $options));
    }

    public function sendDocument(int|string $chatId, string $document, ?string $caption = null, array $options = []): array
    {
        return $this->request('sendDocument', array_merge([
            'chat_id' => $chatId,
            'document' => $document,
            'caption' => $caption,
            'parse_mode' => 'HTML',
        ], $options));
    }

    public function editMessageText(int|string $chatId, int $messageId, string $text, array $options = []): array
    {
        return $this->request('editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options));
    }

    public function answerCallbackQuery(string $callbackQueryId, ?string $text = null, bool $showAlert = false): array
    {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    public function setWebhook(string $url): array
    {
        return $this->request('setWebhook', [
            'url' => $url,
            'allowed_updates' => ['message', 'callback_query'],
        ]);
    }

    public function deleteWebhook(): array
    {
        return $this->request('deleteWebhook');
    }

    public function getFile(string $fileId): array
    {
        return $this->request('getFile', [
            'file_id' => $fileId,
        ]);
    }

    public function downloadFile(string $filePath): ?string
    {
        $url = "https://api.telegram.org/file/bot{$this->token}/{$filePath}";
        $response = Http::get($url);

        if ($response->successful()) {
            return $response->body();
        }

        return null;
    }

    public function getFileUrl(string $fileId): ?string
    {
        $result = $this->getFile($fileId);
        if (! empty($result['result']['file_path'])) {
            return "https://api.telegram.org/file/bot{$this->token}/{$result['result']['file_path']}";
        }

        return null;
    }

    public function buildInlineKeyboard(array $buttons): array
    {
        return ['inline_keyboard' => $buttons];
    }

    public function buildReplyKeyboard(array $buttons, bool $resize = true, bool $oneTime = false): array
    {
        return [
            'keyboard' => $buttons,
            'resize_keyboard' => $resize,
            'one_time_keyboard' => $oneTime,
        ];
    }

    private function request(string $method, array $params = []): array
    {
        $url = "{$this->baseUrl}{$this->token}/{$method}";

        $response = Http::post($url, $params);

        if (! $response->successful()) {
            Log::error('Telegram API Error', [
                'method' => $method,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }

        return $response->json() ?? [];
    }
}
