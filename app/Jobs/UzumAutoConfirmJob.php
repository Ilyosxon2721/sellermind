<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Services\Uzum\UzumAutoConfirmService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class UzumAutoConfirmJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Максимальное количество попыток выполнения
     */
    public int $tries = 3;

    /**
     * Задержка между повторными попытками (секунды)
     */
    public int $backoff = 30;

    /**
     * Автоматически подтвердить новые заказы Uzum Market для всех аккаунтов
     */
    public function handle(UzumAutoConfirmService $service): void
    {
        $accounts = MarketplaceAccount::where('marketplace', 'uzum')
            ->where('uzum_auto_confirm', true)
            ->where('is_active', true)
            ->get();

        if ($accounts->isEmpty()) {
            return;
        }

        foreach ($accounts as $account) {
            try {
                $service->processAccount($account);
            } catch (\Throwable $e) {
                Log::error("UzumAutoConfirmJob: ошибка для аккаунта #{$account->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
