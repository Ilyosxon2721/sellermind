<?php

namespace App\Services\Autopricing;

use App\Models\Autopricing\AutopricingChangeLog;
use App\Models\Autopricing\AutopricingDailyCounter;
use App\Models\Autopricing\AutopricingPolicy;
use App\Models\Autopricing\AutopricingProposal;

class AutopricingApplyService
{
    public function approve(int $proposalId, int $companyId, ?int $userId = null): AutopricingProposal
    {
        $prop = AutopricingProposal::byCompany($companyId)->findOrFail($proposalId);
        $prop->status = 'APPROVED';
        $prop->save();

        return $prop;
    }

    public function reject(int $proposalId, int $companyId, ?string $reason = null): AutopricingProposal
    {
        $prop = AutopricingProposal::byCompany($companyId)->findOrFail($proposalId);
        $prop->status = 'REJECTED';
        $prop->error_message = $reason;
        $prop->save();

        return $prop;
    }

    public function applyApprovedBatch(int $companyId, int $policyId, string $channelCode, string $statusToApply = 'APPROVED', int $limit = 100, ?int $userId = null): array
    {
        $policy = AutopricingPolicy::byCompany($companyId)->findOrFail($policyId);

        $today = now()->toDateString();
        $counter = AutopricingDailyCounter::firstOrCreate(
            ['company_id' => $companyId, 'date' => $today, 'policy_id' => $policyId, 'channel_code' => $channelCode],
            ['changes_count' => 0]
        );
        if ($counter->changes_count >= $policy->max_changes_per_day) {
            return ['skipped' => 0, 'applied' => 0, 'reason' => 'daily_limit'];
        }

        $props = AutopricingProposal::byCompany($companyId)
            ->where('policy_id', $policyId)
            ->where('channel_code', $channelCode)
            ->whereIn('status', [$statusToApply, $policy->mode === 'AUTO_APPLY' ? 'NEW' : $statusToApply])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($props->isEmpty()) {
            return ['skipped' => 0, 'applied' => 0];
        }

        $allowedCount = max(0, $policy->max_changes_per_day - $counter->changes_count);
        $props = $props->take($allowedCount);

        $publish = app(\App\Services\Pricing\PricePublishService::class);
        $job = $publish->buildJob($companyId, $policy->scenario_id, $channelCode, $props->pluck('sku_id')->all(), $userId);
        $publish->run($job->id, $companyId);

        foreach ($props as $prop) {
            $prop->status = 'APPLIED';
            $prop->applied_job_id = $job->id;
            $prop->applied_at = now();
            $prop->save();

            AutopricingChangeLog::create([
                'company_id' => $companyId,
                'proposal_id' => $prop->id,
                'channel_code' => $channelCode,
                'sku_id' => $prop->sku_id,
                'old_price' => $prop->current_price,
                'new_price' => $prop->proposed_price,
                'applied_by' => $userId,
                'applied_by_system' => $userId ? false : true,
                'method' => $policy->mode === 'AUTO_APPLY' ? 'AUTO_APPLY' : ($statusToApply === 'APPROVED' ? 'MANUAL_APPROVE' : 'AUTO_APPLY'),
                'payload_json' => $prop->toArray(),
            ]);
        }

        $counter->changes_count += $props->count();
        $counter->save();

        return ['applied' => $props->count(), 'job_id' => $job->id];
    }
}
