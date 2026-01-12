<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionExpiringNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Subscription $subscription,
        public int $daysRemaining
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = ['database'];

        // Add Telegram if connected
        if ($notifiable->telegram_id && $notifiable->telegram_notifications_enabled) {
            $channels[] = 'telegram';
        }

        // Add email
        if ($notifiable->email) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $plan = $this->subscription->plan;
        $company = $this->subscription->company;

        return (new MailMessage)
            ->subject("Ваша подписка истекает через {$this->daysRemaining} дней")
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваша подписка «{$plan->name}» для компании «{$company->name}» истекает через {$this->daysRemaining} дней.")
            ->line("Дата окончания: {$this->subscription->ends_at->format('d.m.Y')}")
            ->action('Продлить подписку', url('/plans'))
            ->line('Продлите подписку, чтобы продолжить пользоваться всеми возможностями SellerMind AI.');
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram($notifiable): array
    {
        $plan = $this->subscription->plan;
        $company = $this->subscription->company;
        $daysText = $this->getDaysText($this->daysRemaining);

        $message = "🔔 *Подписка истекает*\n\n";
        $message .= "Компания: *{$company->name}*\n";
        $message .= "Тариф: *{$plan->name}*\n";
        $message .= "Осталось: *{$daysText}*\n";
        $message .= "Дата окончания: {$this->subscription->ends_at->format('d.m.Y')}\n\n";
        $message .= "Продлите подписку, чтобы продолжить использовать все возможности платформы.";

        return [
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [[
                    ['text' => '💳 Продлить подписку', 'url' => url('/plans')]
                ]]
            ]
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'subscription_expiring',
            'subscription_id' => $this->subscription->id,
            'company_id' => $this->subscription->company_id,
            'company_name' => $this->subscription->company->name,
            'plan_name' => $this->subscription->plan->name,
            'days_remaining' => $this->daysRemaining,
            'expires_at' => $this->subscription->ends_at->toIso8601String(),
            'message' => "Ваша подписка «{$this->subscription->plan->name}» истекает через {$this->daysRemaining} дней",
        ];
    }

    /**
     * Get days text in Russian
     */
    protected function getDaysText(int $days): string
    {
        $lastDigit = $days % 10;
        $lastTwoDigits = $days % 100;

        if ($lastTwoDigits >= 11 && $lastTwoDigits <= 19) {
            return "{$days} дней";
        }

        return match ($lastDigit) {
            1 => "{$days} день",
            2, 3, 4 => "{$days} дня",
            default => "{$days} дней",
        };
    }
}
