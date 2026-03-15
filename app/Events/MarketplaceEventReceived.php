<?php

declare(strict_types=1);

namespace App\Events;

use App\Models\MarketplaceEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MarketplaceEventReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly MarketplaceEvent $event,
        public readonly int $companyId,
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("company.{$this->companyId}.marketplace-events");
    }

    public function broadcastAs(): string
    {
        return 'marketplace.event';
    }

    public function broadcastWith(): array
    {
        return [
            'uuid'        => $this->event->uuid,
            'marketplace' => $this->event->marketplace->value,
            'event_type'  => $this->event->event_type->value,
            'entity_type' => $this->event->entity_type->value,
            'entity_id'   => $this->event->entity_id,
            'store_id'    => $this->event->store_id,
            'occurred_at' => $this->event->created_at->toISOString(),
            'summary'     => $this->buildSummary(),
        ];
    }

    private function buildSummary(): string
    {
        return $this->event->event_type->label()
            . ' — ' . $this->event->marketplace->label()
            . ' #' . $this->event->entity_id;
    }
}
