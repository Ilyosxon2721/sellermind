<?php

namespace App\Services\Pricing;

use App\Models\Pricing\PriceCalculation;
use App\Models\Pricing\PricePublishJob;
use Illuminate\Support\Facades\DB;

class PricePublishService
{
    public function buildJob(int $companyId, int $scenarioId, string $channelCode, array $skuIds, int $userId = null): PricePublishJob
    {
        $items = PriceCalculation::byCompany($companyId)
            ->where('scenario_id', $scenarioId)
            ->where('channel_code', $channelCode)
            ->whereIn('sku_id', $skuIds)
            ->get()
            ->map(function ($calc) {
                $external = \DB::table('channel_sku_maps')
                    ->where('sku_id', $calc->sku_id)
                    ->where('channel_id', function ($q) use ($channelCode, $calc) {
                        $q->select('id')->from('channels')->where('code', $channelCode)->limit(1);
                    })
                    ->value('external_offer_id');
                return [
                    'sku_id' => $calc->sku_id,
                    'recommended_price' => $calc->recommended_price,
                    'external_offer_id' => $external,
                ];
            })->values()->all();

        return PricePublishJob::create([
            'company_id' => $companyId,
            'scenario_id' => $scenarioId,
            'channel_code' => $channelCode,
            'status' => 'DRAFT',
            'payload_json' => ['items' => $items],
            'created_by' => $userId,
        ]);
    }

    public function queue(int $jobId, int $companyId): PricePublishJob
    {
        $job = PricePublishJob::byCompany($companyId)->findOrFail($jobId);
        $job->update(['status' => 'QUEUED']);
        return $job;
    }

    public function run(int $jobId, int $companyId): PricePublishJob
    {
        $job = PricePublishJob::byCompany($companyId)->findOrFail($jobId);
        $payload = $job->payload_json ?? [];
        $result = [
            'status' => 'mock_run',
            'items' => $payload['items'] ?? [],
        ];
        $job->update([
            'status' => 'DONE',
            'started_at' => $job->started_at ?? now(),
            'finished_at' => now(),
            'result_json' => $result,
        ]);
        return $job;
    }
}
