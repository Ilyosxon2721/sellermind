<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhook;

use App\DTOs\MarketplaceEventData;
use App\Enums\EntityType;
use App\Enums\EventType;
use App\Enums\MarketplaceType;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMarketplaceEventJob;
use App\Models\MarketplaceWebhookConfig;
use App\Services\Notifications\DeduplicationService;
use App\Services\Notifications\EventStoreService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class YandexWebhookController extends Controller
{
    public function __construct(
        private readonly EventStoreService $eventStore,
        private readonly DeduplicationService $dedup,
    ) {}

    public function handle(Request $request, string $webhookUuid): JsonResponse
    {
        // Яндекс.Маркет делает GET запрос для верификации URL
        if ($request->isMethod('GET')) {
            return response()->json(['status' => 'ok'], 200);
        }

        // Яндекс.Маркет шлёт PING для проверки — отвечаем именем
        $payload = $request->all();
        if (($payload['notificationType'] ?? $payload['type'] ?? null) === 'PING') {
            return response()->json([
                'name' => 'SellerMind',
                'version' => '1.0.0',
                'time' => $payload['time'] ?? now()->toISOString(),
            ], 200);
        }

        $config = MarketplaceWebhookConfig::where('webhook_uuid', $webhookUuid)
            ->whereIn('marketplace', [MarketplaceType::YANDEX->value, MarketplaceType::YM->value])
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $payload = $request->all();
        $eventType = $payload['type'] ?? 'unknown';
        $orderId = (string) ($payload['order']['id'] ?? $payload['return']['id'] ?? 'unknown');
        $updatedAt = $payload['updatedAt'] ?? $payload['createdAt'] ?? now()->toISOString();

        $externalId = md5($eventType.$orderId.$updatedAt);

        if ($this->dedup->isDuplicate(MarketplaceType::YANDEX, $externalId)) {
            return response()->json(['status' => 'ok'], 200);
        }

        $internalType = match ($eventType) {
            'ORDER_CREATED' => EventType::ORDER_CREATED,
            'ORDER_STATUS_UPDATED' => EventType::ORDER_STATUS_CHANGED,
            'ORDER_CANCELLED' => EventType::ORDER_CANCELLED,
            'ORDER_UPDATED' => EventType::ORDER_UPDATED,
            'ORDER_RETURN_CREATED' => EventType::RETURN_CREATED,
            'ORDER_RETURN_STATUS_UPDATED' => EventType::RETURN_STATUS_CHANGED,
            'ORDER_CANCELLATION_REQUEST' => EventType::ORDER_CANCELLED,
            'CHAT_MESSAGE_CREATED' => EventType::CHAT_MESSAGE_CREATED,
            default => EventType::ORDER_UPDATED,
        };

        $entityType = match (true) {
            str_contains($eventType, 'RETURN') => EntityType::RETURN,
            str_contains($eventType, 'CHAT') => EntityType::CHAT,
            default => EntityType::ORDER,
        };

        $data = new MarketplaceEventData(
            marketplace: MarketplaceType::YANDEX,
            eventType: $internalType,
            entityType: $entityType,
            entityId: $orderId,
            storeId: $config->store_id,
            rawPayload: $payload,
            externalId: $externalId,
            metadata: ['ip' => $request->ip()],
        );

        $event = $this->eventStore->create($data);
        ProcessMarketplaceEventJob::dispatch($event)->onQueue('marketplace-events');
        $config->recordEvent();

        return response()->json(['status' => 'ok'], 200);
    }
}
