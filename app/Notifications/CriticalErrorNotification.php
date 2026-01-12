<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CriticalErrorNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $title,
        public string $message,
        public ?string $context = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        // Critical errors always bypass business hours
        if ($notifiable->notificationSettings) {
            if ($notifiable->notificationSettings->channel_telegram &&
                $notifiable->notificationSettings->notify_critical_errors) {
                $channels[] = TelegramChannel::class;
            }
            if ($notifiable->notificationSettings->channel_email &&
                $notifiable->notificationSettings->notify_critical_errors) {
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
        $message = "🚨 *Критическая ошибка*\n\n";
        $message .= "*{$this->title}*\n\n";
        $message .= $this->message;

        if ($this->context) {
            $message .= "\n\n_Контекст:_ `{$this->context}`";
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
            ->error()
            ->subject("Критическая ошибка: {$this->title}")
            ->line($this->message);

        if ($this->context) {
            $mail->line("Контекст: {$this->context}");
        }

        return $mail->action('Перейти в панель', url('/dashboard'));
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'critical_error',
            'title' => $this->title,
            'message' => $this->message,
            'context' => $this->context,
        ];
    }
}
