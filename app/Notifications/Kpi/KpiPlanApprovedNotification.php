<?php

declare(strict_types=1);

namespace App\Notifications\Kpi;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Уведомление сотруднику об утверждении KPI-плана
 */
final class KpiPlanApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $periodLabel,
        public readonly float $achievementPercent,
        public readonly float $bonusAmount,
        public readonly string $approverName,
        public readonly string $sphereName,
    ) {}

    /**
     * Ключ дедупликации: одно утверждение = одно уведомление
     */
    public function deduplicationKey(): string
    {
        return "kpi_approved_{$this->periodLabel}_{$this->sphereName}";
    }

    public function deduplicationTtl(): int
    {
        return 86400;
    }

    /**
     * Каналы доставки уведомления
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notificationSettings) {
            if ($notifiable->notificationSettings->channel_telegram) {
                $channels[] = TelegramChannel::class;
            }
        } else {
            if ($notifiable->telegram_id && $notifiable->telegram_notifications_enabled) {
                $channels[] = TelegramChannel::class;
            }
        }

        return $channels;
    }

    /**
     * Telegram-представление уведомления
     */
    public function toTelegram(object $notifiable): array
    {
        $bonusFormatted = number_format($this->bonusAmount, 0, '.', ' ');

        $message = "\u{2705} *Ваш KPI план утверждён*\n\n";
        $message .= "Период: *{$this->periodLabel}*\n";
        $message .= "Сфера: *{$this->sphereName}*\n";
        $message .= "Результат: *{$this->achievementPercent}%*\n";
        $message .= "Бонус: *{$bonusFormatted} сум*\n";
        $message .= "Утвердил: *{$this->approverName}*";

        return [
            'text' => $message,
            'options' => [
                'parse_mode' => 'Markdown',
            ],
        ];
    }

    /**
     * Массив-представление уведомления для БД
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kpi_plan_approved',
            'period' => $this->periodLabel,
            'sphere' => $this->sphereName,
            'achievement_percent' => $this->achievementPercent,
            'bonus_amount' => $this->bonusAmount,
            'approver_name' => $this->approverName,
            'message' => "KPI план утверждён: {$this->sphereName}, результат {$this->achievementPercent}%, бонус {$this->bonusAmount} сум",
        ];
    }
}
