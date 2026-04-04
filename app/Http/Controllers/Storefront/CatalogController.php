<?php

declare(strict_types=1);

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Storefront\Traits\StorefrontHelpers;
use App\Models\Store\StoreProduct;
use App\Models\Store\StoreReview;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Публичный каталог товаров магазина
 */
final class CatalogController extends Controller
{
    use StorefrontHelpers;

    /**
     * Каталог товаров с фильтрацией и сортировкой
     *
     * GET /store/{slug}/catalog
     *
     * Параметры: category_id, search, sort (price_asc, price_desc, newest), page, per_page
     */
    public function index(string $slug, Request $request): View
    {
        $store = $this->getPublishedStore($slug);

        $perPage = min((int) $request->input('per_page', $store->theme->products_per_page ?? 12), 48);

        $query = StoreProduct::where('store_id', $store->id)
            ->where('is_visible', true)
            ->with(['product.mainImage', 'product.variants']);

        // Фильтр по категории
        if ($categoryId = $request->input('category')) {
            $query->whereHas('product', function ($q) use ($categoryId) {
                $q->where('category_id', (int) $categoryId);
            });
        }

        // Поиск по названию товара
        if ($search = $request->input('search')) {
            $escapedSearch = $this->escapeLike($search);
            $query->where(function ($q) use ($escapedSearch) {
                $q->where('custom_name', 'like', "%{$escapedSearch}%")
                    ->orWhereHas('product', function ($productQuery) use ($escapedSearch) {
                        $productQuery->where('name', 'like', "%{$escapedSearch}%")
                            ->orWhere('article', 'like', "%{$escapedSearch}%");
                    });
            });
        }

        // Фильтр по цене (через custom_price или price_default первого варианта)
        if ($priceMin = $request->input('price_min')) {
            $query->where(function ($q) use ($priceMin) {
                $q->where('custom_price', '>=', (float) $priceMin)
                    ->orWhere(function ($q2) use ($priceMin) {
                        $q2->whereNull('custom_price')
                            ->whereHas('product.variants', function ($vq) use ($priceMin) {
                                $vq->where('price_default', '>=', (float) $priceMin);
                            });
                    });
            });
        }

        if ($priceMax = $request->input('price_max')) {
            $query->where(function ($q) use ($priceMax) {
                $q->where('custom_price', '<=', (float) $priceMax)
                    ->orWhere(function ($q2) use ($priceMax) {
                        $q2->whereNull('custom_price')
                            ->whereHas('product.variants', function ($vq) use ($priceMax) {
                                $vq->where('price_default', '<=', (float) $priceMax);
                            });
                    });
            });
        }

        // Сортировка
        $sort = $request->input('sort', 'position');
        $query = match ($sort) {
            'price_asc' => $query->orderByRaw('COALESCE(custom_price, 0) ASC')->orderBy('position'),
            'price_desc' => $query->orderByRaw('COALESCE(custom_price, 0) DESC')->orderBy('position'),
            'newest' => $query->orderByDesc('created_at'),
            'popular' => $query->orderByDesc('is_featured')->orderBy('position'),
            default => $query->orderBy('position'),
        };

        $products = $query->paginate($perPage)->withQueryString();

        // Batch-загрузка складских остатков (1 SQL-запрос)
        \App\Models\Store\StoreProduct::loadWarehouseStocks(collect($products->items()));

        $categories = Cache::remember(
            "storefront:categories:{$store->id}",
            300,
            fn () => $store->visibleCategories()->with('category')->get()
        );

        $this->trackPageView($store);

        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.catalog", compact(
            'store',
            'products',
            'categories',
        ));
    }

    /**
     * Страница отдельного товара
     *
     * GET /store/{slug}/product/{productId}
     */
    public function show(string $slug, int $productId): View
    {
        $store = $this->getPublishedStore($slug);

        $storeProduct = StoreProduct::where('store_id', $store->id)
            ->where('is_visible', true)
            ->where('id', $productId)
            ->with([
                'product.mainImage',
                'product.images',
                'product.variants' => fn ($q) => $q->where('is_active', true)->where('is_deleted', false),
                'product.variants.optionValues.option',
                'product.options.values',
            ])
            ->firstOrFail();

        // Подготовить JSON-данные вариантов для Alpine.js
        $variantsJson = $this->buildVariantsJson($storeProduct);

        // Похожие товары: по той же категории, иначе — рандомные из магазина
        $categoryId = $storeProduct->product?->category_id;

        $relatedQuery = StoreProduct::where('store_id', $store->id)
            ->where('is_visible', true)
            ->where('id', '!=', $storeProduct->id)
            ->with(['product.mainImage', 'product.variants']);

        if ($categoryId) {
            $relatedQuery->whereHas('product', fn ($q) => $q->where('category_id', $categoryId));
        }

        $relatedProducts = $relatedQuery->inRandomOrder()->limit(8)->get();

        // Если по категории ничего не найдено — берём рандомные товары из магазина
        if ($relatedProducts->isEmpty()) {
            $relatedProducts = StoreProduct::where('store_id', $store->id)
                ->where('is_visible', true)
                ->where('id', '!=', $storeProduct->id)
                ->with(['product.mainImage', 'product.variants'])
                ->inRandomOrder()
                ->limit(8)
                ->get();
        }

        // Batch-загрузка складских остатков
        StoreProduct::loadWarehouseStocks($relatedProducts);

        // Отзывы на товар (первые 5 + статистика)
        $reviews = StoreReview::where('store_product_id', $storeProduct->id)
            ->where('is_approved', true)
            ->latest()
            ->limit(5)
            ->get();

        $reviewStats = StoreReview::where('store_product_id', $storeProduct->id)
            ->where('is_approved', true)
            ->selectRaw('COUNT(*) as total, AVG(rating) as avg_rating')
            ->first();

        $this->trackPageView($store);

        $template = $store->theme?->resolvedTemplate() ?? 'default';

        return view("storefront.themes.{$template}.product", compact('store', 'storeProduct', 'variantsJson', 'relatedProducts', 'reviews', 'reviewStats'));
    }

    /**
     * Сформировать JSON-структуру вариантов товара для фронтенда
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildVariantsJson(StoreProduct $storeProduct): array
    {
        $product = $storeProduct->product;

        if ($product->variants->isEmpty()) {
            return [];
        }

        return $product->variants->map(function ($variant) {
            $optionValues = $variant->optionValues->map(function ($ov) {
                return [
                    'option_id'   => $ov->product_option_id,
                    'option_name' => $ov->option?->name ?? '',
                    'option_code' => $ov->option?->code ?? '',
                    'value_id'    => $ov->id,
                    'value'       => $ov->value,
                    'color_hex'   => $ov->color_hex,
                ];
            })->values()->all();

            return [
                'id'                  => $variant->id,
                'sku'                 => $variant->sku,
                'name'                => $variant->option_values_summary ?: $variant->sku,
                'price'               => (float) ($variant->price_default ?? 0),
                'old_price'           => $variant->old_price_default ? (float) $variant->old_price_default : null,
                'stock'               => $variant->stock_default ?? 0,
                'option_values'       => $optionValues,
            ];
        })->values()->all();
    }

    /**
     * Автодополнение поиска — возвращает JSON с результатами
     *
     * GET /store/{slug}/api/search?q=...
     */
    public function search(string $slug, Request $request): JsonResponse
    {
        $query = trim((string) $request->input('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        $store = $this->getPublishedStore($slug);

        $escaped = $this->escapeLike($query);

        $storeProducts = StoreProduct::where('store_id', $store->id)
            ->where('is_visible', true)
            ->where(function ($q) use ($escaped) {
                $q->where('custom_name', 'like', "%{$escaped}%")
                    ->orWhereHas('product', function ($pq) use ($escaped) {
                        $pq->where('name', 'like', "%{$escaped}%")
                            ->orWhere('article', 'like', "%{$escaped}%");
                    });
            })
            ->with(['product.mainImage', 'product.variants'])
            ->limit(6)
            ->get();

        $results = $storeProducts->map(function (StoreProduct $sp) use ($slug): array {
            $image = $sp->product?->mainImage?->url;

            return [
                'id'    => $sp->id,
                'name'  => $sp->getDisplayName(),
                'price' => $sp->getDisplayPrice(),
                'image' => $image,
                'url'   => "/store/{$slug}/product/{$sp->id}",
            ];
        });

        return response()->json(['results' => $results]);
    }
}
