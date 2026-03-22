<?php

// file: app/Modules/UzumAnalytics/Services/RateLimiter.php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Services;

use Illuminate\Support\Facades\Redis;

/**
 * Rate limiter для запросов к публичному API Uzum.
 *
 * Использует Redis INCR + EXPIRE для подсчёта запросов в минуту.
 * Добавляет случайный jitter к задержке чтобы избежать синхронных волн.
 */
final class RateLimiter
{
    private readonly array $config;

    private readonly string $prefix;

    public function __construct()
    {
        $this->config = config('uzum-crawler');
        $this->prefix = $this->config['redis_prefix'] . 'rate:';
    }

    /**
     * Задержать выполнение чтобы не превысить лимит для типа запроса.
     *
     * @param  string  $type  product | category | shop | token
     */
    public function throttle(string $type): void
    {
        $key      = $this->prefix . $type;
        $limit    = $this->config['rate_limits'][$type] ?? 10;
        $delay    = $this->config['delays'][$type] ?? 6;
        $jitter   = $this->config['jitter'] ?? 0.5;

        // Считаем запросы за последнюю минуту
        $count = (int) Redis::get($key);

        if ($count === 0) {
            // Первый запрос в окне — устанавливаем счётчик с TTL 60 секунд
            Redis::setex($key, 60, 1);
        } elseif ($count >= $limit) {
            // Лимит исчерпан — спим до следующего окна
            $ttl = (int) Redis::ttl($key);
            if ($ttl > 0) {
                sleep($ttl);
            }
            Redis::setex($key, 60, 1);
        } else {
            Redis::incr($key);
        }

        // Базовая задержка + случайный jitter
        $sleepSeconds = $delay * (1 + (mt_rand(0, (int) ($jitter * 100)) / 100));
        usleep((int) ($sleepSeconds * 1_000_000));
    }

    /**
     * Проверить — доступен ли тип запроса без ожидания
     */
    public function canProceed(string $type): bool
    {
        $key   = $this->prefix . $type;
        $limit = $this->config['rate_limits'][$type] ?? 10;
        $count = (int) Redis::get($key);

        return $count < $limit;
    }
}
