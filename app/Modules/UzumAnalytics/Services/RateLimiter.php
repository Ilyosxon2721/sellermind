<?php

// file: app/Modules/UzumAnalytics/Services/RateLimiter.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Rate limiter для запросов к публичному API Uzum.
 *
 * Использует Redis INCR + EXPIRE для подсчёта запросов в минуту.
 * Добавляет случайный jitter к задержке чтобы избежать синхронных волн.
 */
class RateLimiter
{
    private readonly array $config;

    private readonly string $prefix;

    public function __construct()
    {
        $this->config = config('uzum-crawler');
        $this->prefix = $this->config['redis_prefix'].'rate:';
    }

    /**
     * Задержать выполнение чтобы не превысить лимит для типа запроса.
     *
     * @param  string  $type  product | category | shop | token
     * @param  bool  $withDelay  true для фоновых задач (sleep между запросами), false для web-запросов
     */
    public function throttle(string $type, bool $withDelay = true): void
    {
        $key = $this->prefix.$type;
        $limit = $this->config['rate_limits'][$type] ?? 10;
        $delay = $this->config['delays'][$type] ?? 6;
        $jitter = $this->config['jitter'] ?? 0.5;

        // Считаем запросы за последнюю минуту
        $count = (int) Cache::get($key, 0);

        if ($count === 0) {
            Cache::put($key, 1, 60);
        } elseif ($count >= $limit) {
            if ($withDelay) {
                // Лимит исчерпан — ждём новое окно (только в фоне)
                sleep(60);
            }
            Cache::put($key, 1, 60);
        } else {
            Cache::put($key, $count + 1, 60);
        }

        // Базовая задержка + случайный jitter (только для фоновых задач)
        if ($withDelay) {
            $sleepSeconds = $delay * (1 + (mt_rand(0, (int) ($jitter * 100)) / 100));
            usleep((int) ($sleepSeconds * 1_000_000));
        }
    }

    /**
     * Проверить — доступен ли тип запроса без ожидания
     */
    public function canProceed(string $type): bool
    {
        $key = $this->prefix.$type;
        $limit = $this->config['rate_limits'][$type] ?? 10;
        $count = (int) Cache::get($key, 0);

        return $count < $limit;
    }
}
