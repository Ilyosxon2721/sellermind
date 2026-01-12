<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BulkOperationCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public int $updatedCount,
        public array $errors = []
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
                $notifiable->notificationSettings->notify_bulk_operations) {
                $channels[] = TelegramChannel::class;
            }
            if ($notifiable->notificationSettings->channel_email &&
                $notifiable->notificationSettings->notify_bulk_operations) {
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
        $message = "✅ *Массовое обновление завершено*\n\n";
        $message .= "Обновлено товаров: *{$this->updatedCount}*\n";

        if (count($this->errors) > 0) {
            $message .= "⚠️ Ошибок: *" . count($this->errors) . "*\n\n";
            $message .= "Первые ошибки:\n";
            foreach (array_slice($this->errors, 0, 3) as $error) {
                $message .= "• " . $error . "\n";
            }
        } else {
            $message .= "✨ Все изменения применены успешно!";
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
        return (new MailMessage)
            ->subject('Массовое обновление товаров завершено')
            ->line("Обновлено товаров: {$this->updatedCount}")
            ->when(count($this->errors) > 0, function ($mail) {
                return $mail->line("Ошибок: " . count($this->errors));
            })
            ->action('Перейти к товарам', url('/products'))
            ->line('Спасибо за использование SellerMind!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bulk_operation_completed',
            'updated_count' => $this->updatedCount,
            'errors_count' => count($this->errors),
            'errors' => $this->errors,
            'message' => "Обновлено товаров: {$this->updatedCount}",
        ];
    }
}
