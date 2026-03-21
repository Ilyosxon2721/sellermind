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

    public int $tries = 1;

    /**
     * Статусы заказов для поллинга (OpenAPI v2)
     */
    private const POLL_STATUSES = ['CREATED', 'PACKING'];

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
                if (! $account) {
                    continue;
                }

                $headers = $account->getUzumAuthHeaders();
                if (empty($headers)) {
                    Log::warning("Uzum polling: no auth token for store {$state->store_id}");

                    continue;
                }

                $this->pollOrders($state, $account, $headers, $dedup, $eventStore);

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
        array $headers,
        DeduplicationService $dedup,
        EventStoreService $eventStore,
    ): void {
        $baseUrl = config('uzum.base_url', 'https://api-seller.uzum.uz/api/seller-openapi');

        foreach (self::POLL_STATUSES as $status) {
            $response = Http::timeout(15)
                ->withHeaders(array_merge($headers, [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ]))
                ->get("{$baseUrl}/v2/fbs/orders", [
                    'status' => $status,
                    'size' => 50,
                    'page' => 0,
                ]);

            if ($response->status() === 429) {
                Log::warning("Uzum polling: rate limited for store {$account->id}, status {$status}");
                usleep(500_000);

                continue;
            }

            if (! $response->successful()) {
                throw new \RuntimeException("Uzum API error: {$response->status()} for status {$status}");
            }

            $orders = $response->json('payload.orders', $response->json('orders', []));

            foreach ($orders as $order) {
                $orderId = (string) ($order['id'] ?? $order['orderId'] ?? uniqid());
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

            // Пауза между статусами для rate-limiting
            usleep(300_000);
        }

        $state->update([
            'last_poll_at' => now(),
            'consecutive_errors' => 0,
        ]);

        Log::info("Uzum polling completed for store {$account->id}");
    }
}
