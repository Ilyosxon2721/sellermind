<?php

// file: app/Http/Controllers/Api/WildberriesProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasPaginatedResponse;
use App\Models\MarketplaceAccount;
use App\Models\Product;
use App\Models\VariantMarketplaceLink;
use App\Models\WildberriesProduct;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WildberriesProductController extends Controller
{
    use HasPaginatedResponse;

    /**
     * List Wildberries products (cards) with pagination and filters.
     */
    public function index(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'has_photo' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'between:10,200'],
            'sort_by' => ['nullable', 'in:synced_at,updated_at,price,stock_total,nm_id,title'],
            'sort_dir' => ['nullable', 'in:asc,desc'],
        ]);

        $perPage = $this->getPerPage($request, 50, 200);
        $sortBy = $request->input('sort_by', 'synced_at');
        $sortDir = $request->input('sort_dir', 'desc');

        $query = WildberriesProduct::where('marketplace_account_id', $account->id);

        if ($request->filled('search')) {
            $search = $this->escapeLike((string) $request->string('search'));
            $query->where(function ($q) use ($search) {
                // Search in basic fields
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('vendor_code', 'like', "%{$search}%")
                    ->orWhere('supplier_article', 'like', "%{$search}%")
                    ->orWhere('nm_id', $search)
                    ->orWhere('barcode', 'like', "%{$search}%")

                    // Search in raw_data JSON field (characteristics, sizes, barcodes, brand, description)
                    // Using CAST to text for partial matching in JSON content
                    ->orWhere(\DB::raw('CAST(raw_data AS CHAR)'), 'like', "%{$search}%");
            });
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->integer('subject_id'));
        }

        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('has_photo')) {
            $hasPhoto = $request->boolean('has_photo');
            $query->where(function ($q) use ($hasPhoto) {
                if ($hasPhoto) {
                    $q->whereNotNull('photos');
                } else {
                    $q->whereNull('photos');
                }
            });
        }

        $allowedSorts = [
            'synced_at' => 'synced_at',
            'updated_at' => 'updated_at',
            'price' => 'price',
            'stock_total' => 'stock_total',
            'nm_id' => 'nm_id',
            'title' => 'title',
        ];
        $sortColumn = $allowedSorts[$sortBy] ?? 'synced_at';
        $direction = $sortDir === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortColumn, $direction)
            ->orderBy('id', 'desc');

        $products = $query->paginate($perPage);

        // Get ALL linked variants for these products (grouped by product_id)
        // IMPORTANT: Filter by marketplace_account_id to prevent ID collision with other marketplaces
        // (wildberries_products.id can match marketplace_products.id from Uzum/YM)
        $productIds = collect($products->items())->pluck('id')->toArray();
        $links = VariantMarketplaceLink::whereIn('marketplace_product_id', $productIds)
            ->where('marketplace_account_id', $account->id) // Filter by account to prevent ID collision
            ->where('is_active', true)
            ->with(['variant:id,sku,stock_default,option_values_summary', 'variant.product:id,name'])
            ->get()
            ->groupBy('marketplace_product_id'); // Group instead of keyBy to keep all links

        $items = collect($products->items())->map(function (WildberriesProduct $product) use ($links) {
            $data = [
                'id' => $product->id,
                'nm_id' => $product->nm_id,
                'imt_id' => $product->imt_id,
                'vendor_code' => $product->vendor_code,
                'supplier_article' => $product->supplier_article,
                'title' => $product->title,
                'brand' => $product->brand,
                'subject_name' => $product->subject_name,
                'subject_id' => $product->subject_id,
                'tech_size' => $product->tech_size,
                'chrt_id' => $product->chrt_id,
                'barcode' => $product->barcode,
                'price' => $product->price,
                'price_with_discount' => $product->price_with_discount,
                'discount_percent' => $product->discount_percent,
                'stock_total' => $product->stock_total,
                'is_active' => $product->is_active,
                'synced_at' => optional($product->synced_at)->toIso8601String(),
                'primary_photo' => $product->getPrimaryPhotoUrl(),
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

            // Add ALL variant links (for barcode-level linking)
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
            'pagination' => $this->paginationMeta($products),
        ]);
    }

    /**
     * Suggestions for a local product context
     */
    public function suggestions(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }
        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $data = $request->validate([
            'local_product_id' => ['required', 'integer', 'exists:products,id'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $product = Product::find($data['local_product_id']);
        if (! $product || $product->company_id !== $account->company_id) {
            return response()->json(['message' => 'Товар не принадлежит компании'], 403);
        }

        $limit = $request->integer('limit', 10);

        $query = WildberriesProduct::where('marketplace_account_id', $account->id);

        // Простая эвристика релевантности: совпадение категории, артикула, названия
        $query->orderByRaw(
            '(title LIKE ? ) desc,
             (vendor_code LIKE ? ) desc,
             (supplier_article LIKE ? ) desc,
             (barcode LIKE ? ) desc,
             synced_at desc',
            [
                '%'.($product->name_internal ?? '').'%',
                '%'.($product->sku ?? '').'%',
                '%'.($product->sku ?? '').'%',
                '%'.($product->barcode ?? '').'%',
            ]
        );

        $items = $query->limit($limit)->get()->map(fn (WildberriesProduct $wb) => $this->transformProduct($wb));

        return response()->json([
            'products' => $items,
        ]);
    }

    /**
     * Search WB cards with local product context
     */
    public function search(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }
        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $data = $request->validate([
            'local_product_id' => ['required', 'integer', 'exists:products,id'],
            'query' => ['required', 'string', 'max:255'],
            'limit' => ['nullable', 'integer', 'between:1,50'],
        ]);

        $product = Product::find($data['local_product_id']);
        if (! $product || $product->company_id !== $account->company_id) {
            return response()->json(['message' => 'Товар не принадлежит компании'], 403);
        }

        $limit = $request->integer('limit', 20);
        $queryText = $this->escapeLike((string) $request->string('query'));

        $query = WildberriesProduct::where('marketplace_account_id', $account->id)
            ->where(function ($q) use ($queryText) {
                $q->where('nm_id', 'like', "%{$queryText}%")
                    ->orWhere('title', 'like', "%{$queryText}%")
                    ->orWhere('vendor_code', 'like', "%{$queryText}%")
                    ->orWhere('supplier_article', 'like', "%{$queryText}%")
                    ->orWhere('barcode', 'like', "%{$queryText}%");
            });

        $query->orderByRaw(
            '(title LIKE ? ) desc,
             (vendor_code LIKE ? ) desc,
             (supplier_article LIKE ? ) desc,
             (barcode LIKE ? ) desc,
             synced_at desc',
            [
                '%'.($product->name_internal ?? '').'%',
                '%'.($product->sku ?? '').'%',
                '%'.($product->sku ?? '').'%',
                '%'.($product->barcode ?? '').'%',
            ]
        );

        $items = $query->limit($limit)->get()->map(fn (WildberriesProduct $wb) => $this->transformProduct($wb));

        return response()->json([
            'products' => $items,
        ]);
    }

    /**
     * Get single WB product
     */
    public function show(Request $request, MarketplaceAccount $account, WildberriesProduct $product): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($product->marketplace_account_id !== $account->id) {
            return response()->json(['message' => 'Товар не принадлежит этому аккаунту'], 403);
        }

        return response()->json([
            'product' => $product->toArray(),
        ]);
    }

    /**
     * Create local WB product record (does not push to WB API)
     */
    public function store(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $data = $request->validate([
            'nm_id' => ['nullable', 'integer'],
            'imt_id' => ['nullable', 'integer'],
            'chrt_id' => ['nullable', 'integer'],
            'vendor_code' => ['nullable', 'string', 'max:255'],
            'supplier_article' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'subject_name' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'discount_percent' => ['nullable', 'integer'],
            'stock_total' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['marketplace_account_id'] = $account->id;
        $product = WildberriesProduct::create($data);

        return response()->json([
            'message' => 'Товар создан локально',
            'product' => $product,
        ], 201);
    }

    /**
     * Update local WB product record (does not push to WB API)
     */
    public function update(Request $request, MarketplaceAccount $account, WildberriesProduct $product): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if ($product->marketplace_account_id !== $account->id) {
            return response()->json(['message' => 'Товар не принадлежит этому аккаунту'], 403);
        }

        $data = $request->validate([
            'vendor_code' => ['nullable', 'string', 'max:255'],
            'supplier_article' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'subject_name' => ['nullable', 'string', 'max:255'],
            'subject_id' => ['nullable', 'integer'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'price' => ['nullable', 'numeric'],
            'discount_percent' => ['nullable', 'integer'],
            'stock_total' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $product->update($data);

        return response()->json([
            'message' => 'Товар обновлён локально',
            'product' => $product->fresh(),
        ]);
    }

    /**
     * Delete local WB product record (does not push to WB API)
     */
    public function destroy(Request $request, MarketplaceAccount $account, WildberriesProduct $product): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
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

    private function transformProduct(WildberriesProduct $product): array
    {
        return [
            'id' => $product->id,
            'nm_id' => $product->nm_id,
            'imt_id' => $product->imt_id,
            'vendor_code' => $product->vendor_code,
            'supplier_article' => $product->supplier_article,
            'title' => $product->title,
            'brand' => $product->brand,
            'subject_name' => $product->subject_name,
            'subject_id' => $product->subject_id,
            'tech_size' => $product->tech_size,
            'price' => $product->price,
            'price_with_discount' => $product->price_with_discount,
            'discount_percent' => $product->discount_percent,
            'stock_total' => $product->stock_total,
            'is_active' => $product->is_active,
            'synced_at' => optional($product->synced_at)->toIso8601String(),
            'primary_photo' => $product->getPrimaryPhotoUrl(),
        ];
    }
}
