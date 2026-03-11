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

final class PollUzumOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 30;
    public int $tries   = 1;

    public function handle(
        PollingLockManager $lockManager,
        DeduplicationService $dedup,
        EventStoreService $eventStore,
    ): void {
        $states = MarketplacePollingState::where('marketplace', MarketplaceType::UZUM->value)
            ->where('endpoint', 'orders')
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
                Log::error("Uzum polling error for store {$state->store_id}: {$e->getMessage()}");

                if ($state->fresh()->consecutive_errors >= 10) {
                    $state->update(['is_active' => false]);
                    Log::critical("Uzum polling deactivated for store {$state->store_id}");
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
        $since = $state->last_poll_at
            ? $state->last_poll_at->toISOString()
            : now()->subHour()->toISOString();

        $response = Http::timeout(15)
            ->withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
            ])
            ->get('https://api-seller.uzum.uz/api/seller/order/list', [
                'statuses' => ['NEW', 'AWAITING_PACKAGING'],
                'dateFrom' => $since,
                'size'     => 100,
            ]);

        if (! $response->successful()) {
            throw new \RuntimeException("Uzum API error: {$response->status()}");
        }

        $orders = $response->json('payload.orders', $response->json('orders', []));

        if (empty($orders)) {
            $state->update(['last_poll_at' => now(), 'consecutive_errors' => 0]);

            return;
        }

        foreach ($orders as $order) {
            $orderId    = (string) ($order['id'] ?? $order['orderId'] ?? uniqid());
            $externalId = "uzum_order_{$orderId}";

            if ($dedup->isDuplicate(MarketplaceType::UZUM, $externalId)) {
                continue;
            }

            $data = new MarketplaceEventData(
                marketplace: MarketplaceType::UZUM,
                eventType: EventType::ORDER_CREATED,
                entityType: EntityType::ORDER,
                entityId: $orderId,
                storeId: $account->id,
                rawPayload: $order,
                externalId: $externalId,
            );

            $event = $eventStore->create($data);
            ProcessMarketplaceEventJob::dispatch($event)->onQueue('marketplace-events');
            $dedup->markProcessed(MarketplaceType::UZUM, $externalId);
        }

        $state->update([
            'last_poll_at'       => now(),
            'consecutive_errors' => 0,
        ]);

        Log::info('Uzum polling: found ' . count($orders) . " orders for store {$account->id}");
    }
}
