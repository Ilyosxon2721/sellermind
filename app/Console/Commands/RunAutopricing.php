<?php

namespace App\Console\Commands;

use App\Models\Autopricing\AutopricingPolicy;
use App\Models\Warehouse\Sku;
use App\Services\Autopricing\AutopricingApplyService;
use App\Services\Autopricing\AutopricingEngineService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunAutopricing extends Command
{
    protected $signature = 'autopricing:run {--policy=} {--channel=} {--limit=200}';

    protected $description = 'Recalculate autopricing proposals and optionally auto-apply for AUTO_APPLY policies';

    public function handle(): int
    {
        $policyId = (int) $this->option('policy');
        $channel = $this->option('channel');
        $limit = (int) $this->option('limit');

        $policy = AutopricingPolicy::findOrFail($policyId);
        $companyId = $policy->company_id;
        $channelCode = $channel ?: ($policy->channel_code ?? 'UZUM');

        $engine = app(AutopricingEngineService::class);

        $skus = Sku::byCompany($companyId)->limit($limit)->pluck('id');
        $created = 0;

        foreach ($skus as $skuId) {
            $prop = $engine->calculateProposal($companyId, $policyId, $channelCode, $skuId);
            if ($prop) {
                DB::table('autopricing_proposals')->insert($prop + ['created_at' => now(), 'updated_at' => now()]);
                $created++;
            }
        }

        $this->info("Proposals created: {$created}");

        if ($policy->mode === 'AUTO_APPLY') {
            $res = app(AutopricingApplyService::class)->applyApprovedBatch($companyId, $policyId, $channelCode, 'NEW', $limit);
            $this->info('Auto-applied: '.json_encode($res));
        }

        return Command::SUCCESS;
    }
}
