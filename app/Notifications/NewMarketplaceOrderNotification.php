<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о новом заказе на маркетплейсе.
 */
final class NewMarketplaceOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Создание нового экземпляра уведомления о заказе на маркетплейсе.
     */
    public function __construct(
        public string $marketplace,
        public string $accountName,
        public string $orderNumber,
        public float $totalAmount,
        public string $currency,
    ) {}

    /**
     * Каналы доставки уведомления.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notificationSettings) {
            // Проверяем бизнес-часы, если настроено
            if ($notifiable->notificationSettings->notify_only_business_hours &&
                ! $notifiable->notificationSettings->shouldNotifyNow()) {
                return ['database']; // Только БД в нерабочее время
            }

            if ($notifiable->notificationSettings->channel_telegram &&
                $notifiable->notificationSettings->notify_marketplace_order) {
                $channels[] = TelegramChannel::class;
            }
        } else {
            // По умолчанию: отправляем в Telegram, если настроен
            if ($notifiable->telegram_id && $notifiable->telegram_notifications_enabled) {
                $channels[] = TelegramChannel::class;
            }
        }

        return $channels;
    }

    /**
     * Telegram-представление уведомления.
     */
    public function toTelegram(object $notifiable): array
    {
        $emoji = $this->getMarketplaceEmoji();
        $label = $this->getMarketplaceLabel();

        $message = "{$emoji} *Новый заказ на {$label}*\n\n";
        $message .= "Аккаунт: *{$this->accountName}*\n";
        $message .= "Заказ: `{$this->orderNumber}`\n\n";
        $message .= "💰 Сумма: *{$this->totalAmount}* {$this->currency}";

        return [
            'text' => $message,
            'options' => [
                'parse_mode' => 'Markdown',
            ],
        ];
    }

    /**
     * Массив-представление уведомления для БД.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'new_marketplace_order',
            'marketplace' => $this->marketplace,
            'account_name' => $this->accountName,
            'order_number' => $this->orderNumber,
            'total_amount' => $this->totalAmount,
            'currency' => $this->currency,
            'message' => "Новый заказ {$this->orderNumber} на {$this->getMarketplaceLabel()} ({$this->accountName})",
        ];
    }

    /**
     * Получить эмодзи маркетплейса.
     */
    private function getMarketplaceEmoji(): string
    {
        return match ($this->marketplace) {
            'wildberries' => "\u{1F7E3}",
            'ozon' => "\u{1F535}",
            'uzum' => "\u{1F7E2}",
            'yandex_market' => "\u{1F7E1}",
            default => "\u{1F4E6}",
        };
    }

    /**
     * Получить читаемое название маркетплейса.
     */
    private function getMarketplaceLabel(): string
    {
        return match ($this->marketplace) {
            'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            'uzum' => 'Uzum Market',
            'yandex_market' => 'Yandex Market',
            default => $this->marketplace,
        };
    }
}
