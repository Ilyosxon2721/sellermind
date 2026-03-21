<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Enums\EventType;
use App\Models\MarketplaceEvent;
use App\Models\OfflineSale;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TelegramNotificationService
{
    private string $botToken;

    private bool $enabled;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token', '');
        $this->enabled = (bool) config('services.telegram.notifications_enabled', false);
    }

    /**
     * Отправить уведомление о событии в Telegram
     */
    public function notify(MarketplaceEvent $event): void
    {
        if (! $this->enabled || empty($this->botToken)) {
            return;
        }

        $account = $event->store;
        if (! $account) {
            return;
        }

        // Берём chat_id из пользователя — сначала напрямую, потом через компанию
        $user = $account->user
            ?? $account->company?->users()
                ->where('telegram_notifications_enabled', true)
                ->whereNotNull('telegram_id')
                ->first();

        if (! $user || ! $user->telegram_id || ! $user->telegram_notifications_enabled) {
            return;
        }

        $chatId = (string) $user->telegram_id;

        $message = $this->buildMessage($event);
        if (! $message) {
            return;
        }

        $this->send($chatId, $message);
    }

    /**
     * Уведомление о подтверждённой ручной продаже
     */
    public function notifyOfflineSale(OfflineSale $sale): void
    {
        if (! $this->enabled || empty($this->botToken)) {
            return;
        }

        $user = User::where('company_id', $sale->company_id)
            ->where('telegram_notifications_enabled', true)
            ->whereNotNull('telegram_id')
            ->first();

        if (! $user) {
            return;
        }

        $typeLabels = ['retail' => 'Розница', 'wholesale' => 'Опт', 'direct' => 'Прямая'];
        $type = $typeLabels[$sale->sale_type] ?? $sale->sale_type;
        $amount = number_format((float) $sale->total_amount, 0, '.', ' ');
        $currency = $sale->currency_code ?? 'UZS';

        $message = "🛒 *РУЧНАЯ ПРОДАЖА*\n\n"
            ."🔢 Номер: {$sale->sale_number}\n"
            ."📋 Тип: {$type}\n"
            ."💰 Сумма: {$amount} {$currency}\n";

        // Контрагент
        $counterpartyName = $sale->counterparty?->name ?? $sale->customer_name ?? null;
        if ($counterpartyName) {
            $message .= "🤝 Контрагент: {$counterpartyName}\n";
        }

        // Товары
        $items = $sale->relationLoaded('items') ? $sale->items : $sale->items()->get();
        if ($items->isNotEmpty()) {
            $message .= "🛍 Товары:\n";
            foreach ($items->take(5) as $item) {
                $name = $item->product_name ?? $item->sku_code ?? '—';
                $qty = (int) $item->quantity;
                $message .= "  • {$name} × {$qty}\n";
            }
            if ($items->count() > 5) {
                $message .= '  ...и ещё '.($items->count() - 5)." шт.\n";
            }
        }

        $message .= '🕐 Дата: '.$sale->sale_date->format('d.m.Y')."\n";

        $this->send((string) $user->telegram_id, $message);
    }

    private function buildMessage(MarketplaceEvent $event): ?string
    {
        return match ($event->event_type) {
            EventType::ORDER_CREATED => $this->orderCreatedMessage($event),
            EventType::ORDER_CANCELLED => $this->orderCancelledMessage($event),
            EventType::ORDER_STATUS_CHANGED => $this->orderStatusMessage($event),
            EventType::RETURN_CREATED => $this->returnMessage($event),
            EventType::CHAT_MESSAGE_CREATED => $this->chatMessage($event),
            default => null,
        };
    }

    private function orderCreatedMessage(MarketplaceEvent $event): string
    {
        $payload = $event->payload ?? [];
        $text = "✅ *НОВЫЙ ЗАКАЗ*\n\n"
            ."📦 Маркетплейс: {$event->marketplace->label()}\n"
            ."🔢 Заказ: \#{$event->entity_id}\n";

        if (! empty($payload['total_price'])) {
            $amount = number_format((float) $payload['total_price'], 0, '.', ' ');
            $currency = $payload['currency'] ?? 'RUB';
            $text .= "💰 Сумма: {$amount} {$currency}\n";
        }

        if (! empty($payload['items']) && is_array($payload['items'])) {
            $text .= "🛍 Товары:\n";
            foreach (array_slice($payload['items'], 0, 5) as $item) {
                $name = $item['name'] ?? $item['subject'] ?? $item['title'] ?? '—';
                $qty = $item['quantity'] ?? $item['qty'] ?? 1;
                $text .= "  • {$name} × {$qty}\n";
            }
            if (count($payload['items']) > 5) {
                $text .= '  ...и ещё '.(count($payload['items']) - 5)." шт.\n";
            }
        }

        $text .= '🕐 Время: '.$event->created_at->format('d.m.Y H:i')."\n";

        return $text;
    }

    private function orderCancelledMessage(MarketplaceEvent $event): string
    {
        $payload = $event->payload ?? [];
        $text = "❌ *ЗАКАЗ ОТМЕНЁН*\n\n"
            ."📦 Маркетплейс: {$event->marketplace->label()}\n"
            ."🔢 Заказ: \#{$event->entity_id}\n";

        if (! empty($payload['cancel_reason'])) {
            $text .= "📝 Причина: {$payload['cancel_reason']}\n";
        }

        $text .= '🕐 Время: '.$event->created_at->format('d.m.Y H:i')."\n";

        return $text;
    }

    private function orderStatusMessage(MarketplaceEvent $event): string
    {
        $payload = $event->payload ?? [];
        $text = "⚠️ *СТАТУС ЗАКАЗА ИЗМЕНЁН*\n\n"
            ."📦 Маркетплейс: {$event->marketplace->label()}\n"
            ."🔢 Заказ: \#{$event->entity_id}\n";

        if (! empty($payload['status'])) {
            $text .= "📋 Статус: {$payload['status']}\n";
        }

        $text .= '🕐 Время: '.$event->created_at->format('d.m.Y H:i')."\n";

        return $text;
    }

    private function returnMessage(MarketplaceEvent $event): string
    {
        $payload = $event->payload ?? [];
        $text = "🔄 *ВОЗВРАТ*\n\n"
            ."📦 Маркетплейс: {$event->marketplace->label()}\n"
            ."🔢 Заказ: \#{$event->entity_id}\n";

        if (! empty($payload['total_price'])) {
            $amount = number_format((float) $payload['total_price'], 0, '.', ' ');
            $text .= "💰 Сумма: {$amount}\n";
        }

        $text .= '🕐 Время: '.$event->created_at->format('d.m.Y H:i')."\n";

        return $text;
    }

    private function chatMessage(MarketplaceEvent $event): string
    {
        $payload = $event->payload ?? [];
        $text = "💬 *НОВОЕ СООБЩЕНИЕ*\n\n"
            ."📦 Маркетплейс: {$event->marketplace->label()}\n";

        if (! empty($payload['text'])) {
            $preview = mb_substr($payload['text'], 0, 100);
            $text .= "✉️ {$preview}\n";
        }

        $text .= '🕐 Время: '.$event->created_at->format('d.m.Y H:i')."\n";

        return $text;
    }

    private function send(string $chatId, string $message): void
    {
        try {
            Http::timeout(5)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed', ['error' => $e->getMessage()]);
        }
    }
}
