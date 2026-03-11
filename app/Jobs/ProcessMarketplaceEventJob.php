<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\EventStatus;
use App\Models\MarketplaceEvent;
use App\Services\Notifications\MarketplaceEventBroadcaster;
use App\Services\Notifications\TelegramNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class ProcessMarketplaceEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;
    public int $timeout = 30;

    public function __construct(
        public readonly MarketplaceEvent $event,
    ) {
        $this->onQueue('marketplace-events');
    }

    public function handle(
        MarketplaceEventBroadcaster $broadcaster,
        TelegramNotificationService $telegram,
    ): void {
        $this->event->update([
            'status'   => EventStatus::PROCESSING->value,
            'attempts' => $this->attempts(),
        ]);

        try {
            // Broadcast через WebSocket
            $broadcaster->broadcast($this->event);

            // Telegram уведомление
            $telegram->notify($this->event);

            $this->event->markProcessed();

            Log::info('Marketplace event processed', [
                'uuid'        => $this->event->uuid,
                'marketplace' => $this->event->marketplace->value,
                'event_type'  => $this->event->event_type->value,
            ]);
        } catch (\Throwable $e) {
            $this->event->update([
                'status'        => EventStatus::FAILED->value,
                'error_message' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    public function backoff(): array
    {
        return [10, 30, 90, 270, 810];
    }

    public function failed(\Throwable $exception): void
    {
        $this->event->update([
            'status'        => EventStatus::FAILED->value,
            'error_message' => $exception->getMessage(),
        ]);

        Log::error('Marketplace event failed after all retries', [
            'uuid'  => $this->event->uuid,
            'error' => $exception->getMessage(),
        ]);
    }

}
