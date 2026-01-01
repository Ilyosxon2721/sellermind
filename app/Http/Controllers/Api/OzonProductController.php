<?php
// file: app/Http/Controllers/Api/OzonProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\OzonProduct;
use App\Models\Product;
use App\Models\VariantMarketplaceLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OzonProductController extends Controller
{
    /**
     * List Ozon products with pagination and filters.
     */
    public function index(Request $request, MarketplaceAccount $account): JsonResponse
    {
        // Auth middleware ensures user is logged in
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:processing,moderating,processed,archived,failed_moderation'],
            'visible' => ['nullable', 'boolean'],
            'linked' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:10,200'],
            'sort_by' => ['nullable', 'in:last_synced_at,updated_at,price,stock,name'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        $perPage = $request->integer('per_page', 50);
        $sortBy = $request->input('sort_by', 'last_synced_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = OzonProduct::where('marketplace_account_id', $account->id);

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('status')) {
            $query->status($request->input('status'));
        }

        if ($request->has('visible')) {
            $query->visible($request->boolean('visible'));
        }

        if ($request->has('linked')) {
            if ($request->boolean('linked')) {
                $query->linked();
            } else {
                $query->unlinked();
            }
        }

        $allowedSorts = [
            'last_synced_at' => 'last_synced_at',
            'updated_at' => 'updated_at',
            'price' => 'price',
            'stock' => 'stock',
            'name' => 'name',
        ];
        $sortColumn = $allowedSorts[$sortBy] ?? 'last_synced_at';
        $direction = $sortDir === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortColumn, $direction)
            ->orderBy('id', 'desc');

        $products = $query->paginate($perPage);

        // Get ALL linked variants for these products (grouped by product_id)
        $productIds = collect($products->items())->pluck('id')->toArray();
        $links = VariantMarketplaceLink::whereIn('marketplace_product_id', $productIds)
            ->where('marketplace_account_id', $account->id)
            ->where('marketplace_code', 'ozon')
            ->where('is_active', true)
            ->with(['variant:id,sku,stock_default,option_values_summary', 'variant.product:id,name'])
            ->get()
            ->groupBy('marketplace_product_id');

        $items = collect($products->items())->map(function (OzonProduct $product) use ($links) {
            $data = [
                'id' => $product->id,
                'external_product_id' => $product->external_product_id,
                'external_offer_id' => $product->external_offer_id,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'category_id' => $product->category_id,
                'price' => $product->price,
                'old_price' => $product->old_price,
                'stock' => $product->stock,
                'status' => $product->status,
                'visible' => $product->visible,
                'last_synced_at' => optional($product->last_synced_at)->toIso8601String(),
                'primary_image' => $product->primary_image,
            ];

            // Get all links for this product
            $productLinks = $links->get($product->id, collect());
            
            // Add linked variant info if exists (backward compatibility - first link)
            $firstLink = $productLinks->first();
            if ($firstLink && $firstLink->variant) {
                $data['linked_variant'] = [
                    'id' => $firstLink->variant->id,
                    'sku' => $firstLink->variant->sku,
                    'name' => $firstLink->variant->product?->name,
                    'stock' => $firstLink->variant->stock_default,
                    'options' => $firstLink->variant->option_values_summary,
                ];
            }

            // Add ALL variant links
            $data['variant_links'] = $productLinks->map(function ($link) {
                return [
                    'id' => $link->id,
                    'external_sku_id' => $link->external_sku_id,
                    'variant' => [
                        'id' => $link->variant->id,
                        'sku' => $link->variant->sku,
                        'name' => $link->variant->product?->name,
                        'stock' => $link->variant->stock_default,
                        'options' => $link->variant->option_values_summary,
                    ],
                ];
            })->values()->all();

            return $data;
        })->all();

        return response()->json([
            'products' => $items,
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    /**
     * Suggestions for a local product context
     */
    public function suggestions(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        $data = $request->validate([
            'local_product_id' => ['required', 'integer', 'exists:products,id'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $product = Product::find($data['local_product_id']);
        if (!$product || $product->company_id !== $account->company_id) {
            return response()->json(['message' => 'Товар не принадлежит компании'], 403);
        }

        $limit = $request->integer('limit', 10);

        $query = OzonProduct::where('marketplace_account_id', $account->id);

        // Simple relevance heuristic: match category, SKU, name
        $query->orderByRaw(
            "(name LIKE ?) desc,
             (external_offer_id LIKE ?) desc,
             (barcode LIKE ?) desc,
             last_synced_at desc",
            [
                '%' . ($product->name_internal ?? '') . '%',
                '%' . ($product->sku ?? '') . '%',
                '%' . ($product->barcode ?? '') . '%',
            ]
        );

        $items = $query->limit($limit)->get()->map(fn (OzonProduct $oz) => $this->transformProduct($oz));

        return response()->json([
            'products' => $items,
        ]);
    }

    /**
     * Search Ozon products with local product context
     */
    public function search(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        $data = $request->validate([
            'local_product_id' => ['required', 'integer', 'exists:products,id'],
            'query' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $product = Product::find($data['local_product_id']);
        if (!$product || $product->company_id !== $account->company_id) {
            return response()->json(['message' => 'Товар не принадлежит компании'], 403);
        }

        $limit = $request->integer('limit', 20);
        $queryText = $request->string('query');

        $query = OzonProduct::where('marketplace_account_id', $account->id)
            ->where(function ($q) use ($queryText) {
                $q->where('external_product_id', 'like', "%{$queryText}%")
                    ->orWhere('name', 'like', "%{$queryText}%")
                    ->orWhere('external_offer_id', 'like', "%{$queryText}%")
                    ->orWhere('barcode', 'like', "%{$queryText}%");
            });

        $query->orderByRaw(
            "(name LIKE ?) desc,
             (external_offer_id LIKE ?) desc,
             (barcode LIKE ?) desc,
             last_synced_at desc",
            [
                '%' . ($product->name_internal ?? '') . '%',
                '%' . ($product->sku ?? '') . '%',
                '%' . ($product->barcode ?? '') . '%',
            ]
        );

        $items = $query->limit($limit)->get()->map(fn (OzonProduct $oz) => $this->transformProduct($oz));

        return response()->json([
            'products' => $items,
        ]);
    }

    /**
     * Get single Ozon product with detailed information
     */
    public function show(Request $request, MarketplaceAccount $account, OzonProduct $product): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($product->marketplace_account_id !== $account->id) {
            return response()->json(['message' => 'Товар не принадлежит этому аккаунту'], 403);
        }

        // Load variant links with related data
        $variantLinks = VariantMarketplaceLink::where('marketplace_product_id', $product->id)
            ->where('marketplace_account_id', $account->id)
            ->where('marketplace_code', 'ozon')
            ->where('is_active', true)
            ->with(['variant.product', 'variant'])
            ->get();

        // Parse images if they're stored as JSON string
        $images = $product->images;
        if (is_string($images)) {
            $images = json_decode($images, true);
        }

        $data = [
            'id' => $product->id,
            'external_product_id' => $product->external_product_id,
            'external_offer_id' => $product->external_offer_id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'description' => $product->description,
            'category_id' => $product->category_id,
            'price' => $product->price,
            'old_price' => $product->old_price,
            'premium_price' => $product->premium_price,
            'stock' => $product->stock,
            'status' => $product->status,
            'visible' => $product->visible,
            'vat' => $product->vat,

            // Dimensions and weight
            'width' => $product->width,
            'height' => $product->height,
            'depth' => $product->depth,
            'weight' => $product->weight,

            // Images
            'primary_image' => $product->primary_image,
            'images' => $images ?? [],

            // Timestamps
            'last_synced_at' => optional($product->last_synced_at)->toIso8601String(),
            'created_at' => optional($product->created_at)->toIso8601String(),
            'updated_at' => optional($product->updated_at)->toIso8601String(),

            // Variant links
            'variant_links' => $variantLinks->map(function ($link) {
                return [
                    'id' => $link->id,
                    'external_sku_id' => $link->external_sku_id,
                    'is_active' => $link->is_active,
                    'variant' => [
                        'id' => $link->variant->id,
                        'sku' => $link->variant->sku,
                        'product_name' => $link->variant->product?->name,
                        'stock' => $link->variant->stock_default,
                        'options' => $link->variant->option_values_summary,
                    ],
                ];
            })->values()->all(),

            'is_linked' => $product->isLinked(),
        ];

        return response()->json([
            'product' => $data,
        ]);
    }

    /**
     * Create local Ozon product record (does not push to Ozon API)
     */
    public function store(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $data = $request->validate([
            'external_product_id' => ['nullable', 'string', 'max:255'],
            'external_offer_id' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric'],
            'old_price' => ['nullable', 'numeric'],
            'stock' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'visible' => ['nullable', 'boolean'],
        ]);

        $data['marketplace_account_id'] = $account->id;
        $product = OzonProduct::create($data);

        return response()->json([
            'message' => 'Товар создан локально',
            'product' => $product,
        ], 201);
    }

    /**
     * Update local Ozon product record (does not push to Ozon API)
     */
    public function update(Request $request, MarketplaceAccount $account, OzonProduct $product): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($product->marketplace_account_id !== $account->id) {
            return response()->json(['message' => 'Товар не принадлежит этому аккаунту'], 403);
        }

        $data = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer'],
            'price' => ['nullable', 'numeric'],
            'old_price' => ['nullable', 'numeric'],
            'stock' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'visible' => ['nullable', 'boolean'],
        ]);

        $product->update($data);

        return response()->json([
            'message' => 'Товар обновлён локально',
            'product' => $product->fresh(),
        ]);
    }

    /**
     * Delete local Ozon product record (does not push to Ozon API)
     */
    public function destroy(Request $request, MarketplaceAccount $account, OzonProduct $product): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($product->marketplace_account_id !== $account->id) {
            return response()->json(['message' => 'Товар не принадлежит этому аккаунту'], 403);
        }

        $product->delete();

        return response()->json([
            'message' => 'Товар удалён локально',
        ]);
    }

    /**
     * Sync catalog from Ozon (pull products from marketplace)
     */
    public function syncCatalog(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($account->marketplace !== 'ozon') {
            return response()->json(['message' => 'Аккаунт не является Ozon.'], 400);
        }

        try {
            $httpClient = app(\App\Services\Marketplaces\MarketplaceHttpClient::class);
            $client = new \App\Services\Marketplaces\OzonClient($httpClient);

            \Log::info('Starting Ozon catalog sync', [
                'account_id' => $account->id,
                'user_id' => $request->user()->id,
            ]);

            $result = $client->syncCatalog($account);

            \Log::info('Ozon catalog sync completed', [
                'account_id' => $account->id,
                'result' => $result,
            ]);

            $synced = $result['synced'] ?? 0;
            $created = $result['created'] ?? 0;
            $updated = $result['updated'] ?? 0;

            if ($synced === 0) {
                return response()->json([
                    'success' => true,
                    'message' => 'На OZON аккаунте не найдено товаров для синхронизации. Убедитесь, что товары добавлены в личном кабинете OZON.',
                    'synced' => 0,
                    'created' => 0,
                    'updated' => 0,
                    'info' => 'Если товары есть в личном кабинете OZON, проверьте права API-ключа.',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => "Синхронизировано товаров: {$synced} (новых: {$created}, обновлено: {$updated})",
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
            ]);
        } catch (\Exception $e) {
            \Log::error('Ozon catalog sync failed', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Ошибка синхронизации: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function transformProduct(OzonProduct $product): array
    {
        return [
            'id' => $product->id,
            'external_product_id' => $product->external_product_id,
            'external_offer_id' => $product->external_offer_id,
            'barcode' => $product->barcode,
            'name' => $product->name,
            'category_id' => $product->category_id,
            'price' => $product->price,
            'old_price' => $product->old_price,
            'stock' => $product->stock,
            'status' => $product->status,
            'visible' => $product->visible,
            'last_synced_at' => optional($product->last_synced_at)->toIso8601String(),
            'primary_image' => $product->primary_image,
        ];
    }
}
