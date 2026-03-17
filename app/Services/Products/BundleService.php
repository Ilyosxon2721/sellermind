<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductBundleItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с комплектами товаров
 */
final class BundleService
{
    /**
     * Создать комплект
     */
    public function createBundle(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $product = Product::create([
                'company_id' => $data['company_id'],
                'name' => $data['name'],
                'article' => $data['article'],
                'brand_name' => $data['brand_name'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'description_short' => $data['description_short'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_bundle' => true,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            // Добавляем компоненты
            if (! empty($data['items'])) {
                $this->syncBundleItems($product, $data['items']);
            }

            Log::info('Bundle created', [
                'bundle_id' => $product->id,
                'name' => $product->name,
                'items_count' => count($data['items'] ?? []),
            ]);

            return $product->load('bundleItems.componentVariant.product');
        });
    }

    /**
     * Обновить комплект
     */
    public function updateBundle(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $product->update(array_filter([
                'name' => $data['name'] ?? null,
                'article' => $data['article'] ?? null,
                'brand_name' => $data['brand_name'] ?? null,
                'category_id' => $data['category_id'] ?? null,
                'description_short' => $data['description_short'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'updated_by' => auth()->id(),
            ], fn ($v) => $v !== null));

            // Обновляем компоненты
            if (isset($data['items'])) {
                $this->syncBundleItems($product, $data['items']);
            }

            Log::info('Bundle updated', [
                'bundle_id' => $product->id,
                'name' => $product->name,
            ]);

            return $product->load('bundleItems.componentVariant.product');
        });
    }

    /**
     * Синхронизировать компоненты комплекта
     *
     * @param array $items [{component_variant_id: int, quantity: int}, ...]
     */
    public function syncBundleItems(Product $product, array $items): void
    {
        // Удаляем старые компоненты
        $product->bundleItems()->delete();

        // Добавляем новые
        foreach ($items as $item) {
            ProductBundleItem::create([
                'company_id' => $product->company_id,
                'bundle_product_id' => $product->id,
                'component_variant_id' => $item['component_variant_id'],
                'quantity' => $item['quantity'] ?? 1,
            ]);
        }
    }

    /**
     * Удалить комплект (архивирование)
     */
    public function deleteBundle(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $product->bundleItems()->delete();
            $product->update(['is_archived' => true]);

            Log::info('Bundle archived', ['bundle_id' => $product->id]);

            return true;
        });
    }

    /**
     * Получить список комплектов с остатками
     */
    public function getBundlesWithStock(int $companyId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Product::query()
            ->where('company_id', $companyId)
            ->where('is_bundle', true)
            ->where('is_archived', false)
            ->with(['bundleItems.componentVariant.product', 'mainImage', 'category']);

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('article', 'like', "%{$search}%");
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        $bundles = $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 20);

        // Добавляем расчёт остатков в JSON через accessor
        $bundles->getCollection()->each(function (Product $bundle) {
            $bundle->append('bundle_stock');
        });

        return $bundles;
    }

    /**
     * Получить полную информацию о комплекте
     */
    public function getBundleDetail(Product $product): Product
    {
        $product->load(['bundleItems.componentVariant.product', 'mainImage', 'category']);
        $product->append('bundle_stock');

        // Добавляем остатки каждого компонента
        foreach ($product->bundleItems as $item) {
            $item->component_stock = $item->componentVariant->getCurrentStock();
            $item->available_kits = $item->getAvailableKits();
        }

        return $product;
    }

    /**
     * Получить варианты для поиска компонентов
     */
    public function searchComponentVariants(int $companyId, string $search): \Illuminate\Support\Collection
    {
        return ProductVariant::query()
            ->with('product:id,name,is_bundle')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->whereHas('product', fn ($q) => $q->where('is_bundle', false)->whereNull('deleted_at'))
            ->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('option_values_summary', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            })
            ->orderBy('sku')
            ->limit(30)
            ->get(['id', 'product_id', 'sku', 'barcode', 'option_values_summary', 'stock_default', 'price_default']);
    }
}
