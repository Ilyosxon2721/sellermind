<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\DTOs\MarketplaceEventData;
use App\Enums\EventStatus;
use App\Models\MarketplaceEvent;
use Illuminate\Support\Str;

final class EventStoreService
{
    /**
     * Сохранить событие в БД
     */
    public function create(MarketplaceEventData $data): MarketplaceEvent
    {
        return MarketplaceEvent::create([
            'uuid' => Str::uuid()->toString(),
            'store_id' => $data->storeId,
            'marketplace' => $data->marketplace->value,
            'event_type' => $data->eventType->value,
            'external_id' => $data->externalId,
            'entity_type' => $data->entityType->value,
            'entity_id' => $data->entityId,
            'payload' => $data->rawPayload,
            'normalized_data' => $data->normalizedData,
            'status' => EventStatus::RECEIVED->value,
            'metadata' => $data->metadata,
        ]);
    }
}
