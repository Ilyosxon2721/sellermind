<?php

namespace App\Services\Autopricing;

use App\Models\Autopricing\AutopricingPolicy;
use App\Models\Autopricing\AutopricingProposal;
use App\Models\Pricing\PriceCalculation;
use App\Services\Warehouse\StockBalanceService;
use Illuminate\Support\Facades\DB;

class AutopricingEngineService
{
    public function __construct(
        protected AutopricingRuleResolver $resolver,
        protected StockBalanceService $balanceService
    ) {}

    public function calculateProposal(int $companyId, int $policyId, string $channelCode, int $skuId, ?int $categoryId = null): ?array
    {
        $policy = AutopricingPolicy::byCompany($companyId)->findOrFail($policyId);
        $calc = PriceCalculation::byCompany($companyId)
            ->where('channel_code', $channelCode)
            ->where('scenario_id', $policy->scenario_id)
            ->where('sku_id', $skuId)
            ->first();
        if (! $calc) {
            return null;
        }

        $current = $this->currentPrice($companyId, $channelCode, $skuId);
        $minPrice = $calc->min_price;
        $recommended = $calc->recommended_price;
        $proposed = $recommended;
        $reasons = [];

        $rules = $this->resolver->resolve($companyId, $policyId, $skuId, $categoryId);
        foreach ($rules as $rule) {
            $p = $this->applyRule($rule->rule_type, $rule->params_json ?? [], $current, $minPrice, $recommended, $skuId, $channelCode, $companyId);
            if ($p !== null) {
                $proposed = $p;
                $reasons[] = ['rule' => $rule->rule_type, 'params' => $rule->params_json];
            }
        }

        // Safety layer
        $safety = [];
        if ($policy->min_price_guard) {
            if ($proposed < $minPrice) {
                $proposed = $minPrice;
                $safety[] = 'MIN_PRICE_GUARD';
            }
        }
        if ($policy->max_price_guard && $policy->max_price_value) {
            if ($proposed > $policy->max_price_value) {
                $proposed = $policy->max_price_value;
                $safety[] = 'MAX_PRICE_GUARD';
            }
        }
        if ($current !== null && $current > 0) {
            $deltaPercent = abs(($proposed - $current) / $current);
            $deltaAmount = abs($proposed - $current);
            if ($deltaPercent > $policy->max_delta_percent) {
                $proposed = $current + ($policy->max_delta_percent * $current) * (($proposed > $current) ? 1 : -1);
                $safety[] = 'CLAMPED_BY_MAX_DELTA_PERCENT';
            }
            if ($policy->max_delta_amount > 0 && $deltaAmount > $policy->max_delta_amount) {
                $proposed = $current + $policy->max_delta_amount * (($proposed > $current) ? 1 : -1);
                $safety[] = 'CLAMPED_BY_MAX_DELTA_AMOUNT';
            }
        }

        // Skip if no change
        if ($current !== null && abs($proposed - $current) < 0.001) {
            return null;
        }

        $deltaAmount = $current !== null ? ($proposed - $current) : $proposed;
        $deltaPercent = $current !== null && $current > 0 ? ($deltaAmount / $current) : 0;

        return [
            'company_id' => $companyId,
            'calculated_at' => now(),
            'policy_id' => $policyId,
            'channel_code' => $channelCode,
            'sku_id' => $skuId,
            'current_price' => $current,
            'min_price' => $minPrice,
            'recommended_price' => $recommended,
            'proposed_price' => $proposed,
            'delta_amount' => $deltaAmount,
            'delta_percent' => $deltaPercent,
            'status' => 'NEW',
            'reasons_json' => $reasons,
            'safety_flags_json' => $safety,
        ];
    }

    protected function applyRule(string $type, array $params, ?float $current, float $min, float $recommended, int $skuId, string $channelCode, int $companyId): ?float
    {
        return match ($type) {
            'TARGET_MARGIN' => ($params['use'] ?? 'RECOMMENDED') === 'MIN' ? $min : $recommended,
            'STOCK_SCARCITY_UP' => $this->stockScarcity($params, $current, $min, $recommended, $skuId, $channelCode, $companyId),
            'STOCK_EXCESS_DOWN' => $this->stockExcess($params, $current, $min, $recommended, $skuId, $channelCode, $companyId),
            default => null,
        };
    }

    protected function stockScarcity(array $params, ?float $current, float $min, float $recommended, int $skuId, string $channelCode, int $companyId): ?float
    {
        $available = $this->balance($companyId, $skuId);
        if ($available !== null && isset($params['available_lt']) && $available < $params['available_lt']) {
            $inc = $params['increase_percent'] ?? 0.05;
            $base = $current ?? $recommended;

            return $base * (1 + $inc);
        }

        return null;
    }

    protected function stockExcess(array $params, ?float $current, float $min, float $recommended, int $skuId, string $channelCode, int $companyId): ?float
    {
        $available = $this->balance($companyId, $skuId);
        if ($available !== null && isset($params['available_gt']) && $available > $params['available_gt']) {
            $dec = $params['decrease_percent'] ?? 0.05;
            $base = $current ?? $recommended;
            $p = $base * (1 - $dec);
            if (($params['floor'] ?? null) === 'MIN_PRICE') {
                $p = max($p, $min);
            }

            return $p;
        }

        return null;
    }

    protected function balance(int $companyId, int $skuId): ?float
    {
        // Simple balance across all warehouses (can be refined)
        $wh = DB::table('warehouses')->where('company_id', $companyId)->pluck('id');
        if ($wh->isEmpty()) {
            return null;
        }
        $onHand = DB::table('stock_ledger')
            ->where('company_id', $companyId)
            ->whereIn('warehouse_id', $wh)
            ->where('sku_id', $skuId)
            ->sum('qty_delta');
        $reserved = DB::table('stock_reservations')
            ->where('company_id', $companyId)
            ->whereIn('warehouse_id', $wh)
            ->where('sku_id', $skuId)
            ->where('status', 'ACTIVE')
            ->sum('qty');

        return (float) $onHand - (float) $reserved;
    }

    protected function currentPrice(int $companyId, string $channelCode, int $skuId): ?float
    {
        $channelId = DB::table('channels')->where('code', $channelCode)->where('company_id', $companyId)->value('id');
        if (! $channelId) {
            return null;
        }
        $meta = DB::table('channel_sku_maps')
            ->where('channel_id', $channelId)
            ->where('sku_id', $skuId)
            ->value('meta_json');
        if ($meta) {
            $json = json_decode($meta, true);
            if (isset($json['current_price'])) {
                return (float) $json['current_price'];
            }
        }

        return null;
    }

    public function persistProposal(array $data): AutopricingProposal
    {
        return AutopricingProposal::create($data);
    }
}
