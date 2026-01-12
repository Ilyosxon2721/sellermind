<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MarketplaceSyncCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $marketplace,
        public int $syncedCount,
        public bool $hasErrors = false,
        public ?string $errorMessage = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notificationSettings) {
            if ($notifiable->notificationSettings->channel_telegram &&
                $notifiable->notificationSettings->notify_marketplace_sync) {
                $channels[] = TelegramChannel::class;
            }
            if ($notifiable->notificationSettings->channel_email &&
                $notifiable->notificationSettings->notify_marketplace_sync) {
                $channels[] = 'mail';
            }
        } else {
            // Default: send to telegram if configured
            if ($notifiable->telegram_id && $notifiable->telegram_notifications_enabled) {
                $channels[] = TelegramChannel::class;
            }
        }

        return $channels;
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram(object $notifiable): array
    {
        $icon = $this->hasErrors ? '⚠️' : '✅';
        $status = $this->hasErrors ? 'с ошибками' : 'успешно';

        $message = "{$icon} *Синхронизация {$this->marketplace} {$status}*\n\n";
        $message .= "Синхронизировано товаров: *{$this->syncedCount}*\n";

        if ($this->hasErrors && $this->errorMessage) {
            $message .= "\n⚠️ Ошибка: {$this->errorMessage}";
        }

        return [
            'text' => $message,
            'options' => [
                'parse_mode' => 'Markdown',
            ],
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Синхронизация {$this->marketplace} завершена")
            ->line("Синхронизировано товаров: {$this->syncedCount}");

        if ($this->hasErrors && $this->errorMessage) {
            $mail->line("Ошибка: {$this->errorMessage}");
        }

        return $mail
            ->action('Перейти к товарам', url('/products'))
            ->line('Проверьте обновленные данные в системе.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace_sync_completed',
            'marketplace' => $this->marketplace,
            'synced_count' => $this->syncedCount,
            'has_errors' => $this->hasErrors,
            'error_message' => $this->errorMessage,
            'message' => "Синхронизация {$this->marketplace}: {$this->syncedCount} товаров",
        ];
    }
}
