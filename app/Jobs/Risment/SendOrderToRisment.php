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
        protected ?int $rismentClientId = null,
    ) {
        $this->connection = 'risment-integration';
        $this->queue = 'risment:orders';
    }

    public function handle(): void
    {
        // Если указан клиент — отправляем только для его связки
        $links = $this->resolveLinks();

        if ($links->isEmpty()) {
            Log::debug('SendOrderToRisment: No active RISMENT links', [
                'company_id' => $this->companyId,
                'risment_client_id' => $this->rismentClientId,
            ]);

            return;
        }

        foreach ($links as $link) {
            $message = json_encode([
                'event' => $this->event,
                'timestamp' => now()->toIso8601String(),
                'source' => 'sellermind',
                'link_token' => $link->link_token,
                'risment_client_id' => $link->risment_client_id,
                'data' => $this->orderData,
            ], JSON_UNESCAPED_UNICODE);

            Redis::connection('integration')->rpush('risment:orders', $message);

            Log::info('SendOrderToRisment: Pushed to risment:orders', [
                'company_id' => $this->companyId,
                'risment_client_id' => $link->risment_client_id,
                'event' => $this->event,
                'order_id' => $this->orderData['order_id'] ?? null,
            ]);
        }
    }

    /**
     * Получить все активные связки для отправки
     */
    protected function resolveLinks(): \Illuminate\Support\Collection
    {
        if ($this->rismentClientId) {
            $link = IntegrationLink::rismentForClient($this->rismentClientId);

            return $link ? collect([$link]) : collect();
        }

        return IntegrationLink::rismentLinksForCompany($this->companyId);
    }
}
