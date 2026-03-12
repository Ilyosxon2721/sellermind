<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Telegram\OrderMessageBuilder;
use App\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Отправка красивого Telegram-уведомления о заказе подписчику.
 */
final class SendTelegramOrderNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        private readonly Model $order,
        private readonly string $chatId,
    ) {
        $this->onQueue('telegram');
    }

    public function handle(OrderMessageBuilder $builder, TelegramService $telegram): void
    {
        try {
            $message = $builder->build($this->order);

            $telegram->sendMessage(
                $this->chatId,
                $message['text'],
                [
                    'parse_mode' => 'HTML',
                    'reply_markup' => json_encode($message['reply_markup']),
                ],
            );
        } catch (\Exception $e) {
            Log::error('Telegram order notification failed', [
                'chat_id' => $this->chatId,
                'order_class' => get_class($this->order),
                'order_id' => $this->order->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Уникальный ID чтобы не дублировать уведомления
     */
    public function uniqueId(): string
    {
        return get_class($this->order) . ':' . $this->order->id . ':' . $this->chatId;
    }
}
