<?php

namespace App\Jobs\Concerns;

use Illuminate\Support\Facades\Log;

/**
 * Trait для обработки rate limiting (429) от маркетплейсов
 *
 * Реализует:
 * - Экспоненциальный backoff при 429 ошибках
 * - Логирование retry попыток
 * - Правильное определение времени ожидания из Retry-After header
 */
trait HandlesMarketplaceRateLimiting
{
    /**
     * Количество секунд ожидания перед retry
     * Экспоненциальный backoff: 30, 60, 120, 240, 480 секунд
     */
    public function backoff(): array
    {
        return [30, 60, 120, 240, 480];
    }

    /**
     * Определить, должна ли job быть повторена при исключении
     */
    public function shouldRetry(\Throwable $exception): bool
    {
        // Retry при rate limiting (429)
        if ($this->isRateLimitException($exception)) {
            Log::warning('Rate limit hit, will retry', [
                'job' => static::class,
                'message' => $exception->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            return true;
        }

        // Retry при временных сетевых ошибках
        if ($this->isTemporaryNetworkError($exception)) {
            Log::warning('Temporary network error, will retry', [
                'job' => static::class,
                'message' => $exception->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            return true;
        }

        return false;
    }

    /**
     * Проверить, является ли исключение ошибкой rate limit (429)
     */
    protected function isRateLimitException(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        // Проверяем HTTP код 429
        if (str_contains($message, '429') || str_contains($message, 'too many requests')) {
            return true;
        }

        // Проверяем rate limit сообщения от разных маркетплейсов
        $rateLimitPhrases = [
            'rate limit',
            'rate-limit',
            'ratelimit',
            'request limit',
            'слишком много запросов',
            'превышен лимит',
            'too many',
            'throttl',
            'quota exceeded',
        ];

        foreach ($rateLimitPhrases as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        // Проверяем GuzzleHttp исключения
        if ($exception instanceof \GuzzleHttp\Exception\ClientException) {
            return $exception->getResponse()?->getStatusCode() === 429;
        }

        return false;
    }

    /**
     * Проверить, является ли исключение временной сетевой ошибкой
     */
    protected function isTemporaryNetworkError(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        $temporaryErrorPhrases = [
            'connection timed out',
            'connection refused',
            'could not resolve host',
            'ssl connection',
            'network is unreachable',
            'temporary failure',
            '502 bad gateway',
            '503 service unavailable',
            '504 gateway timeout',
            'curl error',
        ];

        foreach ($temporaryErrorPhrases as $phrase) {
            if (str_contains($message, $phrase)) {
                return true;
            }
        }

        // GuzzleHttp connection exceptions
        if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
            return true;
        }

        // Server errors (5xx)
        if ($exception instanceof \GuzzleHttp\Exception\ServerException) {
            return true;
        }

        return false;
    }

    /**
     * Получить время ожидания из Retry-After header (если доступен)
     */
    protected function getRetryAfterSeconds(\Throwable $exception): ?int
    {
        if ($exception instanceof \GuzzleHttp\Exception\ClientException) {
            $response = $exception->getResponse();
            if ($response && $response->hasHeader('Retry-After')) {
                $retryAfter = $response->getHeader('Retry-After')[0];

                // Может быть числом секунд или датой
                if (is_numeric($retryAfter)) {
                    return (int) $retryAfter;
                }

                // Попробуем распарсить как дату
                try {
                    $retryDate = new \DateTime($retryAfter);
                    return max(0, $retryDate->getTimestamp() - time());
                } catch (\Exception $e) {
                    // Игнорируем ошибки парсинга
                }
            }
        }

        return null;
    }

    /**
     * Middleware для release с правильным временем при 429
     */
    public function retryUntil(): \DateTime
    {
        // Job может retry в течение 30 минут
        return now()->addMinutes(30);
    }
}
