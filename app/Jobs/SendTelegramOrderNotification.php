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
    ) {
        $this->onQueue('default');
    }

    public function handle(OrderMessageBuilder $builder, TelegramService $telegram): void
    {
        // Дедупликация на уровне отправки — не отправлять повторно 24 часа
        $dedupKey = 'tg_sent:' . get_class($this->order) . ':' . $this->order->id . ':' . $this->chatId;
        if (Cache::has($dedupKey)) {
            Log::debug('Telegram order notification skipped (already sent)', [
                'order_id' => $this->order->id,
                'chat_id' => $this->chatId,
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

            // Запомнить что уведомление отправлено — не дублировать 24 часа
            Cache::put($dedupKey, true, 86400);
        } catch (TelegramRateLimitException $e) {
            // При 429 — откладываем job, но максимум 5 раз
            $releaseCount = (int) Cache::get('tg_release:' . $this->uniqueId(), 0);
            if ($releaseCount >= 5) {
                Log::warning('Telegram rate limit: слишком много release, отбрасываем', [
                    'order_id' => $this->order->id,
                ]);
                return;
            }
            Cache::put('tg_release:' . $this->uniqueId(), $releaseCount + 1, 3600);

            Log::warning('Telegram rate limit, откладываем job', [
                'retry_after' => $e->retryAfter,
                'order_id' => $this->order->id,
                'release_count' => $releaseCount + 1,
            ]);
            $this->release($e->retryAfter);
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
