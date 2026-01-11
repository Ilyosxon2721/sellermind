<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\Promotion;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PromotionExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Promotion $promotion,
        public int $daysLeft
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
                $notifiable->notificationSettings->notify_price_changes) {
                $channels[] = TelegramChannel::class;
            }
            if ($notifiable->notificationSettings->channel_email &&
                $notifiable->notificationSettings->notify_price_changes) {
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
        $icon = $this->daysLeft <= 1 ? '🚨' : '⏰';
        $urgency = $this->daysLeft <= 1 ? 'СРОЧНО' : 'Внимание';

        $message = "{$icon} *{$urgency}: Акция заканчивается!*\n\n";
        $message .= "*{$this->promotion->name}*\n";

        if ($this->promotion->description) {
            $message .= $this->promotion->description . "\n\n";
        }

        $message .= "⏰ Осталось: *{$this->daysLeft} " . $this->pluralize($this->daysLeft) . "*\n";
        $message .= "📅 Конец: " . $this->promotion->end_date->format('d.m.Y H:i') . "\n";
        $message .= "🏷️ Товаров: *{$this->promotion->products_count}*\n";
        $message .= "💰 Скидка: *{$this->promotion->discount_value}";
        $message .= $this->promotion->type === 'percentage' ? '%' : ' ₽';
        $message .= "*\n\n";

        if ($this->daysLeft <= 1) {
            $message .= "⚡ Последний шанс продлить или завершить акцию!";
        } else {
            $message .= "Проверьте результаты и решите, продлить или завершить акцию.";
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
        $urgency = $this->daysLeft <= 1 ? 'СРОЧНО' : 'Внимание';

        return (new MailMessage)
            ->subject("{$urgency}: Акция «{$this->promotion->name}» заканчивается!")
            ->line("Ваша акция «{$this->promotion->name}» заканчивается через {$this->daysLeft} " . $this->pluralize($this->daysLeft) . ".")
            ->line("Конец акции: " . $this->promotion->end_date->format('d.m.Y H:i'))
            ->line("Товаров в акции: {$this->promotion->products_count}")
            ->line("Скидка: {$this->promotion->discount_value}" . ($this->promotion->type === 'percentage' ? '%' : ' ₽'))
            ->action('Управление акциями', url('/promotions'))
            ->line('Проверьте результаты и решите, продлить или завершить акцию.');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'promotion_expiring',
            'promotion_id' => $this->promotion->id,
            'promotion_name' => $this->promotion->name,
            'days_left' => $this->daysLeft,
            'end_date' => $this->promotion->end_date->toIso8601String(),
            'products_count' => $this->promotion->products_count,
            'discount_value' => $this->promotion->discount_value,
            'discount_type' => $this->promotion->type,
            'message' => "Акция «{$this->promotion->name}» заканчивается через {$this->daysLeft} " . $this->pluralize($this->daysLeft),
        ];
    }

    /**
     * Pluralize days in Russian.
     */
    protected function pluralize(int $days): string
    {
        if ($days % 10 === 1 && $days % 100 !== 11) {
            return 'день';
        } elseif (in_array($days % 10, [2, 3, 4]) && !in_array($days % 100, [12, 13, 14])) {
            return 'дня';
        }

        return 'дней';
    }
}
