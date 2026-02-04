<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductDescription;
use App\Models\ProductVariant;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClientApiController extends Controller
{
    /**
     * Получить товары клиента на складе
     */
    public function getProducts(Request $request)
    {
        $company = $request->user()->company;

        $products = ProductVariant::where('company_id', $company->id)
            ->with(['product', 'images', 'variantMarketplaceLinks'])
            ->when($request->search, function ($query, $search) {
                $search = $this->escapeLike($search);
                $query->whereHas('product', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->paginate($request->per_page ?? 20);

        return response()->json($products);
    }

    /**
     * Получить заказы клиента
     */
    public function getOrders(Request $request)
    {
        $company = $request->user()->company;

        $orders = Sale::where('company_id', $company->id)
            ->with(['items.productVariant.product', 'marketplaceAccount'])
            ->when($request->status, fn ($q, $status) => $q->where('status', $status))
            ->when($request->date_from, fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
            ->when($request->date_to, fn ($q, $date) => $q->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate($request->per_page ?? 20);

        return response()->json($orders);
    }

    /**
     * Получить остатки товаров
     */
    public function getInventory(Request $request)
    {
        $company = $request->user()->company;

        $inventory = Inventory::where('company_id', $company->id)
            ->with(['productVariant.product', 'warehouse'])
            ->when($request->warehouse_id, fn ($q, $id) => $q->where('warehouse_id', $id))
            ->get()
            ->groupBy('productVariant.id')
            ->map(function ($items) {
                return [
                    'product' => $items->first()->productVariant,
                    'total_quantity' => $items->sum('quantity'),
                    'warehouses' => $items->map(fn ($item) => [
                        'warehouse' => $item->warehouse,
                        'quantity' => $item->quantity,
                    ]),
                ];
            })
            ->values();

        return response()->json(['data' => $inventory]);
    }

    /**
     * Статистика по клиенту
     */
    public function getStatistics(Request $request)
    {
        $company = $request->user()->company;

        $stats = [
            'total_products' => ProductVariant::where('company_id', $company->id)->count(),
            'total_orders' => Sale::where('company_id', $company->id)->count(),
            'orders_this_month' => Sale::where('company_id', $company->id)
                ->whereMonth('created_at', now()->month)
                ->count(),
            'revenue_this_month' => Sale::where('company_id', $company->id)
                ->whereMonth('created_at', now()->month)
                ->sum('total_amount'),
            'orders_by_status' => Sale::where('company_id', $company->id)
                ->selectRaw('status, count(*) as count')
                ->groupBy('status')
                ->pluck('count', 'status'),
            'total_inventory' => Inventory::where('company_id', $company->id)->sum('quantity'),
        ];

        return response()->json($stats);
    }

    /**
     * Профиль клиента
     */
    public function getProfile(Request $request)
    {
        $user = $request->user();
        $company = $user->company;

        return response()->json([
            'user' => $user->only(['id', 'name', 'email']),
            'company' => $company->only(['id', 'name', 'phone', 'address']),
            'subscription' => [
                'plan' => $company->risment_subscription_plan,
                'expires_at' => $company->subscription_expires_at,
                'is_active' => $company->subscription_expires_at && $company->subscription_expires_at->isFuture(),
            ],
        ]);
    }

    /**
     * Создать товар клиента
     */
    public function createProduct(Request $request)
    {
        $company = $request->user()->company;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sku' => 'nullable|string|max:100',
            'barcode' => 'nullable|string|max:100',
            'price' => 'required|numeric|min:0',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'numeric|min:0',
            'dimensions.width' => 'numeric|min:0',
            'dimensions.height' => 'numeric|min:0',
            'description' => 'nullable|string',
            'category_id' => 'nullable|exists:product_categories,id',
        ]);

        DB::beginTransaction();
        try {
            // Создать основной Product
            $product = Product::create([
                'company_id' => $company->id,
                'name' => $validated['name'],
                'category_id' => $validated['category_id'] ?? null,
            ]);

            // Создать ProductVariant
            $variant = ProductVariant::create([
                'product_id' => $product->id,
                'company_id' => $company->id,
                'sku' => $validated['sku'] ?? null,
                'barcode' => $validated['barcode'] ?? null,
                'price' => $validated['price'],
                'weight' => $validated['weight'] ?? null,
                'length' => $validated['dimensions']['length'] ?? null,
                'width' => $validated['dimensions']['width'] ?? null,
                'height' => $validated['dimensions']['height'] ?? null,
            ]);

            // Создать описание
            if (! empty($validated['description'])) {
                ProductDescription::create([
                    'product_id' => $product->id,
                    'description' => $validated['description'],
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'product' => $variant->load(['product', 'images']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create product',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Обновить товар
     */
    public function updateProduct(Request $request, $id)
    {
        $company = $request->user()->company;

        $variant = ProductVariant::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'weight' => 'sometimes|numeric|min:0',
            'sku' => 'sometimes|string|max:100',
            'barcode' => 'sometimes|string|max:100',
        ]);

        if (isset($validated['name'])) {
            $variant->product->update(['name' => $validated['name']]);
            unset($validated['name']);
        }

        $variant->update($validated);

        return response()->json([
            'success' => true,
            'product' => $variant->load(['product']),
        ]);
    }

    /**
     * Удалить товар
     */
    public function deleteProduct(Request $request, $id)
    {
        $company = $request->user()->company;

        $variant = ProductVariant::where('id', $id)
            ->where('company_id', $company->id)
            ->firstOrFail();

        $variant->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }
}
