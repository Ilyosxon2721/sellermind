<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\Finance\FinanceSettings;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

final class PurchasePriceController extends Controller
{
    use HasPaginatedResponse;

    /**
     * Список товаров с вариантами и закупочными ценами
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;
        $perPage = $this->getPerPage($request, 20);

        $query = Product::query()
            ->forCompany($companyId)
            ->with([
                'mainImage',
                'variants' => fn ($q) => $q->where('is_deleted', false)
                    ->select(['id', 'product_id', 'company_id', 'sku', 'barcode', 'article_suffix', 'option_values_summary', 'purchase_price', 'purchase_price_currency', 'is_active']),
            ])
            ->withCount('variants');

        if ($request->filled('search')) {
            $search = $this->escapeLike($request->get('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%")
                    ->orWhereHas('variants', fn ($vq) => $vq->where('sku', 'like', "%{$search}%"));
            });
        }

        if ($request->boolean('no_cost')) {
            $query->whereHas('variants', fn ($q) => $q->where('is_deleted', false)->where(function ($vq) {
                $vq->whereNull('purchase_price')->orWhere('purchase_price', 0);
            }));
        }

        $products = $query->orderByDesc('updated_at')->paginate($perPage);

        $financeSettings = FinanceSettings::getForCompany($companyId);

        $items = $products->getCollection()->map(function (Product $product) use ($financeSettings) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'article' => $product->article,
                'image_url' => $product->mainImage?->file_path,
                'variants' => $product->variants->map(function (ProductVariant $variant) use ($financeSettings) {
                    return [
                        'id' => $variant->id,
                        'sku' => $variant->sku,
                        'barcode' => $variant->barcode,
                        'option_values_summary' => $variant->option_values_summary,
                        'purchase_price' => $variant->purchase_price ? (float) $variant->purchase_price : null,
                        'purchase_price_currency' => $variant->purchase_price_currency ?? 'UZS',
                        'purchase_price_base' => $variant->getPurchasePriceInBase($financeSettings),
                        'is_active' => (bool) $variant->is_active,
                    ];
                })->values(),
            ];
        });

        // Статистика
        $totalVariants = ProductVariant::where('company_id', $companyId)
            ->where('is_deleted', false)->count();
        $withPrice = ProductVariant::where('company_id', $companyId)
            ->where('is_deleted', false)
            ->where('purchase_price', '>', 0)->count();

        return response()->json([
            'data' => $items,
            'meta' => $this->paginationMeta($products),
            'stats' => [
                'total' => $totalVariants,
                'with_price' => $withPrice,
                'without_price' => $totalVariants - $withPrice,
            ],
        ]);
    }

    /**
     * Обновить закупочную цену одного варианта
     */
    public function updateVariant(Request $request, int $variantId): JsonResponse
    {
        $variant = ProductVariant::findOrFail($variantId);

        if ($variant->company_id !== $request->user()->company_id) {
            abort(403);
        }

        $validated = $request->validate([
            'purchase_price' => 'required|numeric|min:0',
            'purchase_price_currency' => 'sometimes|string|in:UZS,USD,RUB,EUR',
        ]);

        $variant->update($validated);

        $financeSettings = FinanceSettings::getForCompany($variant->company_id);

        return response()->json([
            'success' => true,
            'variant' => [
                'id' => $variant->id,
                'purchase_price' => (float) $variant->purchase_price,
                'purchase_price_currency' => $variant->purchase_price_currency,
                'purchase_price_base' => $variant->getPurchasePriceInBase($financeSettings),
            ],
        ]);
    }

    /**
     * Массовое обновление закупочных цен
     */
    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'required|integer|exists:product_variants,id',
            'variants.*.purchase_price' => 'required|numeric|min:0',
            'variants.*.purchase_price_currency' => 'sometimes|string|in:UZS,USD,RUB,EUR',
        ]);

        $companyId = $request->user()->company_id;
        $financeSettings = FinanceSettings::getForCompany($companyId);
        $updatedVariants = [];

        foreach ($validated['variants'] as $data) {
            $variant = ProductVariant::where('id', $data['id'])
                ->where('company_id', $companyId)
                ->first();

            if ($variant) {
                $variant->update(Arr::only($data, ['purchase_price', 'purchase_price_currency']));
                $updatedVariants[] = [
                    'id' => $variant->id,
                    'purchase_price' => (float) $variant->purchase_price,
                    'purchase_price_currency' => $variant->purchase_price_currency,
                    'purchase_price_base' => $variant->getPurchasePriceInBase($financeSettings),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'updated' => count($updatedVariants),
            'variants' => $updatedVariants,
        ]);
    }
}
