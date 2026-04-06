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
 * Push return/cancellation events into risment:returns Redis queue.
 * RISMENT reads from this queue to handle returns.
 */
class SendReturnToRisment implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        protected int $companyId,
        protected string $event,
        protected array $returnData,
        protected ?int $rismentClientId = null,
    ) {
        $this->connection = 'risment-integration';
        $this->queue = 'risment:returns';
    }

    public function handle(): void
    {
        $links = $this->resolveLinks();

        if ($links->isEmpty()) {
            return;
        }

        foreach ($links as $link) {
            $message = json_encode([
                'event' => $this->event,
                'timestamp' => now()->toIso8601String(),
                'source' => 'sellermind',
                'link_token' => $link->link_token,
                'risment_client_id' => $link->risment_client_id,
                'data' => $this->returnData,
            ], JSON_UNESCAPED_UNICODE);

            Redis::connection('integration')->rpush('risment:returns', $message);

            Log::info('SendReturnToRisment: Pushed to risment:returns', [
                'company_id' => $this->companyId,
                'risment_client_id' => $link->risment_client_id,
                'event' => $this->event,
            ]);
        }
    }

    protected function resolveLinks(): \Illuminate\Support\Collection
    {
        if ($this->rismentClientId) {
            $link = IntegrationLink::rismentForClient($this->rismentClientId);

            return $link ? collect([$link]) : collect();
        }

        return IntegrationLink::rismentLinksForCompany($this->companyId);
    }
}
