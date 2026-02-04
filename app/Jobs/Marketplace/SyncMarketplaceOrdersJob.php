<?php

// file: app/Jobs/Marketplace/SyncMarketplaceOrdersJob.php

namespace App\Jobs\Marketplace;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncMarketplaceOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public MarketplaceAccount $account,
        public ?DateTimeInterface $from = null,
        public ?DateTimeInterface $to = null,
        public ?array $statuses = null
    ) {}

    public function handle(MarketplaceSyncService $syncService): void
    {
        // Берём свежие данные аккаунта, чтобы учитывать обновлённые токены/shop_id/флаги активности
        $account = MarketplaceAccount::find($this->account->id);
        if (! $account || ! $account->is_active) {
            return;
        }

        $syncService->syncOrders($account, $this->from, $this->to, $this->statuses);
    }

    public function tags(): array
    {
        return [
            'marketplace-sync',
            'orders',
            'account:'.$this->account->id,
        ];
    }
}
