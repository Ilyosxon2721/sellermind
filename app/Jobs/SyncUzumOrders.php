<?php

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncUzumOrders implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 300;
    public int $tries = 1;

    public function __construct(protected int $accountId)
    {
    }

    public function handle(MarketplaceSyncService $syncService): void
    {
        $account = MarketplaceAccount::find($this->accountId);

        if (!$account || !$account->isUzum()) {
            Log::warning('SyncUzumOrders skipped: account not found or not Uzum', [
                'account_id' => $this->accountId,
            ]);
            return;
        }

        if (!$account->is_active) {
            Log::info('SyncUzumOrders skipped for inactive account', ['account_id' => $account->id]);
            return;
        }

        Log::info('SyncUzumOrders started', ['account_id' => $account->id]);

        try {
            // Тянем последние 30 дней (по умолчанию внутри syncOrders)
            $syncService->syncOrders($account);
            Log::info('SyncUzumOrders completed', ['account_id' => $account->id]);
        } catch (\Throwable $e) {
            Log::error('SyncUzumOrders failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
