<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceOrdersUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $companyId;

    public int $marketplaceAccountId;

    public int $newOrdersCount;

    public array $stats;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $companyId,
        int $marketplaceAccountId,
        int $newOrdersCount = 0,
        array $stats = []
    ) {
        $this->companyId = $companyId;
        $this->marketplaceAccountId = $marketplaceAccountId;
        $this->newOrdersCount = $newOrdersCount;
        $this->stats = $stats;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        // Private channel для конкретной компании и маркетплейс аккаунта
        return [
            new Channel("company.{$this->companyId}.marketplace.{$this->marketplaceAccountId}.orders"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'orders.updated';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'marketplace_account_id' => $this->marketplaceAccountId,
            'new_orders_count' => $this->newOrdersCount,
            'stats' => $this->stats,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
