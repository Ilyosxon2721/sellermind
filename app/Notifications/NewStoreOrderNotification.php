<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\Store\StoreOrder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class NewStoreOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Создание нового экземпляра уведомления о заказе в магазине.
     */
    public function __construct(
        public StoreOrder $order,
        public string $storeName
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
                $notifiable->notificationSettings->notify_new_order) {
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
        $message = "🛒 *Новый заказ в магазине*\n\n";
        $message .= "*{$this->storeName}*\n";
        $message .= "Заказ: `{$this->order->order_number}`\n";
        $message .= "Клиент: {$this->order->customer_name}\n";
        $message .= "Телефон: {$this->order->customer_phone}\n\n";
        $message .= "💰 Сумма: *{$this->order->total}* UZS\n";
        $message .= "📦 Позиций: {$this->order->items_count}";

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
        return [
            'type' => 'new_store_order',
            'store_order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'store_name' => $this->storeName,
            'customer_name' => $this->order->customer_name,
            'total' => (float) $this->order->total,
            'message' => "Новый заказ {$this->order->order_number} в магазине {$this->storeName}",
        ];
    }
}
