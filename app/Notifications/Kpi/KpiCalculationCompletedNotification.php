<?php

declare(strict_types=1);

namespace App\Notifications\Kpi;

use App\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о завершении массового расчёта KPI за период
 */
final class KpiCalculationCompletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $companyName,
        public readonly int $year,
        public readonly int $month,
        public readonly int $plansCount,
        public readonly float $avgAchievement,
        public readonly int $errorCount = 0,
    ) {}

    /**
     * Ключ дедупликации: один расчёт за период — одно уведомление в час
     */
    public function deduplicationKey(): string
    {
        return "kpi_calc_{$this->year}_{$this->month}_" . date('YmdH');
    }

    public function deduplicationTtl(): int
    {
        return 3600;
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
        $periodLabel = $this->getPeriodLabel();
        $icon = $this->errorCount > 0 ? "\u{26A0}\u{FE0F}" : "\u{1F4CA}";

        $message = "{$icon} *KPI расчёт завершён*\n\n";
        $message .= "Период: *{$periodLabel}*\n";
        $message .= "Компания: *{$this->companyName}*\n";
        $message .= "Рассчитано планов: *{$this->plansCount}*\n";
        $message .= "Средний результат: *{$this->avgAchievement}%*\n";

        if ($this->errorCount > 0) {
            $message .= "\n\u{26A0}\u{FE0F} Ошибок при расчёте: {$this->errorCount}";
        }

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
            'type' => 'kpi_calculation_completed',
            'company_name' => $this->companyName,
            'period' => "{$this->year}-{$this->month}",
            'plans_count' => $this->plansCount,
            'avg_achievement' => $this->avgAchievement,
            'error_count' => $this->errorCount,
            'message' => "KPI расчёт завершён: {$this->plansCount} планов, средний результат {$this->avgAchievement}%",
        ];
    }

    /**
     * Получить читаемое название периода
     */
    private function getPeriodLabel(): string
    {
        $months = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март',
            4 => 'Апрель', 5 => 'Май', 6 => 'Июнь',
            7 => 'Июль', 8 => 'Август', 9 => 'Сентябрь',
            10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
        ];

        return ($months[$this->month] ?? '') . ' ' . $this->year;
    }
}
