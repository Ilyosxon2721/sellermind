<?php

namespace App\Services\Pricing;

use App\Models\Pricing\PriceCalculation;
use Illuminate\Support\Collection;
use RuntimeException;

class PriceEngineService
{
    public function __construct(
        protected PriceCostService $costService,
        protected PricingOverridesService $overrideService,
        protected ChannelCostRuleService $costRuleService
    ) {}

    public function calculate(int $companyId, int $scenarioId, string $channelCode, int $skuId): ?array
    {
        $overrides = $this->overrideService->scenarioWithOverrides($companyId, $scenarioId, $channelCode, $skuId);
        $scenario = $overrides['scenario'];
        $channel = $overrides['channel'];
        $skuOverride = $overrides['sku'];

        if ($skuOverride?->is_excluded) {
            return null;
        }

        $costInfo = $this->costService->unitCost($companyId, $skuId);
        $unitCost = (float) $costInfo['cost'];
        $confidence = $costInfo['confidence'];

        $rules = $this->costRuleService->costs($channelCode, $companyId);

        $targetMargin = (float) ($skuOverride->target_margin_percent ?? $channel?->override_target_margin_percent ?? $scenario->target_margin_percent);
        $promoReserve = (float) ($skuOverride->promo_reserve_percent ?? $channel?->override_promo_reserve_percent ?? $scenario->promo_reserve_percent);
        $roundingStep = (float) ($channel?->override_rounding_step ?? $scenario->rounding_step);

        $minProfit = (float) ($skuOverride->min_profit_fixed ?? $scenario->target_profit_fixed ?? 0);

        $percentSum = $rules['commission_percent'] + $rules['payment_fee_percent'] + $rules['other_percent'] + $promoReserve;
        $fixedSum = $rules['commission_fixed'] + $rules['logistics_fixed'] + $rules['other_fixed'];

        // VAT handling (VAT_INCLUDED common): part of price is VAT
        $vatRatio = 0;
        if ($scenario->tax_mode === 'VAT_INCLUDED' && $scenario->vat_percent > 0) {
            $vatRatio = $scenario->vat_percent / (100 + $scenario->vat_percent);
        } elseif ($scenario->tax_mode === 'VAT_ADDED') {
            // price excludes VAT, but to keep MVP simple we ignore adding; could adjust percentSum
            $vatRatio = 0;
        }

        $minPrice = $this->solvePrice($unitCost, $fixedSum, $percentSum, $vatRatio, $minProfit);
        $recommendedPrice = $this->solvePriceForMargin($unitCost, $fixedSum, $percentSum, $vatRatio, $targetMargin, $scenario->target_profit_fixed ?? 0);

        $minPrice = $this->roundPrice($minPrice, $scenario->rounding_mode, $roundingStep);
        $recommendedPrice = $this->roundPrice($recommendedPrice, $scenario->rounding_mode, $roundingStep);

        $breakdown = [
            'unit_cost' => $unitCost,
            'commission_percent' => $rules['commission_percent'],
            'commission_fixed' => $rules['commission_fixed'],
            'logistics_fixed' => $rules['logistics_fixed'],
            'payment_fee_percent' => $rules['payment_fee_percent'],
            'promo_reserve_percent' => $promoReserve,
            'other_percent' => $rules['other_percent'],
            'other_fixed' => $rules['other_fixed'],
            'vat_ratio' => $vatRatio,
            'min_profit' => $minProfit,
            'target_margin' => $targetMargin,
        ];

        return [
            'company_id' => $companyId,
            'scenario_id' => $scenarioId,
            'channel_code' => $channelCode,
            'sku_id' => $skuId,
            'unit_cost' => $unitCost,
            'min_price' => $minPrice,
            'recommended_price' => $recommendedPrice,
            'breakdown' => $breakdown,
            'confidence' => $confidence,
        ];
    }

    public function calculateBulk(int $companyId, int $scenarioId, string $channelCode, array $skuIds): Collection
    {
        $rows = collect();
        foreach ($skuIds as $skuId) {
            $calc = $this->calculate($companyId, $scenarioId, $channelCode, (int) $skuId);
            if ($calc) {
                $rows->push($calc);
            }
        }

        return $rows;
    }

    public function upsertCalculations(Collection $rows): void
    {
        foreach ($rows as $row) {
            PriceCalculation::updateOrCreate(
                [
                    'company_id' => $row['company_id'],
                    'scenario_id' => $row['scenario_id'],
                    'channel_code' => $row['channel_code'],
                    'sku_id' => $row['sku_id'],
                ],
                [
                    'calculated_at' => now(),
                    'unit_cost' => $row['unit_cost'],
                    'currency_code' => 'UZS',
                    'min_price' => $row['min_price'],
                    'recommended_price' => $row['recommended_price'],
                    'breakdown_json' => $row['breakdown'],
                    'confidence' => $row['confidence'],
                ]
            );
        }
    }

    protected function solvePrice(float $cost, float $fixed, float $pct, float $vatRatio, float $minProfit): float
    {
        $denom = 1 - $pct - $vatRatio;
        if ($denom <= 0) {
            throw new RuntimeException('Invalid percent sum; cannot solve price');
        }

        return ($cost + $fixed + $minProfit) / $denom;
    }

    protected function solvePriceForMargin(float $cost, float $fixed, float $pct, float $vatRatio, float $targetMargin, float $targetProfitFixed): float
    {
        // margin target on price: net_profit >= price * targetMargin AND >= targetProfitFixed
        $denom = 1 - $pct - $vatRatio - $targetMargin;
        if ($denom <= 0) {
            // fallback to min profit only
            return $this->solvePrice($cost, $fixed, $pct, $vatRatio, max($targetProfitFixed, 0));
        }
        $profitFixed = max($targetProfitFixed, 0);

        return ($cost + $fixed + $profitFixed) / $denom;
    }

    protected function roundPrice(float $price, string $mode, float $step): float
    {
        if ($step <= 0) {
            return $price;
        }

        return match (strtoupper($mode)) {
            'NONE' => $price,
            'NEAREST' => ceil($price / $step - 0.5) * $step,
            default => ceil($price / $step) * $step, // UP
        };
    }
}
