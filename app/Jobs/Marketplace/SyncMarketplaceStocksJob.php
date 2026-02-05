<?php

// file: app/Jobs/Marketplace/SyncMarketplaceStocksJob.php

namespace App\Jobs\Marketplace;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMarketplaceStocksJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public MarketplaceAccount $account,
        public ?array $productIds = null
    ) {}

    public function handle(MarketplaceSyncService $syncService): void
    {
        $syncService->syncStocks($this->account, $this->productIds);
    }

    public function tags(): array
    {
        return [
            'marketplace-sync',
            'stocks',
            'account:'.$this->account->id,
        ];
    }
}
