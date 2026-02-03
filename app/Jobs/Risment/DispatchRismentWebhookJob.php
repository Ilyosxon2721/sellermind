<?php

namespace App\Jobs\Risment;

use App\Services\Risment\RismentWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DispatchRismentWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1; // retries handled by the service itself
    public int $timeout = 30;

    public function __construct(
        protected int $webhookLogId,
    ) {}

    public function handle(RismentWebhookService $service): void
    {
        $service->deliver($this->webhookLogId);
    }
}
