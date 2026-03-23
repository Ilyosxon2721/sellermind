<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Channels\TelegramChannel;
use App\Models\MarketplaceProduct;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Уведомление о критически малом прогнозе FBS-остатков маркетплейса.
 * Отправляется когда остаток хватит менее чем на X дней при текущем темпе продаж.
 */
class MarketplaceLowFbsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly MarketplaceProduct $product,
        public readonly int $daysLeft,
        public readonly int $stockFbs,
    ) {}

    /**
     * Дедупликация: один товар — не чаще раза в сутки
     */
    public function deduplicationKey(): string
    {
        return 'mp_low_fbs_' . $this->product->id;
    }

    public function deduplicationTtl(): int
    {
        return 24 * 3600;
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $settings = $notifiable->notificationSettings ?? null;

        if ($settings) {
            if ($settings->notify_only_business_hours && ! $settings->shouldNotifyNow()) {
                return ['database'];
            }
            if ($settings->channel_telegram) {
                $channels[] = TelegramChannel::class;
            }
            if ($settings->channel_email) {
                $channels[] = 'mail';
            }
        } elseif ($notifiable->telegram_id && $notifiable->telegram_notifications_enabled) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toTelegram(object $notifiable): array
    {
        $account = $this->product->account;
        $marketplace = strtoupper($account->marketplace ?? 'МП');
        $title = $this->product->title ?? 'Без названия';

        $emoji = $this->daysLeft <= 3 ? '🔴' : '🟡';
        $text = "{$emoji} *Критический FBS-остаток на {$marketplace}*\n\n";
        $text .= "*{$title}*\n";

        if ($this->product->external_product_id) {
            $text .= "ID: `{$this->product->external_product_id}`\n";
        }

        $text .= "\n📦 Остаток FBS: *{$this->stockFbs}* шт.\n";
        $text .= "⏳ Прогноз: *{$this->daysLeft}* " . $this->dayWord($this->daysLeft) . "\n\n";
        $text .= 'Пополните остатки на FBS!';

        return [
            'text' => $text,
            'options' => ['parse_mode' => 'Markdown'],
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $account = $this->product->account;
        $marketplace = strtoupper($account->marketplace ?? 'МП');
        $title = $this->product->title ?? 'Без названия';

        return (new MailMessage)
            ->subject("Критический FBS-остаток на {$marketplace}: {$title}")
            ->line("Маркетплейс: {$marketplace}")
            ->line("Товар: {$title}")
            ->line("Остаток FBS: {$this->stockFbs} шт.")
            ->line("Прогноз: {$this->daysLeft} " . $this->dayWord($this->daysLeft))
            ->action('Перейти к товарам', url("/marketplace/{$account->id}/products"))
            ->line('Срочно пополните остатки на FBS!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'marketplace_low_fbs',
            'marketplace_product_id' => $this->product->id,
            'marketplace_account_id' => $this->product->marketplace_account_id,
            'title' => $this->product->title,
            'stock_fbs' => $this->stockFbs,
            'days_left' => $this->daysLeft,
            'message' => "Остаток FBS «{$this->product->title}» хватит на {$this->daysLeft} дн.",
        ];
    }

    private function dayWord(int $days): string
    {
        if ($days % 100 >= 11 && $days % 100 <= 14) {
            return 'дней';
        }
        return match ($days % 10) {
            1 => 'день',
            2, 3, 4 => 'дня',
            default => 'дней',
        };
    }
}
