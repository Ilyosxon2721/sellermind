<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\OfflineSale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class OfflineSaleConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Создание нового экземпляра уведомления о подтверждённой офлайн-продаже.
     */
    public function __construct(
        public OfflineSale $sale
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
                $notifiable->notificationSettings->notify_offline_sale) {
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
        $formattedAmount = number_format((float) $this->sale->total_amount, 0, '.', ' ');
        $saleTypeLabel = $this->sale->getSaleTypeLabel();

        $message = "🛒 *Офлайн-продажа подтверждена*\n\n";
        $message .= "Номер: `{$this->sale->sale_number}`\n";
        $message .= "Тип: {$saleTypeLabel}\n";

        // Добавляем клиента, если указан
        if ($this->sale->customer_name) {
            $message .= "Клиент: {$this->sale->customer_name}\n";
        }

        $message .= "\n💰 Сумма: *{$formattedAmount}* {$this->sale->currency_code}";

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
        $formattedAmount = number_format((float) $this->sale->total_amount, 0, '.', ' ');

        return [
            'type' => 'offline_sale_confirmed',
            'offline_sale_id' => $this->sale->id,
            'sale_number' => $this->sale->sale_number,
            'total_amount' => (float) $this->sale->total_amount,
            'currency_code' => $this->sale->currency_code,
            'customer_name' => $this->sale->customer_name,
            'message' => "Офлайн-продажа {$this->sale->sale_number} подтверждена на {$formattedAmount} {$this->sale->currency_code}",
        ];
    }
}
