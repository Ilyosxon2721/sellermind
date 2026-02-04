<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceDataChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $companyId;

    public int $marketplaceAccountId;

    public string $dataType; // 'orders', 'products', 'stocks', 'prices'

    public string $changeType; // 'created', 'updated', 'deleted'

    public int $affectedCount;

    public ?array $changes; // Детали изменений

    public ?array $metadata;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $companyId,
        int $marketplaceAccountId,
        string $dataType,
        string $changeType,
        int $affectedCount = 0,
        ?array $changes = null,
        ?array $metadata = null
    ) {
        $this->companyId = $companyId;
        $this->marketplaceAccountId = $marketplaceAccountId;
        $this->dataType = $dataType;
        $this->changeType = $changeType;
        $this->affectedCount = $affectedCount;
        $this->changes = $changes;
        $this->metadata = $metadata;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("company.{$this->companyId}.marketplace.{$this->marketplaceAccountId}.data"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'data.changed';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'marketplace_account_id' => $this->marketplaceAccountId,
            'data_type' => $this->dataType,
            'change_type' => $this->changeType,
            'affected_count' => $this->affectedCount,
            'changes' => $this->changes,
            'metadata' => $this->metadata,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
