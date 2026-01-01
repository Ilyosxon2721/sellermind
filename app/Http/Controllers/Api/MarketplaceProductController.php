<?php
// file: app/Http/Controllers/Api/MarketplaceProductController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceProduct;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'status' => ['nullable', 'string'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $query = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->select([
                'id',
                'marketplace_account_id',
                'product_id',
                'external_product_id',
                'external_offer_id',
                'external_sku',
                'status',
                'shop_id',
                'title',
                'category',
                'preview_image',
                'last_synced_price',
                'last_synced_stock',
                'last_synced_at',
                'updated_at',
                'created_at',
            ])
            ->with('product');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Use PK index to avoid large filesort on wide JSON payloads
        $products = $query->orderByDesc('id')->paginate(50);

        return response()->json([
            'products' => $products->items(),
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'product_id' => ['required', 'exists:products,id'],
            'external_sku' => ['nullable', 'string', 'max:255'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);
        $product = Product::findOrFail($request->product_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Check if product belongs to same company
        if ($product->company_id !== $account->company_id) {
            return response()->json(['message' => 'Товар не принадлежит компании.'], 400);
        }

        // Ограничение: не более 10 привязок на один локальный товар в рамках аккаунта
        $count = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->where('product_id', $product->id)
            ->count();
        if ($count >= 10) {
            return response()->json(['message' => 'Достигнут лимит 10 карточек WB для этого товара'], 422);
        }

        $mpProduct = MarketplaceProduct::create([
            'marketplace_account_id' => $account->id,
            'product_id' => $product->id,
            'external_sku' => $request->external_sku ?? $product->sku,
            'status' => MarketplaceProduct::STATUS_PENDING,
        ]);

        return response()->json([
            'message' => 'Товар привязан к маркетплейсу.',
            'product' => $mpProduct->load('product'),
        ], 201);
    }

    public function update(Request $request, MarketplaceProduct $marketplaceProduct): JsonResponse
    {
        $account = $marketplaceProduct->account;

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Нормализуем пустые значения, чтобы валидация не падала на пустых строках
        foreach (['product_id', 'external_product_id', 'external_offer_id', 'external_sku'] as $field) {
        if ($request->has($field) && $request->input($field) === '') {
            $request->merge([$field => null]);
        }
    }

        // Приводим числовые внешние идентификаторы к строке, чтобы не срабатывать правило string
        foreach (['external_product_id', 'external_offer_id', 'external_sku'] as $field) {
            if ($request->has($field) && is_numeric($request->input($field))) {
                $request->merge([$field => (string)$request->input($field)]);
            }
        }

        $request->validate([
            'external_sku' => ['nullable', 'string', 'max:255'],
            'external_offer_id' => ['nullable', 'string', 'max:255'],
            'external_product_id' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', 'in:pending,active,paused,failed'],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
        ]);

        if ($request->filled('product_id')) {
            $product = Product::find($request->product_id);
            if (!$product || $product->company_id !== $account->company_id) {
                return response()->json(['message' => 'Товар не принадлежит компании'], 400);
            }
        }

        $payload = $request->only(['external_sku', 'external_offer_id', 'external_product_id', 'status', 'product_id']);

        // Нормализуем пустые значения
        foreach (['external_sku', 'external_offer_id', 'external_product_id'] as $field) {
            if (array_key_exists($field, $payload) && $payload[$field] === '') {
                $payload[$field] = null;
            }
        }
        if (array_key_exists('product_id', $payload) && !$payload['product_id']) {
            $payload['product_id'] = null;
        }

        // Проверяем лимит 10 привязок
        if (!empty($payload['product_id'])) {
            $count = MarketplaceProduct::where('marketplace_account_id', $account->id)
                ->where('product_id', $payload['product_id'])
                ->where('id', '!=', $marketplaceProduct->id)
                ->count();
            if ($count >= 10) {
                return response()->json(['message' => 'Достигнут лимит 10 карточек WB для этого товара'], 422);
            }
        }

        $marketplaceProduct->update($payload);

        return response()->json([
            'message' => 'Товар обновлён.',
            'product' => $marketplaceProduct->fresh()->load('product'),
        ]);
    }

    public function destroy(Request $request, MarketplaceProduct $marketplaceProduct): JsonResponse
    {
        $account = $marketplaceProduct->account;

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $marketplaceProduct->delete();

        return response()->json([
            'message' => 'Привязка товара удалена.',
        ]);
    }

    public function bulkLink(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'product_ids' => ['required', 'array'],
            'product_ids.*' => ['exists:products,id'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $created = 0;
        $skipped = 0;

        foreach ($request->product_ids as $productId) {
            $product = Product::find($productId);

            if (!$product || $product->company_id !== $account->company_id) {
                $skipped++;
                continue;
            }

            $existing = MarketplaceProduct::where('marketplace_account_id', $account->id)
                ->where('product_id', $productId)
                ->exists();

            if ($existing) {
                $skipped++;
                continue;
            }

            MarketplaceProduct::create([
                'marketplace_account_id' => $account->id,
                'product_id' => $productId,
                'external_sku' => $product->sku,
                'status' => MarketplaceProduct::STATUS_PENDING,
            ]);

            $created++;
        }

        return response()->json([
            'message' => "Привязано товаров: {$created}, пропущено: {$skipped}",
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    public function unlinkedProducts(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Get products not yet linked to this marketplace account
        $linkedIds = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->pluck('product_id');

        $products = Product::where('company_id', $account->company_id)
            ->whereNotIn('id', $linkedIds)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'article',
                'brand_name as brand',
            ]);

        return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Get all local products for this company with info if already linked
     */
    public function availableProducts(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $linked = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('product_id')
            ->pluck('id', 'product_id'); // product_id => marketplace_product_id

        $products = Product::where('company_id', $account->company_id)
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'article',
                'brand_name as brand',
            ])
            ->map(function ($product) use ($linked) {
                $product->linked_marketplace_product_id = $linked[$product->id] ?? null;
                $product->is_linked = $product->linked_marketplace_product_id !== null;
                return $product;
            });

            return response()->json([
            'products' => $products,
        ]);
    }

    /**
     * Search local products with fuzzy matching (1+ characters)
     */
    public function searchProducts(Request $request): JsonResponse
    {
        $request->validate([
            'marketplace_account_id' => ['required', 'exists:marketplace_accounts,id'],
            'query' => ['required', 'string', 'min:1', 'max:255'],
        ]);

        $account = MarketplaceAccount::findOrFail($request->marketplace_account_id);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $query = $request->string('query')->trim()->lower()->toString();

        $linked = MarketplaceProduct::where('marketplace_account_id', $account->id)
            ->whereNotNull('product_id')
            ->pluck('id', 'product_id');

        $products = Product::where('company_id', $account->company_id)
            ->where(function ($q) use ($query) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%{$query}%"])
                  ->orWhereRaw('LOWER(article) LIKE ?', ["%{$query}%"])
                  ->orWhereRaw('LOWER(brand_name) LIKE ?', ["%{$query}%"]);
            })
            ->orderByRaw("
                CASE 
                    WHEN LOWER(article) LIKE ? THEN 1
                    WHEN LOWER(name) LIKE ? THEN 2
                    ELSE 3
                END
            ", ["{$query}%", "{$query}%"])
            ->limit(30)
            ->get([
                'id',
                'name',
                'article',
                'brand_name as brand',
            ])
            ->map(function ($product) use ($linked) {
                $product->linked_marketplace_product_id = $linked[$product->id] ?? null;
                $product->is_linked = $product->linked_marketplace_product_id !== null;
                return $product;
            });

        return response()->json([
            'products' => $products,
        ]);
    }
}
