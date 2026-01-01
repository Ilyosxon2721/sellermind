<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inventory;
use App\Models\MarketplaceAccount;
use App\Models\Product;
use App\Models\UzumOrder;
use App\Models\Warehouse\ChannelOrder;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Общий dashboard с информацией по всем модулям
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $companyId = $request->input('company_id');
            
            if (!$companyId) {
                return response()->json(['error' => 'company_id is required'], 400);
            }

            if (!$request->user()->hasCompanyAccess($companyId)) {
                return response()->json(['message' => 'Доступ запрещён.'], 403);
            }

            $today = Carbon::today();
            $weekAgo = Carbon::today()->subDays(7);
            $monthAgo = Carbon::today()->subDays(30);

            // === ПРОДАЖИ ===
            $salesData = $this->getSalesData($companyId, $today, $weekAgo, $monthAgo);

            // === МАРКЕТПЛЕЙСЫ ===
            $marketplaceData = $this->getMarketplaceData($companyId);

            // === СКЛАД ===
            $warehouseData = $this->getWarehouseData($companyId);

            // === ТОВАРЫ ===
            $productsData = $this->getProductsData($companyId);

            return response()->json([
                'success' => true,
                'summary' => [
                    'sales_today' => $salesData['today_amount'],
                    'sales_today_count' => $salesData['today_count'],
                    'sales_week' => $salesData['week_amount'],
                    'sales_week_count' => $salesData['week_count'],
                    'products_total' => $productsData['total'],
                    'products_active' => $productsData['active'],
                    'warehouse_value' => $warehouseData['total_value'],
                    'warehouse_items' => $warehouseData['total_items'],
                    'marketplaces_count' => $marketplaceData['accounts_count'],
                ],
                'sales' => $salesData,
                'marketplace' => $marketplaceData,
                'warehouse' => $warehouseData,
                'products' => $productsData,
            ]);
        } catch (\Throwable $e) {
            \Log::error('Dashboard error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Данные по продажам
     */
    private function getSalesData(int $companyId, Carbon $today, Carbon $weekAgo, Carbon $monthAgo): array
    {
        // Uzum orders
        $uzumOrders = UzumOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId));
        
        $todayAmount = (clone $uzumOrders)->whereDate('ordered_at', $today)->sum('total_amount');
        $todayCount = (clone $uzumOrders)->whereDate('ordered_at', $today)->count();
        
        $weekAmount = (clone $uzumOrders)->whereDate('ordered_at', '>=', $weekAgo)->sum('total_amount');
        $weekCount = (clone $uzumOrders)->whereDate('ordered_at', '>=', $weekAgo)->count();
        
        $monthAmount = (clone $uzumOrders)->whereDate('ordered_at', '>=', $monthAgo)->sum('total_amount');
        $monthCount = (clone $uzumOrders)->whereDate('ordered_at', '>=', $monthAgo)->count();

        // По дням за неделю
        $dailySales = UzumOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereDate('ordered_at', '>=', $weekAgo)
            ->select(DB::raw('DATE(ordered_at) as date'), DB::raw('SUM(total_amount) as amount'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i)->format('Y-m-d');
            $chartLabels[] = Carbon::parse($date)->format('d.m');
            $chartData[] = (float) ($dailySales[$date]->amount ?? 0);
        }

        // Последние заказы
        $recentOrders = UzumOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->orderByDesc('ordered_at')
            ->limit(5)
            ->get()
            ->map(fn($o) => [
                'id' => $o->id,
                'order_number' => $o->external_order_id,
                'amount' => (float) $o->total_amount,
                'status' => $o->status_normalized ?? $o->status,
                'date' => $o->ordered_at?->format('d.m.Y H:i'),
            ]);

        // По статусам
        $byStatus = UzumOrder::whereHas('account', fn($q) => $q->where('company_id', $companyId))
            ->whereDate('ordered_at', '>=', $weekAgo)
            ->select('status_normalized', DB::raw('COUNT(*) as count'))
            ->groupBy('status_normalized')
            ->pluck('count', 'status_normalized')
            ->toArray();

        return [
            'today_amount' => (float) $todayAmount,
            'today_count' => $todayCount,
            'week_amount' => (float) $weekAmount,
            'week_count' => $weekCount,
            'month_amount' => (float) $monthAmount,
            'month_count' => $monthCount,
            'chart_labels' => $chartLabels,
            'chart_data' => $chartData,
            'recent_orders' => $recentOrders,
            'by_status' => $byStatus,
        ];
    }

    /**
     * Данные маркетплейсов
     */
    private function getMarketplaceData(int $companyId): array
    {
        $accounts = MarketplaceAccount::where('company_id', $companyId)->get();
        
        $byMarketplace = $accounts->groupBy('marketplace')->map(fn($group) => [
            'count' => $group->count(),
            'active' => $group->where('is_active', true)->count(),
        ]);

        return [
            'accounts_count' => $accounts->count(),
            'active_count' => $accounts->where('is_active', true)->count(),
            'by_marketplace' => $byMarketplace,
            'accounts' => $accounts->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->name ?? $a->getDisplayName(),
                'marketplace' => $a->marketplace,
                'is_active' => $a->is_active,
            ]),
        ];
    }

    /**
     * Данные склада
     */
    private function getWarehouseData(int $companyId): array
    {
        $warehouses = Warehouse::where('company_id', $companyId)->get();
        $warehouseIds = $warehouses->pluck('id');

        // Получаем агрегированные данные по остаткам из stock_ledger
        $stockData = StockLedger::whereIn('warehouse_id', $warehouseIds)
            ->select('sku_id', DB::raw('SUM(qty_delta) as quantity'), DB::raw('SUM(cost_delta) as total_cost'))
            ->groupBy('sku_id')
            ->having('quantity', '>', 0)
            ->get();

        $totalItems = $stockData->sum('quantity');
        $totalValue = $stockData->sum('total_cost');

        // Критически низкие остатки (меньше 10 единиц)
        $lowStock = $stockData->where('quantity', '>', 0)->where('quantity', '<', 10)->count();

        // Последние инвентаризации
        $recentDocuments = Inventory::whereIn('warehouse_id', $warehouseIds)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($d) => [
                'id' => $d->id,
                'number' => $d->number,
                'type' => $d->type,
                'date' => $d->date?->format('d.m.Y'),
            ]);

        return [
            'warehouses_count' => $warehouses->count(),
            'total_items' => (float) $totalItems,
            'total_value' => (float) $totalValue,
            'low_stock_count' => $lowStock,
            'recent_documents' => $recentDocuments,
        ];
    }

    /**
     * Данные товаров
     */
    private function getProductsData(int $companyId): array
    {
        $products = Product::where('company_id', $companyId);
        
        $total = (clone $products)->count();
        $active = (clone $products)->where('is_active', true)->count();
        $archived = (clone $products)->where('is_active', false)->count();
        
        // Топ товары (если есть связь с продажами)
        $topProducts = Product::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderByDesc('updated_at')
            ->limit(5)
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'price' => (float) $p->price,
            ]);

        return [
            'total' => $total,
            'active' => $active,
            'archived' => $archived,
            'top_products' => $topProducts,
        ];
    }
}
