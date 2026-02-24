<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Pricing;

use App\Http\Controllers\Controller;
use App\Models\Pricing\MarketplaceCategory;
use App\Models\Pricing\MarketplaceCommission;
use App\Models\Pricing\ProductPricing;
use App\Services\Pricing\PricingCalculatorService;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CalculatorController extends Controller
{
    use ApiResponder;

    public function __construct(
        private readonly PricingCalculatorService $calculator,
    ) {}

    /**
     * Рассчитать расходы и маржу
     */
    public function calculate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace' => 'required|string|in:wildberries,ozon,yandex,uzum',
            'fulfillment_type' => 'required|string|in:fbo,fbs,dbs,express',
            'category_id' => 'nullable|integer|exists:marketplace_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'delivery_to_warehouse' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'storage_cost' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'target_margin_percent' => 'nullable|numeric|min:0|max:99',
        ]);

        $pricing = new ProductPricing($validated);
        $pricing->marketplace_category_id = $validated['category_id'] ?? null;

        $targetMargin = $validated['target_margin_percent'] ?? 30;
        $recommendedPrice = $this->calculator->calculatePriceForMargin($pricing, (float) $targetMargin);

        $price = $validated['price'] ?? $recommendedPrice;
        $result = $this->calculator->calculate($pricing, (float) $price);
        $result->recommendedPrice = $recommendedPrice;

        // Разница с текущей ценой
        if (! empty($validated['price']) && $recommendedPrice > 0) {
            $result->priceDiff = $recommendedPrice - (float) $validated['price'];
        }

        return $this->successResponse($result->toArray());
    }

    /**
     * Обратный расчёт: цена для целевой маржи
     */
    public function calculateForMargin(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'marketplace' => 'required|string|in:wildberries,ozon,yandex,uzum',
            'fulfillment_type' => 'required|string|in:fbo,fbs,dbs,express',
            'category_id' => 'nullable|integer|exists:marketplace_categories,id',
            'cost_price' => 'required|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'delivery_to_warehouse' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'storage_cost' => 'nullable|numeric|min:0',
            'target_margin_percent' => 'required|numeric|min:0|max:99',
        ]);

        $pricing = new ProductPricing($validated);
        $pricing->marketplace_category_id = $validated['category_id'] ?? null;

        $recommendedPrice = $this->calculator->calculatePriceForMargin(
            $pricing,
            (float) $validated['target_margin_percent'],
        );
        $result = $this->calculator->calculate($pricing, $recommendedPrice);
        $result->recommendedPrice = $recommendedPrice;

        return $this->successResponse([
            'recommended_price' => $recommendedPrice,
            'calculation' => $result->toArray(),
        ]);
    }

    /**
     * Сравнить прибыльность на разных маркетплейсах
     */
    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'cost_price' => 'required|numeric|min:0',
            'packaging_cost' => 'nullable|numeric|min:0',
            'delivery_to_warehouse' => 'nullable|numeric|min:0',
            'other_costs' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'weight_kg' => 'nullable|numeric|min:0',
            'storage_cost' => 'nullable|numeric|min:0',
            'target_margin_percent' => 'nullable|numeric|min:0|max:99',
            'marketplaces' => 'nullable|array',
            'marketplaces.*' => 'string|in:wildberries,ozon,yandex,uzum',
        ]);

        $pricing = new ProductPricing($validated);
        $pricing->target_margin_percent = $validated['target_margin_percent'] ?? 30;
        $pricing->fulfillment_type = 'fbo';
        $pricing->marketplace = 'wildberries';

        $marketplaces = $validated['marketplaces'] ?? ['wildberries', 'ozon', 'yandex', 'uzum'];
        $results = $this->calculator->compareMarketplaces($pricing, $marketplaces);

        return $this->successResponse(
            array_map(fn ($r) => $r->toArray(), $results),
        );
    }

    /**
     * Список категорий маркетплейса
     */
    public function categories(string $marketplace): JsonResponse
    {
        $categories = MarketplaceCategory::forMarketplace($marketplace)
            ->whereNull('parent_id')
            ->orderBy('name')
            ->get(['id', 'marketplace', 'category_id', 'name']);

        return $this->successResponse($categories);
    }

    /**
     * Комиссии маркетплейса
     */
    public function commissions(string $marketplace): JsonResponse
    {
        $commissions = MarketplaceCommission::forMarketplace($marketplace)
            ->active()
            ->with('category:id,name')
            ->get();

        return $this->successResponse($commissions);
    }
}
