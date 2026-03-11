<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\MarketplaceEventData;
use App\Enums\EntityType;
use App\Enums\EventType;
use App\Enums\MarketplaceType;
use App\Models\MarketplaceAccount;
use App\Models\MarketplacePollingState;
use App\Services\Notifications\DeduplicationService;
use App\Services\Notifications\EventStoreService;
use App\Services\Notifications\PollingLockManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class PollWildberriesOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 1;

    public function handle(
        PollingLockManager $lockManager,
        DeduplicationService $dedup,
        EventStoreService $eventStore,
    ): void {
        $states = MarketplacePollingState::where('marketplace', MarketplaceType::WILDBERRIES->value)
            ->where('endpoint', 'orders_new')
            ->where('is_active', true)
            ->get();

        foreach ($states as $state) {
            if (! $lockManager->acquire($state)) {
                continue;
            }

            try {
                $account = MarketplaceAccount::find($state->store_id);
                if (! $account || empty($account->api_key)) {
                    continue;
                }

                $apiKey = decrypt($account->api_key);
                $this->pollOrders($state, $account, $apiKey, $dedup, $eventStore);

            } catch (\Throwable $e) {
                $state->increment('consecutive_errors');
                Log::error("WB polling error for store {$state->store_id}: {$e->getMessage()}");

                if ($state->fresh()->consecutive_errors >= 10) {
                    $state->update(['is_active' => false]);
                    Log::critical("WB polling deactivated for store {$state->store_id}");
                }
            } finally {
                $lockManager->release($state);
            }
        }
    }

    private function pollOrders(
        MarketplacePollingState $state,
        MarketplaceAccount $account,
        string $apiKey,
        DeduplicationService $dedup,
        EventStoreService $eventStore,
    ): void {
        $response = Http::timeout(15)
            ->withHeaders(['Authorization' => $apiKey])
            ->get('https://marketplace-api.wildberries.ru/api/v3/orders/new');

        if (! $response->successful()) {
            throw new \RuntimeException("WB API error: {$response->status()}");
        }

        $orders = $response->json('orders', []);

        if (empty($orders)) {
            $state->update(['last_poll_at' => now(), 'consecutive_errors' => 0]);

            return;
        }

        foreach ($orders as $order) {
            $orderId    = (string) $order['id'];
            $externalId = "wb_order_{$orderId}_new";

            if ($dedup->isDuplicate(MarketplaceType::WILDBERRIES, $externalId)) {
                continue;
            }

            $data = new MarketplaceEventData(
                marketplace: MarketplaceType::WILDBERRIES,
                eventType: EventType::ORDER_CREATED,
                entityType: EntityType::ORDER,
                entityId: $orderId,
                storeId: $account->id,
                rawPayload: $order,
                externalId: $externalId,
            );

            $event = $eventStore->create($data);
            ProcessMarketplaceEventJob::dispatch($event)->onQueue('marketplace-events');
            $dedup->markProcessed(MarketplaceType::WILDBERRIES, $externalId);
        }

        $lastOrder = end($orders);
        $state->update([
            'last_cursor'        => (string) $lastOrder['id'],
            'last_poll_at'       => now(),
            'consecutive_errors' => 0,
        ]);

        Log::info('WB polling: found ' . count($orders) . " new orders for store {$account->id}");
    }
}
