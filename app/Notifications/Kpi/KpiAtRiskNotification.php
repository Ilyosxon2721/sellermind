<?php

declare(strict_types=1);

namespace App\Notifications\Kpi;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Уведомление руководителю: KPI сотрудника под угрозой (прогноз < 80%)
 */
final class KpiAtRiskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $employeeName,
        public readonly string $sphereName,
        public readonly float $currentAchievement,
        public readonly float $forecastAchievement,
        public readonly string $periodLabel,
    ) {}

    /**
     * Ключ дедупликации: одно предупреждение в день на сотрудника + сферу
     */
    public function deduplicationKey(): string
    {
        return "kpi_risk_{$this->employeeName}_{$this->sphereName}_" . date('Ymd');
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
        $message = "\u{26A0}\u{FE0F} *KPI под угрозой*\n\n";
        $message .= "Сотрудник: *{$this->employeeName}*\n";
        $message .= "Сфера: *{$this->sphereName}*\n";
        $message .= "Период: *{$this->periodLabel}*\n";
        $message .= "Текущий результат: *{$this->currentAchievement}%*\n";
        $message .= "Прогноз: *{$this->forecastAchievement}%*";

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
            'type' => 'kpi_at_risk',
            'employee_name' => $this->employeeName,
            'sphere_name' => $this->sphereName,
            'period' => $this->periodLabel,
            'current_achievement' => $this->currentAchievement,
            'forecast_achievement' => $this->forecastAchievement,
            'message' => "KPI под угрозой: {$this->employeeName} ({$this->sphereName}), прогноз {$this->forecastAchievement}%",
        ];
    }
}
