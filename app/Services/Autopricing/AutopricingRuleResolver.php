<?php

namespace App\Services\Autopricing;

use App\Models\Autopricing\AutopricingRule;

class AutopricingRuleResolver
{
    /**
     * Возвращает упорядоченный список правил (SKU > CATEGORY > GLOBAL) по приоритету.
     */
    public function resolve(int $companyId, int $policyId, int $skuId, ?int $categoryId = null): \Illuminate\Support\Collection
    {
        $rules = AutopricingRule::byCompany($companyId)
            ->where('policy_id', $policyId)
            ->where('is_active', true)
            ->get()
            ->filter(function ($rule) use ($skuId, $categoryId) {
                if ($rule->scope_type === 'SKU') {
                    return (int) $rule->scope_id === $skuId;
                }
                if ($rule->scope_type === 'CATEGORY') {
                    return $categoryId && (int) $rule->scope_id === $categoryId;
                }
                return true; // GLOBAL
            })
            ->sortBy('priority')
            ->values();

        // Сначала SKU, потом CATEGORY, потом GLOBAL
        return $rules->sortBy(function ($rule) {
            return match ($rule->scope_type) {
                'SKU' => 1,
                'CATEGORY' => 2,
                default => 3,
            } * 1000 + $rule->priority;
        })->values();
    }
}
