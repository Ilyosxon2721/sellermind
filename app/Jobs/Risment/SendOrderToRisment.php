<?php

namespace App\Jobs\Risment;

use App\Models\IntegrationLink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Push order data into risment:orders Redis queue.
 * RISMENT reads from this queue to process FBS shipments.
 */
class SendOrderToRisment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        protected int $companyId,
        protected string $event,
        protected array $orderData,
    ) {
        $this->connection = 'risment-integration';
        $this->queue = 'risment:orders';
    }

    public function handle(): void
    {
        $link = IntegrationLink::rismentForCompany($this->companyId);

        if (!$link) {
            Log::debug('SendOrderToRisment: No active RISMENT link for company', [
                'company_id' => $this->companyId,
            ]);
            return;
        }

        $message = json_encode([
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'source' => 'sellermind',
            'link_token' => $link->link_token,
            'data' => $this->orderData,
        ], JSON_UNESCAPED_UNICODE);

        Redis::connection('integration')->rpush('risment:orders', $message);

        Log::info('SendOrderToRisment: Pushed to risment:orders', [
            'company_id' => $this->companyId,
            'event' => $this->event,
            'order_id' => $this->orderData['order_id'] ?? null,
        ]);
    }
}
