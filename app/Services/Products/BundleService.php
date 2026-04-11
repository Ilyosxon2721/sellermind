<?php

declare(strict_types=1);

namespace App\Services\Products;

use App\Models\Product;
use App\Models\ProductBundleItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Сервис для работы с комплектами товаров.
 *
 * Комплект — это Product(is_bundle=true) с одним виртуальным ProductVariant
 * (is_bundle_variant=true), в котором хранятся SKU, штрихкод, цена и т.п.
 * Этот вариант можно привязывать к карточкам маркетплейсов через
 * существующий VariantMarketplaceLink. Остаток комплекта считается
 * динамически из остатков компонентов.
 */
final class BundleService
{
    /**
     * Создать комплект (Product + автоматический bundle-variant).
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
                'description_full' => $data['description_full'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'is_bundle' => true,
                'created_by' => $data['created_by'] ?? auth()->id(),
            ]);

            // Создаём виртуальный вариант комплекта
            $this->createOrUpdateBundleVariant($product, $data);

            // Добавляем компоненты
            if (! empty($data['items'])) {
                $this->syncBundleItems($product, $data['items']);
            }

            // Пересчитываем себестоимость и остаток после добавления компонентов
            $this->refreshBundleVariantDerivedFields($product);

            Log::info('Bundle created', [
                'bundle_id' => $product->id,
                'name' => $product->name,
                'items_count' => count($data['items'] ?? []),
            ]);

            return $product->load(['bundleItems.componentVariant.product', 'bundleVariant']);
        });
    }

    /**
     * Обновить комплект.
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
                'description_full' => $data['description_full'] ?? null,
                'is_active' => $data['is_active'] ?? null,
                'updated_by' => auth()->id(),
            ], fn ($v) => $v !== null));

            // Обновляем или создаём bundle-variant
            $this->createOrUpdateBundleVariant($product, $data);

            // Обновляем компоненты
            if (isset($data['items'])) {
                $this->syncBundleItems($product, $data['items']);
            }

            // Пересчитываем derived-поля
            $this->refreshBundleVariantDerivedFields($product);

            Log::info('Bundle updated', [
                'bundle_id' => $product->id,
                'name' => $product->name,
            ]);

            return $product->load(['bundleItems.componentVariant.product', 'bundleVariant']);
        });
    }

    /**
     * Создать или обновить виртуальный вариант комплекта.
     *
     * Этот вариант держит пользовательские поля: sku, barcode, цену, закупочную цену
     * (себестоимость пересчитывается автоматически, но пользователь тоже может
     * указать её вручную — тогда его значение будет сохранено).
     */
    private function createOrUpdateBundleVariant(Product $product, array $data): ProductVariant
    {
        $variant = $product->bundleVariant()->first();

        $attributes = array_filter([
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'price_default' => isset($data['price_default']) ? (float) $data['price_default'] : null,
            'old_price_default' => isset($data['old_price_default']) ? (float) $data['old_price_default'] : null,
            'option_values_summary' => $data['option_values_summary'] ?? null,
        ], fn ($v) => $v !== null);

        if ($variant) {
            $variant->fill($attributes);
            $variant->save();

            return $variant;
        }

        return ProductVariant::create(array_merge([
            'company_id' => $product->company_id,
            'product_id' => $product->id,
            'sku' => $data['sku'] ?? $product->article,
            'barcode' => $data['barcode'] ?? null,
            'price_default' => isset($data['price_default']) ? (float) $data['price_default'] : null,
            'purchase_price' => 0, // обновится в refreshBundleVariantDerivedFields()
            'purchase_price_currency' => 'UZS',
            'stock_default' => 0, // обновится в refreshBundleVariantDerivedFields()
            'is_active' => true,
            'is_deleted' => false,
            'is_bundle_variant' => true,
        ], $attributes));
    }

    /**
     * Пересчитать derived-поля bundle-варианта: себестоимость и остаток.
     *
     * Себестоимость = сумма purchase_price компонентов × quantity.
     * Остаток = MIN(stock_компонента / quantity).
     *
     * Обновление stock_default триггерит ProductVariantObserver, который
     * в свою очередь запускает синхронизацию с маркетплейсами.
     */
    public function refreshBundleVariantDerivedFields(Product $product): void
    {
        $variant = $product->bundleVariant()->first();
        if (! $variant) {
            return;
        }

        $product->loadMissing('bundleItems.componentVariant');

        $variant->purchase_price = $product->calculateBundleCost();
        $variant->stock_default = $product->calculateBundleStock();
        $variant->save();
    }

    /**
     * Синхронизировать компоненты комплекта.
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
     * Удалить комплект (архивирование).
     */
    public function deleteBundle(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $product->bundleItems()->delete();
            // Архивируем bundle-variant, чтобы не светился в списках остатков
            if ($variant = $product->bundleVariant()->first()) {
                $variant->update(['is_active' => false, 'is_deleted' => true]);
            }
            $product->update(['is_archived' => true]);

            Log::info('Bundle archived', ['bundle_id' => $product->id]);

            return true;
        });
    }

    /**
     * Получить список комплектов с остатками.
     */
    public function getBundlesWithStock(int $companyId, array $filters = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = Product::query()
            ->where('company_id', $companyId)
            ->where('is_bundle', true)
            ->where('is_archived', false)
            ->with([
                'bundleItems.componentVariant.product',
                'bundleVariant.marketplaceLinks.account',
                'mainImage',
                'category',
            ]);

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

        // Добавляем расчёт остатков и себестоимости в JSON через accessor
        $bundles->getCollection()->each(function (Product $bundle) {
            $bundle->append(['bundle_stock', 'bundle_cost']);
        });

        return $bundles;
    }

    /**
     * Получить полную информацию о комплекте.
     */
    public function getBundleDetail(Product $product): Product
    {
        $product->load([
            'bundleItems.componentVariant.product',
            'bundleVariant.marketplaceLinks.account',
            'bundleVariant.marketplaceLinks.marketplaceProduct',
            'mainImage',
            'category',
        ]);
        $product->append(['bundle_stock', 'bundle_cost']);

        $settings = \App\Models\Finance\FinanceSettings::getForCompany($product->company_id);

        // Добавляем остатки и себестоимость в базовой валюте у каждого компонента
        foreach ($product->bundleItems as $item) {
            $cv = $item->componentVariant;
            $item->component_stock = $cv?->getCurrentStock() ?? 0;
            $item->available_kits = $item->getAvailableKits();
            if ($cv) {
                $cv->purchase_price_base = $cv->getPurchasePriceInBase($settings);
            }
        }

        return $product;
    }

    /**
     * Получить варианты для поиска компонентов.
     *
     * Возвращает закупочную цену в оригинальной валюте и её конвертированное
     * значение в UZS (purchase_price_base), чтобы фронт мог корректно
     * считать суммарную себестоимость комплекта.
     */
    public function searchComponentVariants(int $companyId, string $search): \Illuminate\Support\Collection
    {
        $variants = ProductVariant::query()
            ->with('product:id,name,is_bundle')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_deleted', false)
            ->where('is_bundle_variant', false) // не даём вкладывать комплект в комплект
            ->whereHas('product', fn ($q) => $q->where('is_bundle', false)->whereNull('deleted_at'))
            ->where(function ($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                    ->orWhere('barcode', 'like', "%{$search}%")
                    ->orWhere('option_values_summary', 'like', "%{$search}%")
                    ->orWhereHas('product', fn ($q2) => $q2->where('name', 'like', "%{$search}%"));
            })
            ->orderBy('sku')
            ->limit(30)
            ->get(['id', 'product_id', 'sku', 'barcode', 'option_values_summary', 'stock_default', 'price_default', 'purchase_price', 'purchase_price_currency']);

        $settings = \App\Models\Finance\FinanceSettings::getForCompany($companyId);

        // Обогащаем каждую запись конвертированной закупочной ценой в UZS
        $variants->each(function (ProductVariant $v) use ($settings) {
            $v->purchase_price_base = $v->getPurchasePriceInBase($settings);
        });

        return $variants;
    }
}
