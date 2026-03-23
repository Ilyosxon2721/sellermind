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

    public int $timeout = 60;
    public int $tries   = 1;

    /**
     * Статусы заказов для поллинга (OpenAPI v2)
     * Включены все активные статусы для полного отслеживания
     */
    private const POLL_STATUSES = [
        'CREATED',
        'PACKING',
        'PENDING_DELIVERY',
        'DELIVERING',
        'ACCEPTED_AT_DP',
        'DELIVERED_TO_CUSTOMER_DELIVERY_POINT',
    ];

    /**
     * Маппинг статусов Uzum → тип события
     */
    private const STATUS_EVENT_MAP = [
        'CREATED'                              => EventType::ORDER_CREATED,
        'PACKING'                              => EventType::ORDER_STATUS_CHANGED,
        'PENDING_DELIVERY'                     => EventType::ORDER_STATUS_CHANGED,
        'DELIVERING'                           => EventType::ORDER_STATUS_CHANGED,
        'ACCEPTED_AT_DP'                       => EventType::ORDER_STATUS_CHANGED,
        'DELIVERED_TO_CUSTOMER_DELIVERY_POINT' => EventType::ORDER_STATUS_CHANGED,
    ];

    /**
     * Максимальное количество страниц для одного статуса
     */
    private const MAX_PAGES = 5;

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
            $page = 0;

            do {
                $response = Http::timeout(15)
                    ->withHeaders(array_merge($headers, [
                        'Accept'       => 'application/json',
                        'Content-Type' => 'application/json',
                    ]))
                    ->get("{$baseUrl}/v2/fbs/orders", [
                        'status' => $status,
                        'size'   => 50,
                        'page'   => $page,
                    ]);

                if ($response->status() === 429) {
                    Log::warning("Uzum polling: rate limited for store {$account->id}, status {$status}");
                    usleep(1_000_000); // Пауза 1 сек при rate limit
                    break; // Переходим к следующему статусу
                }

                if (! $response->successful()) {
                    throw new \RuntimeException("Uzum API error: {$response->status()} for status {$status}");
                }

                $orders = $response->json('payload.orders', $response->json('orders', []));

                foreach ($orders as $order) {
                    $orderId    = (string) ($order['id'] ?? $order['orderId'] ?? uniqid());
                    $externalId = "uzum_order_{$orderId}_{$status}";

                    if ($dedup->isDuplicate(MarketplaceType::UZUM, $externalId)) {
                        continue;
                    }

                    $eventType = self::STATUS_EVENT_MAP[$status] ?? EventType::ORDER_STATUS_CHANGED;

                    $data = new MarketplaceEventData(
                        marketplace: MarketplaceType::UZUM,
                        eventType: $eventType,
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

                $page++;

                // Пауза между страницами
                usleep(300_000);

            } while (count($orders) >= 50 && $page < self::MAX_PAGES);

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
