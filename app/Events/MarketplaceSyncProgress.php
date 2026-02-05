<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MarketplaceSyncProgress implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $companyId;

    public int $marketplaceAccountId;

    public string $status; // 'started', 'progress', 'completed', 'error'

    public string $message;

    public ?int $progress; // 0-100

    public ?array $data;

    /**
     * Create a new event instance.
     */
    public function __construct(
        int $companyId,
        int $marketplaceAccountId,
        string $status,
        string $message,
        ?int $progress = null,
        ?array $data = null
    ) {
        $this->companyId = $companyId;
        $this->marketplaceAccountId = $marketplaceAccountId;
        $this->status = $status;
        $this->message = $message;
        $this->progress = $progress;
        $this->data = $data;
    }

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new Channel("company.{$this->companyId}.marketplace.{$this->marketplaceAccountId}.sync"),
        ];
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs(): string
    {
        return 'sync.progress';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith(): array
    {
        return [
            'company_id' => $this->companyId,
            'marketplace_account_id' => $this->marketplaceAccountId,
            'status' => $this->status,
            'message' => $this->message,
            'progress' => $this->progress,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
