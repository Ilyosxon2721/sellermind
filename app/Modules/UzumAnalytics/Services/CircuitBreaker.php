<?php

// file: app/Modules/UzumAnalytics/Services/CircuitBreaker.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Circuit Breaker — защита от перегрузки API Uzum.
 *
 * Три состояния:
 *  - CLOSED: краулер работает нормально
 *  - OPEN: пауза (5 ошибок за 10 мин → пауза на 1 час)
 *  - Автоматически переходит в CLOSED через pause_minutes
 */
class CircuitBreaker
{
    private readonly array $config;

    private readonly string $cacheKey;

    public function __construct()
    {
        $this->config   = config('uzum-crawler.circuit_breaker');
        $this->cacheKey = $this->config['redis_key'];
    }

    /**
     * Цепь открыта — краулер должен остановиться
     */
    public function isOpen(): bool
    {
        $state = $this->getState();

        if ($state['status'] !== 'open') {
            return false;
        }

        // Проверяем, не прошло ли время паузы
        $pauseUntil = $state['pause_until'] ?? 0;
        if (time() >= $pauseUntil) {
            $this->reset();

            return false;
        }

        return true;
    }

    /**
     * Записать ошибку (429/503). Открыть цепь при достижении порога.
     */
    public function recordFailure(): void
    {
        $state   = $this->getState();
        $now     = time();
        $window  = $this->config['window_minutes'] * 60;

        // Убрать ошибки вне окна наблюдения
        $failures = array_filter(
            $state['failures'] ?? [],
            fn (int $t) => ($now - $t) <= $window
        );

        $failures[] = $now;
        $state['failures']             = array_values($failures);
        $state['consecutive_failures'] = ($state['consecutive_failures'] ?? 0) + 1;

        // Алерт при достижении порога последовательных ошибок
        if ($state['consecutive_failures'] >= $this->config['alert_threshold']) {
            $this->sendAlert($state['consecutive_failures']);
        }

        // Открыть цепь при достижении threshold ошибок в окне
        if (count($state['failures']) >= $this->config['failure_threshold']) {
            $pauseUntil   = $now + ($this->config['pause_minutes'] * 60);
            $state['status']      = 'open';
            $state['pause_until'] = $pauseUntil;

            Log::error('UzumCrawler CircuitBreaker: цепь ОТКРЫТА', [
                'failures_in_window' => count($state['failures']),
                'pause_until'        => date('Y-m-d H:i:s', $pauseUntil),
            ]);

            $this->sendAlert($state['consecutive_failures'], open: true);
        }

        $this->saveState($state);
    }

    /**
     * Записать успешный запрос — сбросить счётчик последовательных ошибок
     */
    public function recordSuccess(): void
    {
        $state = $this->getState();
        $state['consecutive_failures'] = 0;
        $this->saveState($state);
    }

    /**
     * Текущий статус для healthcheck
     */
    public function getStatus(): array
    {
        $state = $this->getState();

        return [
            'status'               => $state['status'] ?? 'closed',
            'consecutive_failures' => $state['consecutive_failures'] ?? 0,
            'failures_in_window'   => count($state['failures'] ?? []),
            'pause_until'          => isset($state['pause_until']) && $state['pause_until']
                ? date('Y-m-d H:i:s', $state['pause_until'])
                : null,
        ];
    }

    /**
     * Принудительно закрыть цепь (сброс)
     */
    public function reset(): void
    {
        $this->saveState([
            'status'               => 'closed',
            'failures'             => [],
            'consecutive_failures' => 0,
            'pause_until'          => null,
        ]);

        Log::info('UzumCrawler CircuitBreaker: цепь ЗАКРЫТА (сброс)');
    }

    private function getState(): array
    {
        return Cache::get($this->cacheKey, [
            'status'               => 'closed',
            'failures'             => [],
            'consecutive_failures' => 0,
            'pause_until'          => null,
        ]);
    }

    private function saveState(array $state): void
    {
        Cache::put($this->cacheKey, $state, 86400);
    }

    /**
     * Telegram алерт разработчику
     */
    private function sendAlert(int $consecutiveErrors, bool $open = false): void
    {
        $chatId = config('uzum-crawler.telegram_chat_id');
        if (! $chatId) {
            return;
        }

        $message = $open
            ? "🚨 UzumCrawler: Circuit Breaker ОТКРЫТ\n{$consecutiveErrors} последовательных ошибок. Краулер остановлен на {$this->config['pause_minutes']} минут."
            : "⚠️ UzumCrawler: {$consecutiveErrors} последовательных ошибок 429/503";

        try {
            $token = config('telegram.bot_token');
            if ($token) {
                \Illuminate\Support\Facades\Http::post(
                    "https://api.telegram.org/bot{$token}/sendMessage",
                    ['chat_id' => $chatId, 'text' => $message]
                );
            }
        } catch (\Throwable) {
            // Не блокировать краулер из-за ошибки алерта
        }
    }
}
