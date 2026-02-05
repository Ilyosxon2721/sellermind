<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Services\PromotionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PromotionController extends Controller
{
    public function __construct(
        protected PromotionService $promotionService
    ) {}

    /**
     * Получить все акции компании.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:active,expired,upcoming'],
            'is_automatic' => ['nullable', 'boolean'],
        ]);

        $companyId = $validated['company_id'] ?? Auth::user()->companies()->first()?->id;

        if (! $companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $query = Promotion::where('company_id', $companyId)
            ->with(['creator:id,name', 'promotionProducts']);

        // Фильтры
        if (isset($validated['status'])) {
            if ($validated['status'] === 'active') {
                $query->active();
            } elseif ($validated['status'] === 'expired') {
                $query->where('end_date', '<', now());
            } elseif ($validated['status'] === 'upcoming') {
                $query->where('start_date', '>', now());
            }
        }

        if (array_key_exists('is_automatic', $validated) && $validated['is_automatic'] !== null) {
            $query->where('is_automatic', (bool) $validated['is_automatic']);
        }

        $promotions = $query->orderByDesc('created_at')->paginate(20);

        // Добавить статистику к каждой акции
        $promotions->getCollection()->transform(function ($promotion) {
            $promotion->stats = $this->promotionService->getPromotionStats($promotion);
            $promotion->is_currently_active = $promotion->isCurrentlyActive();
            $promotion->days_until_expiration = $promotion->getDaysUntilExpiration();

            return $promotion;
        });

        return response()->json($promotions);
    }

    /**
     * Get a single promotion.
     */
    public function show(Promotion $promotion): JsonResponse
    {
        $this->authorizeCompanyAccess($promotion->company_id);

        $promotion->load(['creator:id,name', 'promotionProducts.variant.product']);
        $promotion->stats = $this->promotionService->getPromotionStats($promotion);
        $promotion->is_currently_active = $promotion->isCurrentlyActive();
        $promotion->days_until_expiration = $promotion->getDaysUntilExpiration();

        return response()->json($promotion);
    }

    /**
     * Create a new promotion.
     */
    public function store(Request $request): JsonResponse
    {
        $companyId = $request->input('company_id') ?? Auth::user()->companies()->first()?->id;

        if (! $companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorizeCompanyAccess($companyId);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'is_active' => 'sometimes|boolean',
            'product_variant_ids' => 'required|array|min:1',
            'product_variant_ids.*' => 'exists:product_variants,id',
        ]);

        DB::beginTransaction();

        try {
            $promotion = Promotion::create([
                'company_id' => $companyId,
                'created_by' => Auth::id(),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => $validated['type'],
                'discount_value' => $validated['discount_value'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => $validated['is_active'] ?? true,
                'is_automatic' => false,
                'notify_before_expiry' => true,
                'notify_days_before' => 3,
            ]);

            // Attach products
            foreach ($validated['product_variant_ids'] as $variantId) {
                $variant = \App\Models\ProductVariant::find($variantId);
                if (! $variant) {
                    continue;
                }

                $originalPrice = (float) $variant->price;
                $discountedPrice = $promotion->calculateDiscountedPrice($originalPrice);
                $discountAmount = $promotion->calculateDiscountAmount($originalPrice);

                $promotion->products()->attach($variantId, [
                    'original_price' => $originalPrice,
                    'discounted_price' => $discountedPrice,
                    'discount_amount' => $discountAmount,
                    'stock_at_promotion_start' => $variant->stock_default,
                ]);
            }

            // Update products count
            $promotion->update(['products_count' => count($validated['product_variant_ids'])]);

            DB::commit();

            $promotion->load(['promotionProducts.variant.product']);

            return response()->json($promotion, 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a promotion.
     */
    public function update(Request $request, Promotion $promotion): JsonResponse
    {
        $this->authorizeCompanyAccess($promotion->company_id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:percentage,fixed_amount',
            'discount_value' => 'sometimes|numeric|min:0',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after:start_date',
            'is_active' => 'sometimes|boolean',
        ]);

        $promotion->update($validated);

        return response()->json($promotion);
    }

    /**
     * Delete a promotion.
     */
    public function destroy(Promotion $promotion): JsonResponse
    {
        $this->authorizeCompanyAccess($promotion->company_id);

        // Restore original prices before deleting
        $this->promotionService->removePromotion($promotion);

        $promotion->delete();

        return response()->json(['message' => 'Promotion deleted']);
    }

    /**
     * Apply promotion (update product prices).
     */
    public function apply(Promotion $promotion): JsonResponse
    {
        $this->authorizeCompanyAccess($promotion->company_id);

        $count = $this->promotionService->applyPromotion($promotion);

        $promotion->update(['is_active' => true]);

        return response()->json([
            'message' => "Promotion applied to {$count} products",
            'products_updated' => $count,
        ]);
    }

    /**
     * Remove promotion (restore original prices).
     */
    public function remove(Promotion $promotion): JsonResponse
    {
        $this->authorizeCompanyAccess($promotion->company_id);

        $count = $this->promotionService->removePromotion($promotion);

        $promotion->update(['is_active' => false]);

        return response()->json([
            'message' => "Promotion removed from {$count} products",
            'products_updated' => $count,
        ]);
    }

    /**
     * Обнаружить медленно продающиеся товары.
     */
    public function detectSlowMoving(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'min_days_no_sale' => ['sometimes', 'integer', 'min:1'],
            'min_stock' => ['sometimes', 'integer', 'min:1'],
            'min_price' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $companyId = $validated['company_id'] ?? Auth::user()->companies()->first()?->id;

        if (! $companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorizeCompanyAccess($companyId);

        $slowMoving = $this->promotionService->detectSlowMovingProducts(
            $companyId,
            $validated
        );

        return response()->json([
            'count' => $slowMoving->count(),
            'products' => $slowMoving->values()->all(),
        ]);
    }

    /**
     * Создать автоматическую акцию из медленно продающихся товаров.
     */
    public function createAutomatic(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => ['nullable', 'integer'],
            'min_days_no_sale' => ['sometimes', 'integer', 'min:1'],
            'min_stock' => ['sometimes', 'integer', 'min:1'],
            'duration_days' => ['sometimes', 'integer', 'min:1', 'max:365'],
            'max_discount' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'apply_immediately' => ['sometimes', 'boolean'],
        ]);

        $companyId = $validated['company_id'] ?? Auth::user()->companies()->first()?->id;

        if (! $companyId) {
            return response()->json(['error' => 'Company not found'], 404);
        }

        $this->authorizeCompanyAccess($companyId);

        // Detect slow-moving products
        $slowMoving = $this->promotionService->detectSlowMovingProducts(
            $companyId,
            $validated
        );

        if ($slowMoving->isEmpty()) {
            return response()->json([
                'message' => 'No slow-moving products found',
                'promotion' => null,
            ]);
        }

        // Create automatic promotion
        $company = \App\Models\Company::find($companyId);
        $promotion = $this->promotionService->createAutomaticPromotion(
            $company,
            $slowMoving->toArray(),
            [
                'duration_days' => $validated['duration_days'] ?? 30,
                'max_discount' => $validated['max_discount'] ?? 50,
                'user_id' => Auth::id(),
            ]
        );

        // Apply immediately if requested
        if ($validated['apply_immediately'] ?? false) {
            $this->promotionService->applyPromotion($promotion);
        }

        $promotion->load(['promotionProducts.variant.product']);
        $promotion->stats = $this->promotionService->getPromotionStats($promotion);

        return response()->json([
            'message' => "Automatic promotion created with {$slowMoving->count()} products",
            'promotion' => $promotion,
        ], 201);
    }

    /**
     * Get promotion statistics.
     */
    public function stats(Promotion $promotion): JsonResponse
    {
        $this->authorizeCompanyAccess($promotion->company_id);

        $stats = $this->promotionService->getPromotionStats($promotion);

        return response()->json($stats);
    }

    /**
     * Authorize company access.
     */
    protected function authorizeCompanyAccess(int $companyId): void
    {
        $user = Auth::user();
        if (! $user->hasCompanyAccess($companyId)) {
            abort(403, 'Unauthorized access to company');
        }
    }
}
