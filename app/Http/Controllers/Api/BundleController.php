<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\Products\BundleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API контроллер для управления комплектами
 */
final class BundleController extends Controller
{
    public function __construct(
        private readonly BundleService $bundleService,
    ) {}

    /**
     * Список комплектов с остатками
     */
    public function index(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $bundles = $this->bundleService->getBundlesWithStock($companyId, [
            'search' => $request->input('search'),
            'category_id' => $request->input('category_id'),
            'per_page' => $request->input('per_page', 20),
        ]);

        return response()->json($bundles);
    }

    /**
     * Получить комплект с деталями
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->company_id)
            ->where('is_bundle', true)
            ->findOrFail($id);

        $bundle = $this->bundleService->getBundleDetail($product);

        return response()->json(['data' => $bundle]);
    }

    /**
     * Создать комплект
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'article' => 'required|string|max:100',
            'brand_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'description_short' => 'nullable|string',
            'is_active' => 'boolean',
            'items' => 'required|array|min:2',
            'items.*.component_variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $data = $request->all();
        $data['company_id'] = $request->user()->company_id;
        $data['created_by'] = $request->user()->id;

        $bundle = $this->bundleService->createBundle($data);

        return response()->json(['data' => $bundle], 201);
    }

    /**
     * Обновить комплект
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->company_id)
            ->where('is_bundle', true)
            ->findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'article' => 'sometimes|string|max:100',
            'brand_name' => 'nullable|string|max:255',
            'category_id' => 'nullable|integer|exists:product_categories,id',
            'description_short' => 'nullable|string',
            'is_active' => 'boolean',
            'items' => 'sometimes|array|min:2',
            'items.*.component_variant_id' => 'required_with:items|integer|exists:product_variants,id',
            'items.*.quantity' => 'required_with:items|integer|min:1',
        ]);

        $bundle = $this->bundleService->updateBundle($product, $request->all());

        return response()->json(['data' => $bundle]);
    }

    /**
     * Удалить (архивировать) комплект
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->company_id)
            ->where('is_bundle', true)
            ->findOrFail($id);

        $this->bundleService->deleteBundle($product);

        return response()->json(['message' => 'Комплект удалён']);
    }

    /**
     * Поиск вариантов для добавления в комплект
     */
    public function searchVariants(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'required|string|min:1',
        ]);

        $variants = $this->bundleService->searchComponentVariants(
            $request->user()->company_id,
            $request->input('search')
        );

        return response()->json(['data' => $variants]);
    }

    /**
     * Списать остатки комплекта (ручная продажа)
     */
    public function deductStock(Request $request, int $id): JsonResponse
    {
        $product = Product::where('company_id', $request->user()->company_id)
            ->where('is_bundle', true)
            ->findOrFail($id);

        $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $qty = (int) $request->input('quantity');
        $bundleStock = $product->calculateBundleStock();

        if ($qty > $bundleStock) {
            return response()->json([
                'message' => "Недостаточно остатков. Доступно: {$bundleStock} шт.",
            ], 422);
        }

        $results = $product->deductBundleStock($qty);

        return response()->json([
            'message' => "Списано {$qty} комплект(ов)",
            'deductions' => $results,
            'new_bundle_stock' => $product->calculateBundleStock(),
        ]);
    }
}
