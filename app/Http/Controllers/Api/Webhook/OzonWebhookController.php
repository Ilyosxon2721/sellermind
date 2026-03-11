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
use Illuminate\Support\Facades\Log;

final class OzonWebhookController extends Controller
{
    public function __construct(
        private readonly EventStoreService $eventStore,
        private readonly DeduplicationService $dedup,
    ) {}

    public function handle(Request $request, string $webhookUuid): JsonResponse
    {
        $config = MarketplaceWebhookConfig::where('webhook_uuid', $webhookUuid)
            ->where('marketplace', MarketplaceType::OZON->value)
            ->where('is_active', true)
            ->first();

        if (! $config) {
            return response()->json(['status' => 'not_found'], 404);
        }

        $payload      = $request->all();
        $messageType  = $payload['message_type'] ?? 'unknown';
        $postingNumber = $payload['posting_number'] ?? null;
        $changedAt    = $payload['changed_at'] ?? now()->toISOString();

        $externalId = md5($messageType . $postingNumber . $changedAt);

        if ($this->dedup->isDuplicate(MarketplaceType::OZON, $externalId)) {
            return response()->json(['status' => 'duplicate'], 200);
        }

        $data = new MarketplaceEventData(
            marketplace: MarketplaceType::OZON,
            eventType: $this->mapEventType($messageType),
            entityType: $this->mapEntityType($messageType),
            entityId: $postingNumber ?? $payload['chat_id'] ?? 'unknown',
            storeId: $config->store_id,
            rawPayload: $payload,
            externalId: $externalId,
            metadata: [
                'ip'         => $request->ip(),
                'user_agent' => $request->userAgent(),
            ],
        );

        $event = $this->eventStore->create($data);
        ProcessMarketplaceEventJob::dispatch($event)->onQueue('marketplace-events');
        $config->recordEvent();

        Log::info('Ozon webhook received', [
            'message_type'   => $messageType,
            'posting_number' => $postingNumber,
            'store_id'       => $config->store_id,
        ]);

        return response()->json(['status' => 'ok'], 200);
    }

    private function mapEventType(string $ozonType): EventType
    {
        return match ($ozonType) {
            'TYPE_NEW_POSTING'       => EventType::ORDER_CREATED,
            'TYPE_POSTING_CANCELLED' => EventType::ORDER_CANCELLED,
            'TYPE_NEW_MESSAGE'       => EventType::CHAT_MESSAGE_CREATED,
            'TYPE_UPDATE_MESSAGE'    => EventType::CHAT_MESSAGE_UPDATED,
            'TYPE_MESSAGE_READ'      => EventType::CHAT_MESSAGE_READ,
            'TYPE_CHAT_CLOSED'       => EventType::CHAT_CLOSED,
            default                  => EventType::ORDER_UPDATED,
        };
    }

    private function mapEntityType(string $ozonType): EntityType
    {
        return match (true) {
            str_contains($ozonType, 'MESSAGE'),
            str_contains($ozonType, 'CHAT') => EntityType::CHAT,
            default                         => EntityType::ORDER,
        };
    }
}
