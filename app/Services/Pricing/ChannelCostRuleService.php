<?php

namespace App\Services\Pricing;

use Illuminate\Support\Facades\DB;

class ChannelCostRuleService
{
    /**
     * Return variable and fixed costs as percents/fixed parts.
     */
    public function costs(string $channelCode, int $companyId): array
    {
        $row = null;
        if (DB::getSchemaBuilder()->hasTable('channel_cost_rules')) {
            $row = DB::table('channel_cost_rules')
                ->where('company_id', $companyId)
                ->where('channel_code', $channelCode)
                ->orderByDesc('id')
                ->first();
        }

        return [
            'commission_percent' => (float) ($row->commission_percent ?? 0.25),
            'commission_fixed' => (float) ($row->commission_fixed ?? 0),
            'logistics_fixed' => (float) ($row->logistics_fixed ?? 0),
            'payment_fee_percent' => (float) ($row->payment_fee_percent ?? 0),
            'other_percent' => (float) ($row->other_percent ?? 0),
            'other_fixed' => (float) ($row->other_fixed ?? 0),
        ];
    }
}
