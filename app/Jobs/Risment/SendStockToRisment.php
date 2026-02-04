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
 * Push stock update events into risment:stock Redis queue.
 * RISMENT reads from this queue to sync inventory.
 */
class SendStockToRisment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    public function __construct(
        protected int $companyId,
        protected string $event,
        protected array $stockData,
    ) {
        $this->connection = 'risment-integration';
        $this->queue = 'risment:stock';
    }

    public function handle(): void
    {
        $link = IntegrationLink::rismentForCompany($this->companyId);

        if (!$link) {
            return;
        }

        $message = json_encode([
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'source' => 'sellermind',
            'link_token' => $link->link_token,
            'data' => $this->stockData,
        ], JSON_UNESCAPED_UNICODE);

        Redis::connection('integration')->rpush('risment:stock', $message);

        Log::info('SendStockToRisment: Pushed to risment:stock', [
            'company_id' => $this->companyId,
            'event' => $this->event,
        ]);
    }
}
