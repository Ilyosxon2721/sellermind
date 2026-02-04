<?php

namespace App\Events;

use App\Models\UzumOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UzumOrderUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public UzumOrder $order,
        public string $action, // 'created', 'updated', 'deleted'
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('company.'.$this->order->account->company_id),
            new PrivateChannel('marketplace-account.'.$this->order->marketplace_account_id),
        ];
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'order_id' => $this->order->id,
            'external_order_id' => $this->order->external_order_id,
            'status' => $this->order->status,
            'marketplace_account_id' => $this->order->marketplace_account_id,
            'total_amount' => $this->order->total_amount,
            'ordered_at' => $this->order->ordered_at?->toIso8601String(),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'uzum.order.updated';
    }
}
