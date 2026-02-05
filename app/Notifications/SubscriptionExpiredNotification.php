<?php

namespace App\Notifications;

use App\Models\Subscription;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionExpiredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Subscription $subscription
    ) {}

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
            ->error()
            ->subject('Ваша подписка истекла')
            ->greeting("Здравствуйте, {$notifiable->name}!")
            ->line("Ваша подписка «{$plan->name}» для компании «{$company->name}» истекла.")
            ->line("Дата окончания: {$this->subscription->ends_at->format('d.m.Y')}")
            ->line('Доступ к функциям платформы ограничен до продления подписки.')
            ->action('Продлить подписку', url('/plans'))
            ->line('Продлите подписку, чтобы восстановить доступ ко всем возможностям SellerMind AI.');
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram($notifiable): array
    {
        $plan = $this->subscription->plan;
        $company = $this->subscription->company;

        $message = "⚠️ *Подписка истекла*\n\n";
        $message .= "Компания: *{$company->name}*\n";
        $message .= "Тариф: *{$plan->name}*\n";
        $message .= "Дата окончания: {$this->subscription->ends_at->format('d.m.Y')}\n\n";
        $message .= "❌ Доступ к функциям платформы ограничен.\n\n";
        $message .= 'Продлите подписку, чтобы восстановить полный доступ.';

        return [
            'text' => $message,
            'parse_mode' => 'Markdown',
            'reply_markup' => [
                'inline_keyboard' => [[
                    ['text' => '💳 Продлить подписку', 'url' => url('/plans')],
                ]],
            ],
        ];
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return [
            'type' => 'subscription_expired',
            'subscription_id' => $this->subscription->id,
            'company_id' => $this->subscription->company_id,
            'company_name' => $this->subscription->company->name,
            'plan_name' => $this->subscription->plan->name,
            'expired_at' => $this->subscription->ends_at->toIso8601String(),
            'message' => "Ваша подписка «{$this->subscription->plan->name}» истекла. Доступ ограничен.",
        ];
    }
}
