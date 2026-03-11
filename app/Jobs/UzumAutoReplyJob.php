<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Services\Uzum\UzumAutoReviewService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class UzumAutoReplyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Максимальное количество попыток выполнения
     */
    public int $tries = 2;

    /**
     * Задержка между повторными попытками (секунды)
     */
    public int $backoff = 60;

    /**
     * Автоматически ответить на неотвеченные отзывы Uzum Market для всех аккаунтов
     */
    public function handle(UzumAutoReviewService $service): void
    {
        $accounts = MarketplaceAccount::where('marketplace', 'uzum')
            ->where('uzum_auto_reply', true)
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return;
        }

        foreach ($accounts as $account) {
            try {
                $stats = $service->processAccount($account);
                Log::info("UzumAutoReplyJob: аккаунт #{$account->id}", $stats);
            } catch (\Throwable $e) {
                Log::error("UzumAutoReplyJob: ошибка для аккаунта #{$account->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
