<?php

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public ProductVariant $variant,
        public int $currentStock
    ) {
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->notificationSettings) {
            // Check business hours if configured
            if ($notifiable->notificationSettings->notify_only_business_hours &&
                !$notifiable->notificationSettings->shouldNotifyNow()) {
                return ['database']; // Only database during off-hours
            }

            if ($notifiable->notificationSettings->channel_telegram &&
                $notifiable->notificationSettings->notify_low_stock) {
                $channels[] = TelegramChannel::class;
            }
            if ($notifiable->notificationSettings->channel_email &&
                $notifiable->notificationSettings->notify_low_stock) {
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
        $product = $this->variant->product;
        $threshold = $notifiable->notificationSettings?->low_stock_threshold ?? 10;

        $message = "⚠️ *Низкий остаток товара*\n\n";
        $message .= "*{$product->name}*\n";

        if ($this->variant->sku) {
            $message .= "SKU: `{$this->variant->sku}`\n";
        }

        if ($this->variant->options) {
            $message .= "Вариант: {$this->variant->options}\n";
        }

        $message .= "\n📦 Остаток: *{$this->currentStock}* шт.\n";
        $message .= "⚡ Порог: {$threshold} шт.\n\n";
        $message .= "Рекомендуется пополнить запас!";

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
        $product = $this->variant->product;

        return (new MailMessage)
            ->subject("Низкий остаток: {$product->name}")
            ->line("Товар: {$product->name}")
            ->line("SKU: {$this->variant->sku}")
            ->line("Текущий остаток: {$this->currentStock} шт.")
            ->action('Посмотреть товар', url("/products/{$product->id}"))
            ->line('Рекомендуется пополнить запас!');
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        $product = $this->variant->product;

        return [
            'type' => 'low_stock',
            'product_id' => $product->id,
            'variant_id' => $this->variant->id,
            'product_name' => $product->name,
            'sku' => $this->variant->sku,
            'current_stock' => $this->currentStock,
            'threshold' => $notifiable->notificationSettings?->low_stock_threshold ?? 10,
            'message' => "Низкий остаток: {$product->name} ({$this->currentStock} шт.)",
        ];
    }
}
