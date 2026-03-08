<?php

declare(strict_types=1);

namespace App\Http\Controllers\Store;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\Product;
use App\Models\Store\Store;
use App\Models\Store\StoreCategory;
use App\Models\Store\StoreProduct;
use App\Support\ApiResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Управление каталогом магазина (товары и категории)
 */
final class StoreCatalogController extends Controller
{
    use ApiResponder, HasCompanyScope;

    // ==================
    // Товары
    // ==================

    /**
     * Список товаров магазина
     */
    public function productIndex(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $products = StoreProduct::where('store_id', $store->id)
            ->with('product:id,name,article,price,image')
            ->orderBy('position')
            ->get();

        return $this->successResponse($products);
    }

    /**
     * Добавить товары в магазин (массово)
     */
    public function productStore(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);
        $companyId = $this->getCompanyId();

        $data = $request->validate([
            'product_ids' => ['required', 'array', 'min:1'],
            'product_ids.*' => ['required', 'integer'],
        ]);

        // Проверяем что товары принадлежат компании
        $validProductIds = Product::where('company_id', $companyId)
            ->whereIn('id', $data['product_ids'])
            ->pluck('id');

        // Исключаем уже добавленные товары
        $existingProductIds = StoreProduct::where('store_id', $store->id)
            ->whereIn('product_id', $validProductIds)
            ->pluck('product_id');

        $newProductIds = $validProductIds->diff($existingProductIds);

        $maxPosition = StoreProduct::where('store_id', $store->id)->max('position') ?? 0;
        $created = [];

        foreach ($newProductIds as $productId) {
            $maxPosition++;
            $created[] = StoreProduct::create([
                'store_id' => $store->id,
                'product_id' => $productId,
                'is_visible' => true,
                'position' => $maxPosition,
            ]);
        }

        return $this->successResponse([
            'added' => count($created),
            'skipped' => $existingProductIds->count(),
            'products' => $created,
        ])->setStatusCode(201);
    }

    /**
     * Обновить товар магазина (кастомные поля)
     */
    public function productUpdate(int $storeId, int $storeProductId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $storeProduct = StoreProduct::where('store_id', $store->id)->findOrFail($storeProductId);

        $data = $request->validate([
            'custom_name' => ['nullable', 'string', 'max:255'],
            'custom_description' => ['nullable', 'string'],
            'custom_price' => ['nullable', 'numeric', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'is_featured' => ['sometimes', 'boolean'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $storeProduct->update($data);

        return $this->successResponse($storeProduct->load('product:id,name,article,price,image'));
    }

    /**
     * Удалить товар из магазина
     */
    public function productDestroy(int $storeId, int $storeProductId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $storeProduct = StoreProduct::where('store_id', $store->id)->findOrFail($storeProductId);
        $storeProduct->delete();

        return $this->successResponse(['message' => 'Товар убран из магазина']);
    }

    /**
     * Синхронизировать все товары компании в магазин
     */
    public function productSync(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);
        $companyId = $this->getCompanyId();

        $companyProductIds = Product::where('company_id', $companyId)->pluck('id');

        $existingProductIds = StoreProduct::where('store_id', $store->id)
            ->pluck('product_id');

        $newProductIds = $companyProductIds->diff($existingProductIds);

        $maxPosition = StoreProduct::where('store_id', $store->id)->max('position') ?? 0;
        $addedCount = 0;

        foreach ($newProductIds as $productId) {
            $maxPosition++;
            StoreProduct::create([
                'store_id' => $store->id,
                'product_id' => $productId,
                'is_visible' => true,
                'position' => $maxPosition,
            ]);
            $addedCount++;
        }

        return $this->successResponse([
            'added' => $addedCount,
            'total' => StoreProduct::where('store_id', $store->id)->count(),
        ]);
    }

    // ==================
    // Категории
    // ==================

    /**
     * Список категорий магазина
     */
    public function categoryIndex(int $storeId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $categories = StoreCategory::where('store_id', $store->id)
            ->with('category:id,name')
            ->orderBy('position')
            ->get();

        return $this->successResponse($categories);
    }

    /**
     * Добавить категорию в магазин
     */
    public function categoryStore(int $storeId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $data = $request->validate([
            'category_id' => ['nullable', 'integer', 'exists:product_categories,id'],
            'custom_name' => ['required_without:category_id', 'string', 'max:255'],
            'custom_description' => ['nullable', 'string'],
            'custom_image' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'show_in_menu' => ['sometimes', 'boolean'],
        ]);

        $data['store_id'] = $store->id;

        if (! isset($data['position'])) {
            $data['position'] = StoreCategory::where('store_id', $store->id)->max('position') + 1;
        }

        $category = StoreCategory::create($data);

        return $this->successResponse($category->load('category:id,name'))->setStatusCode(201);
    }

    /**
     * Обновить категорию магазина
     */
    public function categoryUpdate(int $storeId, int $storeCategoryId, Request $request): JsonResponse
    {
        $store = $this->findStore($storeId);

        $storeCategory = StoreCategory::where('store_id', $store->id)->findOrFail($storeCategoryId);

        $data = $request->validate([
            'custom_name' => ['nullable', 'string', 'max:255'],
            'custom_description' => ['nullable', 'string'],
            'custom_image' => ['nullable', 'string', 'max:500'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['sometimes', 'boolean'],
            'show_in_menu' => ['sometimes', 'boolean'],
        ]);

        $storeCategory->update($data);

        return $this->successResponse($storeCategory->load('category:id,name'));
    }

    /**
     * Удалить категорию из магазина
     */
    public function categoryDestroy(int $storeId, int $storeCategoryId): JsonResponse
    {
        $store = $this->findStore($storeId);

        $storeCategory = StoreCategory::where('store_id', $store->id)->findOrFail($storeCategoryId);
        $storeCategory->delete();

        return $this->successResponse(['message' => 'Категория убрана из магазина']);
    }

    /**
     * Найти магазин текущей компании
     */
    private function findStore(int $storeId): Store
    {
        $companyId = $this->getCompanyId();

        return Store::where('company_id', $companyId)->findOrFail($storeId);
    }
}
