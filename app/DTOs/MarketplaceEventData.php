<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\EntityType;
use App\Enums\EventType;
use App\Enums\MarketplaceType;
use Carbon\Carbon;

final readonly class MarketplaceEventData
{
    public function __construct(
        public MarketplaceType $marketplace,
        public EventType $eventType,
        public EntityType $entityType,
        public string $entityId,
        public int $storeId,
        public array $rawPayload,
        public ?string $externalId = null,
        public ?Carbon $occurredAt = null,
        public array $metadata = [],
        public ?array $normalizedData = null,
    ) {}
}
