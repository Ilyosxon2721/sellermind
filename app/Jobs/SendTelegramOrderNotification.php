<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Telegram\OrderMessageBuilder;
use App\Telegram\TelegramRateLimitException;
use App\Telegram\TelegramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Отправка красивого Telegram-уведомления о заказе подписчику.
 * Дедупликация: ShouldBeUnique предотвращает дубли в очереди,
 * Redis-кэш предотвращает повторную отправку того же статуса в течение 30 минут.
 */
final class SendTelegramOrderNotification implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $maxExceptions = 3;

    public array $backoff = [30, 60, 120];

    /**
     * Уникальность job в очереди — 1 час
     */
    public int $uniqueFor = 3600;

    public function __construct(
        private readonly Model $order,
        private readonly string $chatId,
        private readonly string $status = '',
    ) {
        $this->onQueue('default');
    }

    public function handle(OrderMessageBuilder $builder, TelegramService $telegram): void
    {
        // Redis-дедупликация: не отправлять тот же статус повторно в течение 30 минут
        $dedupKey = $this->getDeduplicationKey();
        if (Cache::has($dedupKey)) {
            Log::debug('Telegram notification deduplicated', [
                'order_id' => $this->order->id,
                'chat_id' => $this->chatId,
                'status' => $this->status,
            ]);

            return;
        }

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

            // Пометить как отправленное на 30 минут
            Cache::put($dedupKey, true, now()->addMinutes(30));
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
     * Уникальный ID для ShouldBeUnique — включает статус заказа
     */
    public function uniqueId(): string
    {
        return get_class($this->order) . ':' . $this->order->id . ':' . $this->chatId . ':' . $this->status;
    }

    /**
     * Ключ для Redis-дедупликации отправленных уведомлений
     */
    private function getDeduplicationKey(): string
    {
        return 'tg_notif:' . get_class($this->order) . ':' . $this->order->id . ':' . $this->chatId . ':' . $this->status;
    }
}
