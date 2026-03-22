<?php

declare(strict_types=1);

namespace App\Modules\UzumAnalytics\Jobs;

use App\Modules\UzumAnalytics\Services\TokenRefreshService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Job для обновления пула JWT токенов Uzum API
 *
 * Запускается каждые 5 минут через Laravel Scheduler
 */
final class RefreshTokenPoolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Количество попыток выполнения
     */
    public int $tries = 3;

    /**
     * Таймаут выполнения (секунды)
     */
    public int $timeout = 60;

    /**
     * Execute the job
     */
    public function handle(TokenRefreshService $tokenService): void
    {
        Log::info('[UzumAnalytics] RefreshTokenPoolJob: начало обновления пула токенов');

        try {
            // TokenRefreshService::refreshPool() выполняет:
            // - Деактивацию истёкших токенов
            // - Деактивацию переполненных токенов (request_count >= max)
            // - Создание новых токенов до минимального размера пула
            // - Обновление токенов, которые скоро истекут
            $tokenService->refreshPool();

            Log::info('[UzumAnalytics] RefreshTokenPoolJob: завершено');

        } catch (\Throwable $e) {
            Log::error('[UzumAnalytics] RefreshTokenPoolJob: ошибка обновления пула', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw чтобы Laravel повторил попытку
            throw $e;
        }
    }

    /**
     * Обработка неудачного выполнения
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('[UzumAnalytics] RefreshTokenPoolJob: провалено после всех попыток', [
            'error' => $exception->getMessage(),
        ]);

        // TODO: Отправить алерт в Telegram разработчику
        // if (config('uzum-crawler.monitoring.telegram_alerts')) {
        //     TelegramService::sendAlert('RefreshTokenPoolJob failed', $exception->getMessage());
        // }
    }
}
