<?php

declare(strict_types=1);

namespace App\Services\Pricing;

use App\Models\Pricing\ChannelCostRule;

final class ChannelCostRuleService
{
    /**
     * Возвращает затраты канала (проценты в дробях: 0.25 = 25%)
     */
    public function costs(string $channelCode, int $companyId): array
    {
        $row = ChannelCostRule::byCompany($companyId)
            ->where('channel_code', $channelCode)
            ->first();

        return [
            'commission_percent' => $row->commission_percent ?? 0.25,
            'commission_fixed' => $row->commission_fixed ?? 0,
            'logistics_fixed' => $row->logistics_fixed ?? 0,
            'return_logistics_fixed' => $row->return_logistics_fixed ?? 0,
            'payment_fee_percent' => $row->payment_fee_percent ?? 0,
            'return_percent' => $row->return_percent ?? 0,
            'storage_cost_per_day' => $row->storage_cost_per_day ?? 0,
            'vat_percent' => $row->vat_percent ?? 0,
            'turnover_tax_percent' => $row->turnover_tax_percent ?? 0,
            'profit_tax_percent' => $row->profit_tax_percent ?? 0,
            'other_percent' => $row->other_percent ?? 0,
            'other_fixed' => $row->other_fixed ?? 0,
        ];
    }
}
