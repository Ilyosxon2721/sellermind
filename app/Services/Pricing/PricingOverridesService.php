<?php

namespace App\Services\Pricing;

use App\Models\Pricing\PricingChannelOverride;
use App\Models\Pricing\PricingScenario;
use App\Models\Pricing\PricingSkuOverride;

class PricingOverridesService
{
    public function scenarioWithOverrides(int $companyId, int $scenarioId, string $channelCode, int $skuId): array
    {
        /** @var PricingScenario $scenario */
        $scenario = PricingScenario::byCompany($companyId)->findOrFail($scenarioId);
        $channel = PricingChannelOverride::byCompany($companyId)
            ->where('scenario_id', $scenarioId)
            ->where('channel_code', $channelCode)
            ->first();
        $sku = PricingSkuOverride::byCompany($companyId)
            ->where('scenario_id', $scenarioId)
            ->where('sku_id', $skuId)
            ->first();

        return [
            'scenario' => $scenario,
            'channel' => $channel,
            'sku' => $sku,
        ];
    }
}
