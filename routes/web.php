<?php

use App\Http\Controllers\MarketplaceSyncLogController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\VpcControlApiController;
use App\Http\Controllers\VpcSessionController;
use App\Http\Controllers\Web\LandingController;
use App\Http\Controllers\Web\MarketplaceWebController;
use App\Http\Controllers\Web\PageController;
use App\Http\Controllers\Web\Products\ProductWebController;
use App\Http\Controllers\Web\Warehouse\WarehouseController;
use Illuminate\Support\Facades\Route;

// Localized Landing Pages
Route::get('/uz', [LandingController::class, 'home'])->defaults('locale', 'uz')->name('home.uz');
Route::get('/ru', [LandingController::class, 'home'])->defaults('locale', 'ru')->name('home.ru');
Route::get('/en', [LandingController::class, 'home'])->defaults('locale', 'en')->name('home.en');

// Localized auth routes
Route::prefix('{locale}')->whereIn('locale', ['uz', 'ru', 'en'])->group(function () {
    Route::get('/login', [LandingController::class, 'login'])->name('login.localized');
    Route::get('/register', [LandingController::class, 'register'])->name('register.localized');
});

// Root redirect to Russian
Route::get('/', function (Illuminate\Http\Request $request) {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return redirect('/ru');
})->name('home');

Route::view('/login', 'pages.login')->name('login');
Route::view('/register', 'pages.register')->name('register');

// Health check for PWA offline detection
Route::get('/api/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Offline page for PWA
Route::view('/offline', 'offline')->name('offline');

// Auth API routes (in web.php for proper session cookie handling)
// These MUST be in web.php, not api.php, for session cookies to work correctly
Route::prefix('api/auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
});

// App pages - Dashboard is the main page after login (protected by auth middleware)
Route::middleware('auth.any')->group(function () {
    Route::redirect('/home', '/dashboard');

    Route::view('/dashboard', 'pages.dashboard')->name('dashboard');
    Route::view('/chat', 'pages.chat')->name('chat');
    Route::view('/settings', 'pages.settings')->name('settings');
    Route::view('/profile', 'pages.profile')->name('profile');

    // Integrations
    Route::get('/integrations', [\App\Http\Controllers\Web\IntegrationController::class, 'index'])
        ->name('integrations.index');
    Route::get('/integrations/risment', [\App\Http\Controllers\Web\IntegrationController::class, 'risment'])
        ->name('integrations.risment');

    // RISMENT Integration Link page (legacy redirect)
    Route::redirect('/integration/risment', '/integrations/risment')->name('integration.risment');

    Route::view('/promotions', 'pages.promotions')->name('promotions');
    Route::view('/analytics', 'pages.analytics')->name('analytics');

    // Analytics sub-pages
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::view('/revenue', 'pages.analytics')->name('revenue');
        Route::view('/products', 'pages.analytics')->name('products');
        Route::view('/abc', 'pages.analytics')->name('abc');
        Route::view('/pnl', 'pages.analytics')->name('pnl');
        Route::view('/stock', 'pages.analytics')->name('stock');
        Route::view('/funnel', 'pages.analytics')->name('funnel');
    });

    Route::view('/reviews', 'pages.reviews')->name('reviews');

    Route::get('/products/categories', [\App\Http\Controllers\Web\CategoryController::class, 'index'])->name('web.categories.index');

    Route::prefix('products')->name('web.products.')->group(function () {
        Route::get('/', [ProductWebController::class, 'index'])->name('index');
        Route::view('/purchase-prices', 'products.purchase-prices')->name('purchase-prices');
        Route::get('/create', [ProductWebController::class, 'create'])->name('create');
        Route::get('/{product}/edit', [ProductWebController::class, 'edit'])->name('edit');

        // POST/PUT/DELETE — только owner компании
        Route::middleware('company.owner')->group(function () {
            Route::post('/', [ProductWebController::class, 'store'])->name('store');
            Route::put('/{product}', [ProductWebController::class, 'update'])->name('update');
            Route::delete('/{product}', [ProductWebController::class, 'destroy'])->name('destroy');
            Route::post('/{product}/publish', [ProductWebController::class, 'publish'])->name('publish');

            // Bulk операции (через web для session auth)
            Route::match(['get', 'post'], '/bulk/export', [\App\Http\Controllers\Api\ProductBulkController::class, 'export'])->name('bulk.export');
            Route::post('/bulk/import/preview', [\App\Http\Controllers\Api\ProductBulkController::class, 'previewImport'])->name('bulk.import.preview');
            Route::post('/bulk/import/apply', [\App\Http\Controllers\Api\ProductBulkController::class, 'applyImport'])->name('bulk.import.apply');
            Route::post('/bulk/update', [\App\Http\Controllers\Api\ProductBulkController::class, 'bulkUpdate'])->name('bulk.update');
        });
    });

    Route::prefix('warehouse')->name('warehouse.')->group(function () {
        Route::get('/', [WarehouseController::class, 'dashboard'])->name('dashboard');
        Route::get('/balance', [WarehouseController::class, 'balance'])->name('balance');
        Route::get('/in', [WarehouseController::class, 'receipts'])->name('in');
        Route::get('/in/create', [WarehouseController::class, 'createReceipt'])->name('in.create');
        Route::get('/in/{id}/edit', [WarehouseController::class, 'editReceipt'])->name('in.edit');
        Route::get('/list', [WarehouseController::class, 'warehouses'])->name('warehouses');
        Route::get('/create', [PageController::class, 'warehouseCreate'])->name('warehouse.create');
        Route::get('/{id}/edit', [PageController::class, 'warehouseEdit'])->name('warehouse.edit');
        Route::get('/documents', [WarehouseController::class, 'documents'])->name('documents');
        Route::get('/documents/{id}', [WarehouseController::class, 'document'])->name('documents.show');
        Route::get('/reservations', [WarehouseController::class, 'reservations'])->name('reservations');
        Route::get('/ledger', [WarehouseController::class, 'ledger'])->name('ledger');

        // Write-off
        Route::get('/write-off', [WarehouseController::class, 'writeOffs'])->name('write-offs');
        Route::get('/write-off/create', [WarehouseController::class, 'createWriteOff'])->name('write-off.create');
        Route::get('/write-off/{id}/edit', [WarehouseController::class, 'editWriteOff'])->name('write-off.edit');

        // Inventory (инвентаризация)
        Route::get('/inventory', [WarehouseController::class, 'inventoryList'])->name('inventory');
        Route::get('/inventory/create', [WarehouseController::class, 'createInventory'])->name('inventory.create');
        Route::get('/inventory/{id}/edit', [WarehouseController::class, 'editInventory'])->name('inventory.edit');

        // Web-based API routes for warehouse CRUD (uses session auth)
        Route::middleware('auth')->group(function () {
            Route::get('/api/list', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'index'])->name('api.list');
            Route::get('/api/{id}', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'show'])->name('api.show');
            Route::post('/api', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'store'])->name('api.store');
            Route::put('/api/{id}', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'update'])->name('api.update');
            Route::post('/api/{id}/default', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'makeDefault'])->name('api.default');
        });
    });

    // Cabinet aliases for warehouse
    Route::prefix('cabinet/warehouse')->group(function () {
        Route::get('/', [WarehouseController::class, 'dashboard'])->name('cabinet.warehouse.dashboard');
        Route::get('/balance', [WarehouseController::class, 'balance'])->name('cabinet.warehouse.balance');
        Route::get('/in', [WarehouseController::class, 'receipts'])->name('cabinet.warehouse.in');
        Route::get('/in/create', [WarehouseController::class, 'createReceipt'])->name('cabinet.warehouse.in.create');
        Route::get('/in/{id}/edit', [WarehouseController::class, 'editReceipt'])->name('cabinet.warehouse.in.edit');
        Route::get('/list', [WarehouseController::class, 'warehouses'])->name('cabinet.warehouse.warehouses');
        Route::get('/create', [PageController::class, 'warehouseCreate'])->name('cabinet.warehouse.create');
        Route::get('/{id}/edit', [PageController::class, 'warehouseEdit'])->name('cabinet.warehouse.edit');
        Route::get('/documents', [WarehouseController::class, 'documents'])->name('cabinet.warehouse.documents');
        Route::get('/documents/{id}', [WarehouseController::class, 'document'])->name('cabinet.warehouse.documents.show');
        Route::get('/reservations', [WarehouseController::class, 'reservations'])->name('cabinet.warehouse.reservations');
        Route::get('/ledger', [WarehouseController::class, 'ledger'])->name('cabinet.warehouse.ledger');

        // Write-off
        Route::get('/write-off', [WarehouseController::class, 'writeOffs'])->name('cabinet.warehouse.write-offs');
        Route::get('/write-off/create', [WarehouseController::class, 'createWriteOff'])->name('cabinet.warehouse.write-off.create');
        Route::get('/write-off/{id}/edit', [WarehouseController::class, 'editWriteOff'])->name('cabinet.warehouse.write-off.edit');
        Route::get('/inventory/{id}/edit', [WarehouseController::class, 'editInventory'])->name('cabinet.warehouse.inventory.edit');
    });

    Route::view('/tasks', 'pages.tasks')->name('tasks');

    // PWA-optimized tasks
    Route::view('/tasks-pwa', 'pages.tasks-pwa')->name('tasks.pwa');

    // Agent Mode
    Route::view('/agent', 'pages.agent.index')->name('agent.index');
    Route::view('/agent/create', 'pages.agent.create')->name('agent.create');
    Route::get('/agent/{taskId}', [PageController::class, 'agentShow'])->name('agent.show');
    Route::get('/agent/run/{runId}', [PageController::class, 'agentRun'])->name('agent.run');

    Route::view('/sales', 'sales.index')->name('sales.index');
    Route::view('/sales/create', 'sales.create')->name('sales.create');
    Route::get('/sales/{id}', [PageController::class, 'salesShow'])->name('sales.show');

    // Sale print routes
    Route::get('/sales/{sale}/print/receipt', [\App\Http\Controllers\SalePrintController::class, 'receipt'])->name('sales.print.receipt');
    Route::get('/sales/{sale}/print/invoice', [\App\Http\Controllers\SalePrintController::class, 'invoice'])->name('sales.print.invoice');
    Route::get('/sales/{sale}/print/waybill', [\App\Http\Controllers\SalePrintController::class, 'waybill'])->name('sales.print.waybill');

    Route::view('/companies', 'companies.index')->name('companies.index');
    Route::view('/company/profile', 'company.profile')->name('company.profile');
    Route::view('/counterparties', 'counterparties.index')->name('counterparties.index');
    Route::view('/inventory', 'inventory.index')->name('inventory.index');
    Route::view('/replenishment', 'replenishment.index')->name('replenishment.index');

    Route::get('/ap', [PageController::class, 'accountsPayable'])->name('ap.index');

    Route::view('/finance', 'finance.index')->name('finance.index');
    Route::view('/debts', 'debts.index')->name('debts.index');
    Route::get('/debts/{id}', [PageController::class, 'debtsShow'])->name('debts.show');

    Route::view('/pricing', 'pricing.index')->name('pricing.index');
    Route::view('/pricing/autopricing', 'pricing.autopricing')->name('pricing.autopricing');
    Route::view('/pricing/calculator', 'pricing.calculator')->name('pricing.calculator');

    // Subscription Plans (Public - can be accessed without auth)
    Route::view('/plans', 'plans.index')->withoutMiddleware('auth')->name('plans.index');

    // Marketplace Module
    Route::view('/marketplace', 'pages.marketplace.index')->name('marketplace.index');

    // PWA-optimized marketplace pages
    Route::view('/marketplace-pwa', 'pages.marketplace.index-pwa')->name('marketplace.pwa');
    Route::get('/marketplace-pwa/{accountId}', [PageController::class, 'marketplacePwaShow'])->name('marketplace.show.pwa');

    // Marketplace sync logs (admin) - должен быть ПЕРЕД {accountId}
    Route::get('/marketplace/sync-logs', [MarketplaceSyncLogController::class, 'index'])
        ->name('marketplace.sync-logs');
    Route::get('/marketplace/sync-logs/json', [MarketplaceSyncLogController::class, 'json'])
        ->name('marketplace.sync-logs.json');

    // Остатки МП — сводная страница по всем маркетплейсам
    Route::get('/marketplace/stocks', function () {
        return view('pages.marketplace.stocks');
    })->name('marketplace.stocks');

    Route::get('/marketplace/stocks/json', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $companyId = $user->company_id;

        $accountsQuery = \App\Models\MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true);

        if ($marketplace = $request->get('marketplace')) {
            $accountsQuery->where('marketplace', $marketplace);
        }

        $accounts = $accountsQuery->get(['id', 'name', 'marketplace']);
        $accountIds = $accounts->pluck('id')->toArray();

        if (empty($accountIds)) {
            return response()->json([
                'products' => [],
                'shops' => [],
                'accounts' => $accounts,
                'pagination' => ['total' => 0, 'per_page' => 50, 'current_page' => 1, 'last_page' => 1],
                'summary' => ['total_fbs' => 0, 'total_fbo' => 0, 'total_additional' => 0, 'total_sold' => 0, 'total_returned' => 0, 'total_products' => 0, 'zero_stock_count' => 0, 'low_stock_count' => 0, 'total_fbs_value' => 0, 'total_fbo_value' => 0, 'total_stock_value' => 0],
            ]);
        }

        $financeSettings = \App\Models\Finance\FinanceSettings::getForCompany($companyId);

        // Себестоимость ТОЛЬКО из variant_marketplace_links → product_variants
        // Для товаров без связи — себестоимость не определяется (не гадаем по SKU/баркоду)
        $costPriceMap = [];
        $links = \DB::table('variant_marketplace_links as vml')
            ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('vml.marketplace_account_id', $accountIds)
            ->select('vml.marketplace_product_id', 'vml.last_stock_synced', 'pv.purchase_price', 'pv.purchase_price_currency')
            ->get()
            ->groupBy('marketplace_product_id');
        foreach ($links as $productId => $productLinks) {
            $priced = $productLinks->filter(fn ($l) => (float) ($l->purchase_price ?? 0) > 0);
            if ($priced->isEmpty()) {
                continue;
            }
            $totalStock = (int) $priced->sum('last_stock_synced');
            if ($totalStock > 0) {
                $weightedSum = $priced->sum(function ($l) use ($financeSettings) {
                    $pp = (float) $l->purchase_price;
                    $pc = $l->purchase_price_currency ?? 'UZS';
                    $costUzs = $pc === 'UZS' ? $pp : $financeSettings->convertToBase($pp, $pc);

                    return $costUzs * (int) ($l->last_stock_synced ?? 0);
                });
                $costPriceMap[$productId] = $weightedSum / $totalStock;
            } else {
                $sum = $priced->sum(function ($l) use ($financeSettings) {
                    $pp = (float) $l->purchase_price;
                    $pc = $l->purchase_price_currency ?? 'UZS';

                    return $pc === 'UZS' ? $pp : $financeSettings->convertToBase($pp, $pc);
                });
                $costPriceMap[$productId] = $sum / $priced->count();
            }
        }

        // Карта себестоимостей Uzum по каждому SKU (только для связанных вариантов)
        $uzumAccountIds = $accounts->where('marketplace', 'uzum')->pluck('id')->toArray();
        $uzumAllSkuCostMap = [];
        if (! empty($uzumAccountIds)) {
            $allUzumLinks = \DB::table('variant_marketplace_links as vml')
                ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
                ->whereIn('vml.marketplace_account_id', $uzumAccountIds)
                ->whereNotNull('vml.external_sku_id')
                ->select('vml.marketplace_product_id', 'vml.external_sku_id', 'pv.purchase_price', 'pv.purchase_price_currency')
                ->get();
            foreach ($allUzumLinks as $link) {
                $price = (float) ($link->purchase_price ?? 0);
                if ($price > 0) {
                    $currency = $link->purchase_price_currency ?? 'UZS';
                    $uzumAllSkuCostMap[$link->marketplace_product_id][$link->external_sku_id] =
                        $currency === 'UZS' ? $price : $financeSettings->convertToBase($price, $currency);
                }
            }
        }

        $query = \App\Models\MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
            ->select([
                'id', 'marketplace_account_id', 'title', 'preview_image', 'external_product_id',
                'shop_id', 'status', 'stock_fbs', 'stock_fbo', 'stock_additional',
                'quantity_sold', 'quantity_returned', 'last_synced_stock', 'last_synced_price',
                'purchase_price', 'purchase_price_currency', 'last_synced_at',
            ]);

        if ($accountId = $request->get('account_id')) {
            $query->where('marketplace_account_id', (int) $accountId);
        }

        if ($shopId = $request->get('shop_id')) {
            $query->where('shop_id', $shopId);
        }

        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_product_id', 'like', "%{$search}%");
            });
        }

        $stockFilter = $request->get('stock_filter', 'all');
        match ($stockFilter) {
            'zero' => $query->where(function ($q) {
                $q->where('stock_fbs', 0)->orWhere('stock_fbo', 0)->orWhereNull('stock_fbs')->orWhereNull('stock_fbo');
            }),
            'low' => $query->where(function ($q) {
                $q->where(function ($q2) {
                    $q2->where('stock_fbs', '>', 0)->where('stock_fbs', '<', 5);
                })->orWhere(function ($q2) {
                    $q2->where('stock_fbo', '>', 0)->where('stock_fbo', '<', 5);
                });
            }),
            'normal' => $query->where('stock_fbs', '>=', 5)->where('stock_fbo', '>=', 5),
            default => null,
        };

        $sortBy = $request->get('sort_by', 'title');
        $sortDir = $request->get('sort_dir', 'asc');
        $allowedSorts = ['title', 'stock_fbs', 'stock_fbo', 'quantity_sold', 'quantity_returned', 'last_synced_at', 'last_synced_price', 'stock_value'];
        if (in_array($sortBy, $allowedSorts) && $sortBy !== 'stock_value') {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        $summaryBase = \App\Models\MarketplaceProduct::whereIn('marketplace_account_id', $accountIds);
        if ($accountId) {
            $summaryBase->where('marketplace_account_id', (int) $accountId);
        }
        $raw = (clone $summaryBase)->selectRaw('
            COALESCE(SUM(stock_fbs), 0) as total_fbs,
            COALESCE(SUM(stock_fbo), 0) as total_fbo,
            COALESCE(SUM(stock_additional), 0) as total_additional,
            COALESCE(SUM(quantity_sold), 0) as total_sold,
            COALESCE(SUM(quantity_returned), 0) as total_returned,
            COUNT(*) as total_products,
            SUM(CASE WHEN COALESCE(stock_fbs, 0) = 0 OR COALESCE(stock_fbo, 0) = 0 THEN 1 ELSE 0 END) as zero_stock_count,
            SUM(CASE WHEN (stock_fbs > 0 AND stock_fbs < 5) OR (stock_fbo > 0 AND stock_fbo < 5) THEN 1 ELSE 0 END) as low_stock_count
        ')->first();

        // WB/Ozon/YM: per-вариант данные (только связанные варианты)
        $nonUzumVariantData = [];
        $nonUzumAccountIds = array_diff($accountIds, $uzumAccountIds);
        if (! empty($nonUzumAccountIds)) {
            $nonUzumAllLinks = \DB::table('variant_marketplace_links as vml')
                ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
                ->whereIn('vml.marketplace_account_id', $nonUzumAccountIds)
                ->whereNotNull('pv.purchase_price')
                ->where('pv.purchase_price', '>', 0)
                ->whereNotNull('vml.last_stock_synced')
                ->where('vml.last_stock_synced', '>', 0)
                ->select('vml.marketplace_product_id', 'vml.last_stock_synced', 'pv.purchase_price', 'pv.purchase_price_currency')
                ->get();
            foreach ($nonUzumAllLinks as $link) {
                $pp = (float) $link->purchase_price;
                $pc = $link->purchase_price_currency ?? 'UZS';
                $costUzs = $pc === 'UZS' ? $pp : $financeSettings->convertToBase($pp, $pc);
                $nonUzumVariantData[$link->marketplace_product_id][] = [
                    'stock' => (int) $link->last_stock_synced,
                    'cost_uzs' => $costUzs,
                ];
            }
        }

        // Uzum: загрузить SKU-остатки для стоимостного расчёта (только связанные)
        $uzumProductIdsWithLinks = array_keys($uzumAllSkuCostMap);
        $uzumSkuStockData = [];
        if (! empty($uzumProductIdsWithLinks)) {
            $skuStockRows = \DB::table('marketplace_products')
                ->whereIn('id', $uzumProductIdsWithLinks)
                ->selectRaw("id, JSON_EXTRACT(raw_payload, '$.skuList') as sku_list")
                ->get();
            foreach ($skuStockRows as $row) {
                $uzumSkuStockData[$row->id] = is_string($row->sku_list)
                    ? (json_decode($row->sku_list, true) ?? [])
                    : [];
            }
        }

        $stockRows = (clone $summaryBase)->select('id', 'stock_fbs', 'stock_fbo')->get();
        $totalFbsValue = 0;
        $totalFboValue = 0;
        foreach ($stockRows as $row) {
            if (isset($uzumSkuStockData[$row->id])) {
                $skuCostMap = $uzumAllSkuCostMap[$row->id];
                foreach ($uzumSkuStockData[$row->id] as $sku) {
                    $skuId = (string) ($sku['skuId'] ?? '');
                    $cp = $skuCostMap[$skuId] ?? 0;
                    $totalFbsValue += (int) ($sku['quantityFbs'] ?? 0) * $cp;
                    $totalFboValue += (int) ($sku['quantityActive'] ?? 0) * $cp;
                }
            } elseif (isset($nonUzumVariantData[$row->id])) {
                $variantTotal = 0.0;
                foreach ($nonUzumVariantData[$row->id] as $v) {
                    $variantTotal += $v['stock'] * $v['cost_uzs'];
                }
                $stockFbs = (int) ($row->stock_fbs ?? 0);
                $stockFbo = (int) ($row->stock_fbo ?? 0);
                $totalMpStock = $stockFbs + $stockFbo;
                if ($totalMpStock > 0) {
                    $totalFbsValue += $variantTotal * $stockFbs / $totalMpStock;
                    $totalFboValue += $variantTotal * $stockFbo / $totalMpStock;
                } else {
                    $totalFbsValue += $variantTotal;
                }
            }
            // else: нет связи с внутренним товаром — стоимость не считаем
        }

        $summary = [
            'total_fbs' => (int) $raw->total_fbs,
            'total_fbo' => (int) $raw->total_fbo,
            'total_additional' => (int) $raw->total_additional,
            'total_sold' => (int) $raw->total_sold,
            'total_returned' => (int) $raw->total_returned,
            'total_products' => (int) $raw->total_products,
            'zero_stock_count' => (int) $raw->zero_stock_count,
            'low_stock_count' => (int) $raw->low_stock_count,
            'total_fbs_value' => $totalFbsValue,
            'total_fbo_value' => $totalFboValue,
            'total_stock_value' => $totalFbsValue + $totalFboValue,
        ];

        $products = $query->paginate((int) $request->get('per_page', 50));
        $productIds = collect($products->items())->pluck('id')->toArray();

        // Варианты через variant_marketplace_links (только связанные)
        $variantsMap = \DB::table('variant_marketplace_links as vml')
            ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('vml.marketplace_product_id', $productIds)
            ->select(
                'vml.marketplace_product_id', 'pv.id as variant_id', 'pv.sku',
                'pv.option_values_summary', 'pv.purchase_price', 'pv.purchase_price_currency',
                'vml.last_stock_synced', 'vml.last_price_synced'
            )
            ->get()
            ->groupBy('marketplace_product_id');

        // Варианты Uzum из raw_payload.skuList + себестоимость только из связанных вариантов
        $uzumRawData = [];
        $uzumSkuCostMap = [];
        if (! empty($uzumAccountIds) && ! empty($productIds)) {
            $uzumRows = \DB::table('marketplace_products')
                ->whereIn('id', $productIds)
                ->whereIn('marketplace_account_id', $uzumAccountIds)
                ->select('id', 'raw_payload')
                ->get();
            foreach ($uzumRows as $row) {
                $payload = is_string($row->raw_payload) ? json_decode($row->raw_payload, true) : [];
                $uzumRawData[$row->id] = $payload['skuList'] ?? [];
            }

            $uzumLinks = \DB::table('variant_marketplace_links as vml')
                ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
                ->whereIn('vml.marketplace_product_id', $productIds)
                ->whereIn('vml.marketplace_account_id', $uzumAccountIds)
                ->whereNotNull('vml.external_sku_id')
                ->select('vml.marketplace_product_id', 'vml.external_sku_id', 'pv.id as variant_id', 'pv.purchase_price', 'pv.purchase_price_currency')
                ->get();
            foreach ($uzumLinks as $link) {
                $uzumSkuCostMap[$link->marketplace_product_id][$link->external_sku_id] = [
                    'variant_id' => $link->variant_id,
                    'purchase_price' => (float) ($link->purchase_price ?? 0),
                    'purchase_price_currency' => $link->purchase_price_currency ?? 'UZS',
                ];
            }
        }

        $products->getCollection()->transform(function ($product) use ($costPriceMap, $variantsMap, $financeSettings, $uzumAccountIds, $uzumRawData, $uzumSkuCostMap) {
            $product->cost_price = $costPriceMap[$product->id] ?? 0;
            $isUzum = in_array($product->marketplace_account_id, $uzumAccountIds);

            if ($isUzum && isset($uzumRawData[$product->id]) && ! empty($uzumRawData[$product->id])) {
                $skuLinks = $uzumSkuCostMap[$product->id] ?? [];
                $product->variants = collect($uzumRawData[$product->id])
                    ->map(function ($sku) use ($skuLinks, $financeSettings) {
                        $skuId = (string) ($sku['skuId'] ?? '');
                        $link = $skuLinks[$skuId] ?? null;

                        if ($link && $link['purchase_price'] > 0) {
                            $pp = $link['purchase_price'];
                            $pc = $link['purchase_price_currency'];
                            $skuCostUzs = $pc === 'UZS' ? $pp : $financeSettings->convertToBase($pp, $pc);
                            $linkedVariantId = $link['variant_id'];
                        } else {
                            // Нет связи с внутренним вариантом — себестоимость не показываем
                            $pp = 0;
                            $pc = 'UZS';
                            $skuCostUzs = 0;
                            $linkedVariantId = null;
                        }

                        return [
                            'variant_id' => $linkedVariantId,
                            'sku_id' => $skuId,
                            'sku' => (string) ($sku['barcode'] ?? $skuId),
                            'option_values_summary' => $sku['skuTitle'] ?? $sku['skuFullTitle'] ?? null,
                            'stock_fbs' => (int) ($sku['quantityFbs'] ?? 0),
                            'stock_fbo' => (int) ($sku['quantityActive'] ?? 0),
                            'stock_additional' => (int) ($sku['quantityAdditional'] ?? 0),
                            'quantity_sold' => (int) ($sku['quantitySold'] ?? 0),
                            'quantity_returned' => (int) ($sku['quantityReturned'] ?? 0),
                            'price' => (float) ($sku['price'] ?? 0),
                            'purchase_price' => $pp,
                            'purchase_price_currency' => $pc,
                            'cost_price' => $skuCostUzs,
                            'is_uzum_sku' => true,
                        ];
                    })->values()->toArray();
            } else {
                $product->variants = ($variantsMap->get($product->id) ?? collect())
                    ->map(function ($v) use ($financeSettings) {
                        $price = (float) ($v->purchase_price ?? 0);
                        $currency = $v->purchase_price_currency ?? 'UZS';
                        $vCostUzs = $price > 0
                            ? ($currency === 'UZS' ? $price : $financeSettings->convertToBase($price, $currency))
                            : 0;

                        return [
                            'variant_id' => $v->variant_id,
                            'sku_id' => null,
                            'sku' => $v->sku,
                            'option_values_summary' => $v->option_values_summary,
                            'stock_fbs' => $v->last_stock_synced !== null ? (int) $v->last_stock_synced : null,
                            'stock_fbo' => null,
                            'stock_additional' => null,
                            'quantity_sold' => null,
                            'quantity_returned' => null,
                            'price' => $v->last_price_synced !== null ? (float) $v->last_price_synced : null,
                            'purchase_price' => $price,
                            'purchase_price_currency' => $currency,
                            'cost_price' => $vCostUzs,
                            'is_uzum_sku' => false,
                        ];
                    })->values()->toArray();
            }

            return $product;
        });

        $shops = \App\Models\MarketplaceShop::whereIn('marketplace_account_id', $accountIds)
            ->get(['external_id', 'name', 'marketplace_account_id']);

        return response()->json([
            'products' => $products->items(),
            'shops' => $shops,
            'accounts' => $accounts,
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
            'summary' => $summary,
        ]);
    })->name('marketplace.stocks.json');

    // Сохранить себестоимость варианта (только через связанный внутренний товар)
    Route::post('/marketplace/stocks/variant-cost-price', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'variant_id' => 'required|integer',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_price_currency' => 'nullable|string|in:UZS,USD,RUB,EUR',
        ]);

        $user = auth()->user();
        $variant = \App\Models\ProductVariant::where('id', $request->variant_id)
            ->where('company_id', $user->company_id)
            ->firstOrFail();

        $variant->update([
            'purchase_price' => $request->purchase_price,
            'purchase_price_currency' => $request->purchase_price_currency ?? 'UZS',
        ]);

        $price = (float) $request->purchase_price;
        $currency = $request->purchase_price_currency ?? 'UZS';
        if ($currency !== 'UZS' && $price > 0) {
            $settings = \App\Models\Finance\FinanceSettings::getForCompany($user->company_id);
            $price = $settings->convertToBase($price, $currency);
        }

        return response()->json([
            'success' => true,
            'cost_price' => $price,
            'purchase_price' => (float) $request->purchase_price,
            'purchase_price_currency' => $currency,
        ]);
    })->name('marketplace.stocks.variant-cost-price');

    Route::get('/marketplace/{accountId}', [PageController::class, 'marketplaceShow'])->name('marketplace.show');
    Route::get('/marketplace/{accountId}/products', [MarketplaceWebController::class, 'products'])->name('marketplace.products');

    // JSON для UI (чтение из БД без повторной синхронизации)
    Route::get('/marketplace/{accountId}/products/json', [MarketplaceWebController::class, 'productsJson'])->name('marketplace.products.json');

    // Деталь продукта с raw_payload по ID
    Route::get('/marketplace/{accountId}/products/{productId}/json', [MarketplaceWebController::class, 'productJson'])->name('marketplace.products.show.json');

    // Generic orders route - redirects to marketplace-specific page
    Route::get('/marketplace/{accountId}/orders', [MarketplaceWebController::class, 'orders'])->name('marketplace.orders');

    // Explicit URL by marketplace - redirects to dedicated page
    Route::get('/marketplace/{accountId}/{marketplace}/orders', [MarketplaceWebController::class, 'ordersSpecific'])->name('marketplace.orders.specific');

    Route::get('/marketplace/{accountId}/supplies', [MarketplaceWebController::class, 'supplies'])->name('marketplace.supplies');
    Route::get('/marketplace/{accountId}/passes', [MarketplaceWebController::class, 'passes'])->name('marketplace.passes');

    // Wildberries Settings
    Route::get('/marketplace/{accountId}/wb-settings', [MarketplaceWebController::class, 'wbSettings'])->name('marketplace.wb-settings');

    // Wildberries Products (cards)
    Route::get('/marketplace/{accountId}/wb-products', [MarketplaceWebController::class, 'wbProducts'])->name('marketplace.wb-products');

    // Wildberries FBS Orders (new dedicated page)
    Route::get('/marketplace/{accountId}/wb-orders', [MarketplaceWebController::class, 'wbOrders'])->name('marketplace.wb-orders');

    // Uzum Settings
    Route::get('/marketplace/{accountId}/uzum-settings', [MarketplaceWebController::class, 'uzumSettings'])->name('marketplace.uzum-settings');

    // Yandex Market Settings
    Route::get('/marketplace/{accountId}/ym-settings', [MarketplaceWebController::class, 'ymSettings'])->name('marketplace.ym-settings');

    // Yandex Market Orders
    Route::get('/marketplace/{accountId}/ym-orders', [MarketplaceWebController::class, 'ymOrders'])->name('marketplace.ym-orders');

    // Yandex Market Orders JSON
    Route::get('/marketplace/{accountId}/ym-orders/json', [MarketplaceWebController::class, 'ymOrdersJson'])->name('marketplace.ym-orders.json');

    // Uzum FBS Orders (new dedicated page with brand design)
    Route::get('/marketplace/{accountId}/uzum-orders', [MarketplaceWebController::class, 'uzumOrders'])->name('marketplace.uzum-orders');

    // Uzum Reviews
    Route::get('/marketplace/{accountId}/uzum-reviews', [MarketplaceWebController::class, 'uzumReviews'])->name('marketplace.uzum-reviews');

    // Ozon Settings
    Route::get('/marketplace/{accountId}/ozon-settings', [MarketplaceWebController::class, 'ozonSettings'])->name('marketplace.ozon-settings');

    // Ozon Products
    Route::get('/marketplace/{accountId}/ozon-products', [MarketplaceWebController::class, 'ozonProducts'])->name('marketplace.ozon-products');

    // Ozon Orders
    Route::get('/marketplace/{accountId}/ozon-orders', [MarketplaceWebController::class, 'ozonOrders'])->name('marketplace.ozon-orders');

    // Ozon Orders JSON API
    Route::get('/marketplace/{accountId}/ozon-orders/json', [MarketplaceWebController::class, 'ozonOrdersJson'])->name('marketplace.ozon-orders.json');

    if (env('VPC_ENABLED', false)) {
        Route::get('/vpc-sessions', [VpcSessionController::class, 'index'])->name('vpc_sessions.index');
        Route::get('/vpc-sessions/create', [VpcSessionController::class, 'create'])->name('vpc_sessions.create');
        Route::post('/vpc-sessions', [VpcSessionController::class, 'store'])->name('vpc_sessions.store');
        Route::get('/vpc-sessions/{vpcSession}', [VpcSessionController::class, 'show'])->name('vpc_sessions.show');
        Route::post('/vpc-sessions/{vpcSession}/start', [VpcSessionController::class, 'start'])->name('vpc_sessions.start');
        Route::post('/vpc-sessions/{vpcSession}/stop', [VpcSessionController::class, 'stop'])->name('vpc_sessions.stop');
        Route::post('/vpc-sessions/{vpcSession}/pause', [VpcSessionController::class, 'pause'])->name('vpc_sessions.pause');
        Route::post('/vpc-sessions/{vpcSession}/resume', [VpcSessionController::class, 'resume'])->name('vpc_sessions.resume');
        Route::post('/vpc-sessions/{vpcSession}/control-mode', [VpcSessionController::class, 'setControlMode'])->name('vpc_sessions.control_mode');
        Route::delete('/vpc-sessions/{vpcSession}', [VpcSessionController::class, 'destroy'])->name('vpc_sessions.destroy');

        // VPC Control API (для ручного управления через JS)
        Route::post('/vpc-sessions/{vpcSession}/actions', [VpcControlApiController::class, 'sendAction'])->name('vpc_sessions.actions');
        Route::get('/vpc-sessions/{vpcSession}/status', [VpcControlApiController::class, 'getStatus'])->name('vpc_sessions.status');
        Route::get('/vpc-sessions/{vpcSession}/actions-list', [VpcControlApiController::class, 'getActions'])->name('vpc_sessions.actions_list');
        Route::post('/vpc-sessions/{vpcSession}/control-mode-api', [VpcControlApiController::class, 'setControlMode'])->name('vpc_sessions.control_mode_api');
    }
    // Payment routes (protected)
    Route::get('/payment/subscription/{subscription}', [\App\Http\Controllers\PaymentController::class, 'initiate'])
        ->name('payment.initiate');
    Route::post('/payment/click/{subscription}', [\App\Http\Controllers\PaymentController::class, 'initiateClick'])
        ->name('payment.initiate.click');
    Route::post('/payment/payme/{subscription}', [\App\Http\Controllers\PaymentController::class, 'initiatePayme'])
        ->name('payment.initiate.payme');
    Route::get('/payment/callback/click/{subscription}', [\App\Http\Controllers\PaymentController::class, 'callbackClick'])
        ->name('payment.callback.click');
    Route::get('/payment/callback/payme/{subscription}', [\App\Http\Controllers\PaymentController::class, 'callbackPayme'])
        ->name('payment.callback.payme');
    Route::get('/payment/renew/{subscription}', [\App\Http\Controllers\PaymentController::class, 'renew'])
        ->name('payment.renew');
    // Store Builder — Admin pages
    Route::prefix('my-store')->name('store.')->group(function () {
        Route::view('/', 'store.admin.dashboard')->name('dashboard');
        Route::get('/{storeId}/theme', [PageController::class, 'storeTheme'])->name('theme');
        Route::get('/{storeId}/catalog', [PageController::class, 'storeCatalog'])->name('catalog');
        Route::get('/{storeId}/delivery', [PageController::class, 'storeDelivery'])->name('delivery');
        Route::get('/{storeId}/payment', [PageController::class, 'storePayment'])->name('payment');
        Route::get('/{storeId}/orders', [PageController::class, 'storeOrders'])->name('orders');
        Route::get('/{storeId}/orders/{orderId}', [PageController::class, 'storeOrderShow'])->name('orders.show');
        Route::get('/{storeId}/pages', [PageController::class, 'storePages'])->name('pages');
        Route::get('/{storeId}/analytics', [PageController::class, 'storeAnalytics'])->name('analytics');
        Route::get('/{storeId}/banners', [PageController::class, 'storeBanners'])->name('banners');
    });
}); // End of auth middleware group

// Storefront — Public pages (no auth)
Route::prefix('store/{slug}')->group(function () {
    Route::get('/', [\App\Http\Controllers\Storefront\StorefrontController::class, 'home'])->name('storefront.home');
    Route::get('/catalog', [\App\Http\Controllers\Storefront\CatalogController::class, 'index'])->name('storefront.catalog');
    Route::get('/product/{productId}', [\App\Http\Controllers\Storefront\CatalogController::class, 'show'])->name('storefront.product');
    Route::get('/cart', [\App\Http\Controllers\Storefront\CartController::class, 'index'])->name('storefront.cart');
    Route::get('/checkout', [\App\Http\Controllers\Storefront\CheckoutController::class, 'index'])->name('storefront.checkout');
    Route::get('/order/{orderNumber}', [\App\Http\Controllers\Storefront\CheckoutController::class, 'orderStatus'])->name('storefront.order');
    Route::get('/page/{pageSlug}', [\App\Http\Controllers\Storefront\StorefrontController::class, 'page'])->name('storefront.page');
    Route::get('/payment/success', [\App\Http\Controllers\Storefront\PaymentController::class, 'success'])->name('storefront.payment.success');
    Route::get('/payment/fail', [\App\Http\Controllers\Storefront\PaymentController::class, 'fail'])->name('storefront.payment.fail');

    // Storefront API (cart, checkout, payment — JSON endpoints)
    Route::get('/api/cart', [\App\Http\Controllers\Storefront\CartController::class, 'show']);
    Route::post('/api/cart/add', [\App\Http\Controllers\Storefront\CartController::class, 'add']);
    Route::put('/api/cart/update', [\App\Http\Controllers\Storefront\CartController::class, 'update']);
    Route::delete('/api/cart/remove', [\App\Http\Controllers\Storefront\CartController::class, 'remove']);
    Route::delete('/api/cart/clear', [\App\Http\Controllers\Storefront\CartController::class, 'clear']);
    Route::post('/api/cart/promocode', [\App\Http\Controllers\Storefront\CartController::class, 'applyPromocode']);
    Route::delete('/api/cart/promocode', [\App\Http\Controllers\Storefront\CartController::class, 'removePromocode']);
    Route::post('/api/checkout', [\App\Http\Controllers\Storefront\CheckoutController::class, 'store']);
    Route::post('/api/payment/{orderId}/initiate', [\App\Http\Controllers\Storefront\PaymentController::class, 'initiate']);
});

// Payment webhooks (public, no CSRF)
Route::post('/webhooks/click/prepare', [\App\Http\Controllers\Webhooks\ClickWebhookController::class, 'prepare'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.click.prepare');
Route::post('/webhooks/click/complete', [\App\Http\Controllers\Webhooks\ClickWebhookController::class, 'complete'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.click.complete');
Route::post('/webhooks/payme', [\App\Http\Controllers\Webhooks\PaymeWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])
    ->name('webhook.payme');

// Telegram webhook (public, no auth required)
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
