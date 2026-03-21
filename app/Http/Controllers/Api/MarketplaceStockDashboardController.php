<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HasCompanyScope;
use App\Models\MarketplaceAccount;
use App\Models\WildberriesProduct;
use App\Models\WildberriesStock;
use App\Models\WildberriesWarehouse;
use App\Support\ApiResponder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Дашборд остатков на складах маркетплейсов
 */
final class MarketplaceStockDashboardController extends Controller
{
    use ApiResponder;
    use HasCompanyScope;

    /**
     * Сводка остатков по всем маркетплейсам
     */
    public function summary(Request $request): JsonResponse
    {
        $emptyResponse = [
            'total_quantity' => 0,
            'total_in_transit' => 0,
            'total_returning' => 0,
            'warehouses_count' => 0,
            'products_count' => 0,
            'out_of_stock_count' => 0,
            'low_stock_count' => 0,
            'accounts' => [],
            'warehouses' => [],
            'top_products' => [],
            'low_stock_products' => [],
        ];

        try {
            $companyId = $this->getCompanyId();
        } catch (\Throwable) {
            return $this->successResponse($emptyResponse);
        }

        $accounts = MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->get(['id', 'name', 'marketplace']);

        if ($accounts->isEmpty()) {
            return $this->successResponse($emptyResponse);
        }

        $accountIds = $accounts->pluck('id');

        try {
            // Агрегация по складам
            $warehouseStats = WildberriesStock::query()
                ->whereIn('marketplace_account_id', $accountIds)
                ->join('wildberries_warehouses', 'wildberries_stocks.wildberries_warehouse_id', '=', 'wildberries_warehouses.id')
                ->select(
                    'wildberries_warehouses.id as warehouse_id',
                    'wildberries_warehouses.warehouse_name',
                    'wildberries_warehouses.warehouse_type',
                    'wildberries_warehouses.marketplace_account_id',
                    DB::raw('SUM(wildberries_stocks.quantity) as total_quantity'),
                    DB::raw('SUM(wildberries_stocks.quantity_full) as total_quantity_full'),
                    DB::raw('SUM(wildberries_stocks.in_way_to_client) as total_in_way_to_client'),
                    DB::raw('SUM(wildberries_stocks.in_way_from_client) as total_in_way_from_client'),
                    DB::raw('COUNT(DISTINCT wildberries_stocks.wildberries_product_id) as products_count'),
                )
                ->groupBy(
                    'wildberries_warehouses.id',
                    'wildberries_warehouses.warehouse_name',
                    'wildberries_warehouses.warehouse_type',
                    'wildberries_warehouses.marketplace_account_id',
                )
                ->get();
        } catch (QueryException) {
            // Таблицы ещё не созданы (миграция не выполнена)
            return $this->successResponse($emptyResponse);
        }

        // Агрегация по аккаунтам
        $accountStats = $warehouseStats->groupBy('marketplace_account_id')->map(function ($items, $accountId) use ($accounts) {
            $account = $accounts->firstWhere('id', $accountId);

            return [
                'account_id' => $accountId,
                'name' => $account?->name ?? 'Unknown',
                'marketplace' => $account?->marketplace ?? 'unknown',
                'total_quantity' => $items->sum('total_quantity'),
                'total_in_transit' => $items->sum('total_in_way_to_client'),
                'total_returning' => $items->sum('total_in_way_from_client'),
                'warehouses_count' => $items->count(),
                'products_count' => $items->sum('products_count'),
            ];
        })->values();

        // Склады с детализацией
        $warehouses = $warehouseStats->map(function ($wh) use ($accounts) {
            $account = $accounts->firstWhere('id', $wh->marketplace_account_id);

            return [
                'warehouse_id' => $wh->warehouse_id,
                'warehouse_name' => $wh->warehouse_name,
                'warehouse_type' => $wh->warehouse_type,
                'account_name' => $account?->name,
                'marketplace' => $account?->marketplace,
                'total_quantity' => (int) $wh->total_quantity,
                'total_quantity_full' => (int) $wh->total_quantity_full,
                'in_way_to_client' => (int) $wh->total_in_way_to_client,
                'in_way_from_client' => (int) $wh->total_in_way_from_client,
                'products_count' => (int) $wh->products_count,
            ];
        })->sortByDesc('total_quantity')->values();

        // Топ товаров по количеству
        $topProducts = WildberriesStock::query()
            ->whereIn('wildberries_stocks.marketplace_account_id', $accountIds)
            ->join('wildberries_products', 'wildberries_stocks.wildberries_product_id', '=', 'wildberries_products.id')
            ->select(
                'wildberries_products.id',
                'wildberries_products.nm_id',
                'wildberries_products.supplier_article',
                'wildberries_products.title',
                'wildberries_products.brand',
                'wildberries_products.subject_name',
                'wildberries_products.barcode',
                DB::raw('SUM(wildberries_stocks.quantity) as total_stock'),
                DB::raw('SUM(wildberries_stocks.in_way_to_client) as in_transit'),
                DB::raw('SUM(wildberries_stocks.in_way_from_client) as returning'),
                DB::raw('COUNT(DISTINCT wildberries_stocks.wildberries_warehouse_id) as warehouse_count'),
            )
            ->groupBy(
                'wildberries_products.id',
                'wildberries_products.nm_id',
                'wildberries_products.supplier_article',
                'wildberries_products.title',
                'wildberries_products.brand',
                'wildberries_products.subject_name',
                'wildberries_products.barcode',
            )
            ->orderByDesc('total_stock')
            ->limit(20)
            ->get();

        // Товары с низким остатком (1-5 шт)
        $lowStockProducts = WildberriesStock::query()
            ->whereIn('wildberries_stocks.marketplace_account_id', $accountIds)
            ->join('wildberries_products', 'wildberries_stocks.wildberries_product_id', '=', 'wildberries_products.id')
            ->select(
                'wildberries_products.id',
                'wildberries_products.nm_id',
                'wildberries_products.supplier_article',
                'wildberries_products.title',
                'wildberries_products.brand',
                'wildberries_products.barcode',
                DB::raw('SUM(wildberries_stocks.quantity) as total_stock'),
                DB::raw('SUM(wildberries_stocks.in_way_to_client) as in_transit'),
            )
            ->groupBy(
                'wildberries_products.id',
                'wildberries_products.nm_id',
                'wildberries_products.supplier_article',
                'wildberries_products.title',
                'wildberries_products.brand',
                'wildberries_products.barcode',
            )
            ->havingRaw('SUM(wildberries_stocks.quantity) BETWEEN 1 AND 5')
            ->orderBy('total_stock')
            ->limit(20)
            ->get();

        // Товары с нулевым остатком
        $outOfStockCount = WildberriesProduct::query()
            ->whereIn('marketplace_account_id', $accountIds)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('stock_total', 0)->orWhereNull('stock_total');
            })
            ->count();

        return $this->successResponse([
            'total_quantity' => $warehouseStats->sum('total_quantity'),
            'total_in_transit' => $warehouseStats->sum('total_in_way_to_client'),
            'total_returning' => $warehouseStats->sum('total_in_way_from_client'),
            'warehouses_count' => $warehouseStats->count(),
            'products_count' => WildberriesProduct::whereIn('marketplace_account_id', $accountIds)->where('is_active', true)->count(),
            'out_of_stock_count' => $outOfStockCount,
            'low_stock_count' => $lowStockProducts->count(),
            'accounts' => $accountStats,
            'warehouses' => $warehouses,
            'top_products' => $topProducts,
            'low_stock_products' => $lowStockProducts,
        ]);
    }

    /**
     * Поиск товаров по остаткам
     */
    public function search(Request $request): JsonResponse
    {
        $emptySearch = ['items' => [], 'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 30, 'total' => 0]];

        $request->validate([
            'query' => ['nullable', 'string', 'max:255'],
            'account_id' => ['nullable', 'integer'],
            'warehouse_id' => ['nullable', 'integer'],
            'stock_filter' => ['nullable', 'in:all,in_stock,low,out_of_stock'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        try {
            $companyId = $this->getCompanyId();
        } catch (\Throwable) {
            return $this->successResponse($emptySearch);
        }
        $accountIds = MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true)
            ->pluck('id');

        $query = WildberriesProduct::query()
            ->whereIn('wildberries_products.marketplace_account_id', $accountIds)
            ->where('wildberries_products.is_active', true)
            ->with(['account:id,name,marketplace']);

        // Фильтр по аккаунту
        if ($request->account_id) {
            $query->where('wildberries_products.marketplace_account_id', $request->account_id);
        }

        // Поиск
        if ($search = $request->query) {
            $query->where(function ($q) use ($search) {
                $q->where('wildberries_products.supplier_article', 'like', "%{$search}%")
                    ->orWhere('wildberries_products.title', 'like', "%{$search}%")
                    ->orWhere('wildberries_products.barcode', 'like', "%{$search}%")
                    ->orWhere('wildberries_products.nm_id', 'like', "%{$search}%")
                    ->orWhere('wildberries_products.brand', 'like', "%{$search}%");
            });
        }

        // Фильтр по остаткам
        $stockFilter = $request->stock_filter ?? 'all';
        if ($stockFilter === 'in_stock') {
            $query->where('wildberries_products.stock_total', '>', 5);
        } elseif ($stockFilter === 'low') {
            $query->whereBetween('wildberries_products.stock_total', [1, 5]);
        } elseif ($stockFilter === 'out_of_stock') {
            $query->where(fn ($q) => $q->where('wildberries_products.stock_total', 0)->orWhereNull('wildberries_products.stock_total'));
        }

        $perPage = min((int) ($request->per_page ?? 30), 100);
        $paginator = $query->orderByDesc('wildberries_products.stock_total')
            ->paginate($perPage);

        // Подгрузить остатки по складам для каждого товара
        $productIds = collect($paginator->items())->pluck('id');
        $stocksByProduct = WildberriesStock::query()
            ->whereIn('wildberries_product_id', $productIds)
            ->with('warehouse:id,warehouse_name,warehouse_type')
            ->get()
            ->groupBy('wildberries_product_id');

        $items = collect($paginator->items())->map(function ($product) use ($stocksByProduct) {
            $stocks = $stocksByProduct->get($product->id, collect());

            return [
                'id' => $product->id,
                'nm_id' => $product->nm_id,
                'supplier_article' => $product->supplier_article,
                'title' => $product->title,
                'brand' => $product->brand,
                'barcode' => $product->barcode,
                'stock_total' => $product->stock_total ?? 0,
                'marketplace' => $product->account?->marketplace,
                'account_name' => $product->account?->name,
                'warehouses' => $stocks->map(fn ($s) => [
                    'warehouse_name' => $s->warehouse?->warehouse_name,
                    'warehouse_type' => $s->warehouse?->warehouse_type,
                    'quantity' => $s->quantity,
                    'quantity_full' => $s->quantity_full,
                    'in_way_to_client' => $s->in_way_to_client,
                    'in_way_from_client' => $s->in_way_from_client,
                ])->values(),
            ];
        });

        return $this->successResponse([
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}
