<?php

// file: app/Jobs/Marketplace/SyncMarketplacePricesJob.php

namespace App\Jobs\Marketplace;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMarketplacePricesJob implements ShouldQueue
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
        $syncService->syncPrices($this->account, $this->productIds);
    }

    public function tags(): array
    {
        return [
            'marketplace-sync',
            'prices',
            'account:'.$this->account->id,
        ];
    }
}
