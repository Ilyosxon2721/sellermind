<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\TelegramSubscription;
use App\Services\Telegram\OrderMessageBuilder;
use App\Telegram\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Отправить дневной отчёт подписчикам Telegram.
 * Запускается каждую минуту — отправляет тем, у кого summary_time совпадает с текущим временем.
 */
final class SendDailySummary extends Command
{
    protected $signature = 'telegram:daily-summary';

    protected $description = 'Отправить дневной отчёт подписчикам Telegram';

    public function handle(OrderMessageBuilder $builder, TelegramService $telegram): int
    {
        $now = now()->format('H:i');

        $subscriptions = TelegramSubscription::query()
            ->active()
            ->dailySummary()
            ->where('summary_time', $now)
            ->with('user')
            ->get();

        if ($subscriptions->isEmpty()) {
            $this->line("Нет подписок на {$now}");

            return self::SUCCESS;
        }

        $this->info("Отправка дневных отчётов: {$subscriptions->count()} подписок");

        $sent = 0;
        $failed = 0;

        foreach ($subscriptions as $subscription) {
            try {
                $message = $builder->buildDailySummary($subscription->user_id);

                $telegram->sendMessage(
                    $subscription->chat_id,
                    $message['text'],
                    [
                        'parse_mode' => 'HTML',
                        'reply_markup' => json_encode($message['reply_markup']),
                    ],
                );

                $sent++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('Daily summary send failed', [
                    'subscription_id' => $subscription->id,
                    'user_id' => $subscription->user_id,
                    'chat_id' => $subscription->chat_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->info("Готово: отправлено {$sent}, ошибок {$failed}");

        return self::SUCCESS;
    }
}
