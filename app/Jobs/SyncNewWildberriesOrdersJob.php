<?php

namespace App\Jobs;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class SyncNewWildberriesOrdersJob implements ShouldQueue
{
    use Queueable;

    protected MarketplaceAccount $account;

    /**
     * Create a new job instance.
     */
    public function __construct(MarketplaceAccount $account)
    {
        $this->account = $account;
    }

    /**
     * Execute the job.
     */
    public function handle(WildberriesOrderService $orderService): void
    {
        // Проверяем, что это Wildberries аккаунт
        if ($this->account->marketplace !== 'wb') {
            Log::warning('SyncNewWildberriesOrdersJob: Not a WB account', [
                'account_id' => $this->account->id,
                'marketplace' => $this->account->marketplace,
            ]);
            return;
        }

        Log::info('SyncNewWildberriesOrdersJob: Starting', [
            'account_id' => $this->account->id,
            'account_name' => $this->account->name,
        ]);

        try {
            $result = $orderService->fetchNewOrders($this->account);

            Log::info('SyncNewWildberriesOrdersJob: Completed', [
                'account_id' => $this->account->id,
                'synced' => $result['synced'],
                'created' => $result['created'],
                'errors_count' => count($result['errors']),
            ]);

            // Если есть ошибки, логируем их
            if (!empty($result['errors'])) {
                Log::warning('SyncNewWildberriesOrdersJob: Errors occurred', [
                    'account_id' => $this->account->id,
                    'errors' => $result['errors'],
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SyncNewWildberriesOrdersJob: Failed', [
                'account_id' => $this->account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
