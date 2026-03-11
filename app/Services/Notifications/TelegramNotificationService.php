<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\EventType;
use App\Models\MarketplaceEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramNotificationService
{
    private string $botToken;
    private bool $enabled;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->enabled  = (bool) config('services.telegram.notifications_enabled', false);
    }

    /**
     * Отправить уведомление о событии в Telegram
     */
    public function notify(MarketplaceEvent $event): void
    {
        if (! $this->enabled || empty($this->botToken)) {
            return;
        }

        $account = $event->store;
        if (! $account) {
            return;
        }

        // Берём chat_id из пользователя — Telegram привязан на уровне аккаунта
        $user = $account->user;
        if (! $user || ! $user->telegram_id || ! $user->telegram_notifications_enabled) {
            return;
        }

        $chatId = (string) $user->telegram_id;

        $message = $this->buildMessage($event);
        if (! $message) {
            return;
        }

        $this->send($chatId, $message);
    }

    private function buildMessage(MarketplaceEvent $event): ?string
    {
        return match ($event->event_type) {
            EventType::ORDER_CREATED        => $this->orderCreatedMessage($event),
            EventType::ORDER_CANCELLED      => $this->orderCancelledMessage($event),
            EventType::ORDER_STATUS_CHANGED => $this->orderStatusMessage($event),
            EventType::RETURN_CREATED       => $this->returnMessage($event),
            EventType::CHAT_MESSAGE_CREATED => $this->chatMessage($event),
            default                         => null,
        };
    }

    private function orderCreatedMessage(MarketplaceEvent $event): string
    {
        return "✅ *НОВЫЙ ЗАКАЗ*\n\n"
            . "📦 Маркетплейс: {$event->marketplace->label()}\n"
            . "🔢 Заказ: \#{$event->entity_id}\n"
            . "🕐 Время: " . $event->created_at->format('d.m.Y H:i') . "\n";
    }

    private function orderCancelledMessage(MarketplaceEvent $event): string
    {
        return "❌ *ЗАКАЗ ОТМЕНЁН*\n\n"
            . "📦 Маркетплейс: {$event->marketplace->label()}\n"
            . "🔢 Заказ: \#{$event->entity_id}\n"
            . "🕐 Время: " . $event->created_at->format('d.m.Y H:i') . "\n";
    }

    private function orderStatusMessage(MarketplaceEvent $event): string
    {
        return "⚠️ *СТАТУС ЗАКАЗА ИЗМЕНЁН*\n\n"
            . "📦 Маркетплейс: {$event->marketplace->label()}\n"
            . "🔢 Заказ: \#{$event->entity_id}\n"
            . "🕐 Время: " . $event->created_at->format('d.m.Y H:i') . "\n";
    }

    private function returnMessage(MarketplaceEvent $event): string
    {
        return "🔄 *ВОЗВРАТ*\n\n"
            . "📦 Маркетплейс: {$event->marketplace->label()}\n"
            . "🔢 Заказ: \#{$event->entity_id}\n"
            . "🕐 Время: " . $event->created_at->format('d.m.Y H:i') . "\n";
    }

    private function chatMessage(MarketplaceEvent $event): string
    {
        return "💬 *НОВОЕ СООБЩЕНИЕ*\n\n"
            . "📦 Маркетплейс: {$event->marketplace->label()}\n"
            . "🕐 Время: " . $event->created_at->format('d.m.Y H:i') . "\n";
    }

    private function send(string $chatId, string $message): void
    {
        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id'    => $chatId,
                'text'       => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed', ['error' => $e->getMessage()]);
        }
    }
}
