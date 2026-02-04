<?php

namespace App\Services\Risment;

use App\Jobs\Risment\DispatchRismentWebhookJob;
use App\Models\Risment\RismentWebhookEndpoint;
use App\Models\Risment\RismentWebhookLog;
use Illuminate\Support\Facades\Log;

class RismentWebhookService
{
    /**
     * Dispatch a webhook event to all active endpoints for the company
     */
    public function dispatch(int $companyId, string $event, array $data): void
    {
        $endpoints = RismentWebhookEndpoint::where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        foreach ($endpoints as $endpoint) {
            if (!$endpoint->listensTo($event)) {
                continue;
            }

            $payload = [
                'event' => $event,
                'timestamp' => now()->toIso8601String(),
                'data' => $data,
            ];

            try {
                $log = RismentWebhookLog::create([
                    'webhook_endpoint_id' => $endpoint->id,
                    'event' => $event,
                    'payload' => $payload,
                    'attempts' => 0,
                ]);

                DispatchRismentWebhookJob::dispatch($log->id);

            } catch (\Exception $e) {
                Log::error('Risment: Failed to queue webhook', [
                    'endpoint_id' => $endpoint->id,
                    'event' => $event,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * Deliver a single webhook (called from job)
     */
    public function deliver(int $logId): bool
    {
        $log = RismentWebhookLog::with('endpoint')->find($logId);

        if (!$log || !$log->endpoint || !$log->endpoint->is_active) {
            return false;
        }

        $endpoint = $log->endpoint;
        $payload = json_encode($log->payload);
        $signature = hash_hmac('sha256', $payload, $endpoint->secret);

        $log->increment('attempts');

        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Risment-Signature' => $signature,
                    'X-Risment-Event' => $log->event,
                    'X-Risment-Delivery' => (string) $log->id,
                ])
                ->withBody($payload, 'application/json')
                ->post($endpoint->url);

            $log->update([
                'response_code' => $response->status(),
                'response_body' => mb_substr($response->body(), 0, 2000),
            ]);

            if ($response->successful()) {
                $log->update(['delivered_at' => now()]);
                return true;
            }

            // Schedule retry with exponential backoff
            $this->scheduleRetry($log);
            return false;

        } catch (\Exception $e) {
            Log::warning('Risment: Webhook delivery failed', [
                'log_id' => $log->id,
                'url' => $endpoint->url,
                'attempt' => $log->attempts,
                'error' => $e->getMessage(),
            ]);

            $log->update([
                'response_code' => 0,
                'response_body' => $e->getMessage(),
            ]);

            $this->scheduleRetry($log);
            return false;
        }
    }

    protected function scheduleRetry(RismentWebhookLog $log): void
    {
        if ($log->attempts >= 5) {
            return; // max retries reached
        }

        // Exponential backoff: 30s, 2m, 8m, 32m
        $delaySeconds = (int) (30 * pow(4, $log->attempts - 1));
        $retryAt = now()->addSeconds($delaySeconds);

        $log->update(['next_retry_at' => $retryAt]);

        DispatchRismentWebhookJob::dispatch($log->id)
            ->delay($retryAt);
    }
}
