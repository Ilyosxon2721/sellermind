<?php

use App\Http\Controllers\MarketplaceSyncLogController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\VpcControlApiController;
use App\Http\Controllers\VpcSessionController;
use App\Http\Controllers\Web\Products\ProductWebController;
use App\Http\Controllers\Web\Warehouse\WarehouseController;
// VPC Sessions - DISABLED: Module not complete, enable via VPC_ENABLED=true in .env
use App\Models\AP\Supplier;
use Illuminate\Support\Facades\Route;

// Localized Landing Pages
Route::get('/uz', function (Illuminate\Http\Request $request) {
    App::setLocale('uz');
    $plans = \App\Models\Plan::where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    return view('welcome', compact('plans'));
})->name('home.uz');

Route::get('/ru', function (Illuminate\Http\Request $request) {
    App::setLocale('ru');
    $plans = \App\Models\Plan::where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    return view('welcome', compact('plans'));
})->name('home.ru');

Route::get('/en', function (Illuminate\Http\Request $request) {
    App::setLocale('en');
    $plans = \App\Models\Plan::where('is_active', true)
        ->orderBy('sort_order')
        ->get();

    return view('welcome', compact('plans'));
})->name('home.en');

// Localized auth routes
Route::prefix('{locale}')->whereIn('locale', ['uz', 'ru', 'en'])->group(function () {
    Route::get('/login', function ($locale) {
        App::setLocale($locale);

        return view('pages.login');
    })->name('login.localized');

    Route::get('/register', function ($locale) {
        App::setLocale($locale);

        return view('pages.register');
    })->name('register.localized');
});

// Root redirect to Russian
Route::get('/', function (Illuminate\Http\Request $request) {
    if (auth()->check()) {
        return redirect('/dashboard');
    }

    return redirect('/ru');
})->name('home');

Route::get('/login', function () {
    return view('pages.login');
})->name('login');

Route::get('/register', function () {
    return view('pages.register');
})->name('register');

// Health check for PWA offline detection
Route::get('/api/health', function () {
    return response()->json(['status' => 'ok'], 200);
});

// Offline page for PWA
Route::get('/offline', function () {
    return view('offline');
})->name('offline');

// Auth API routes (in web.php for proper session cookie handling)
// These MUST be in web.php, not api.php, for session cookies to work correctly
Route::prefix('api/auth')->middleware('throttle:auth')->group(function () {
    Route::post('register', [\App\Http\Controllers\Api\AuthController::class, 'register']);
    Route::post('login', [\App\Http\Controllers\Api\AuthController::class, 'login']);
});

// App pages - Dashboard is the main page after login (protected by auth middleware)
Route::middleware('auth.any')->group(function () {
    Route::get('/home', function () {
        return redirect()->route('dashboard');
    });

    Route::get('/dashboard', function () {
        return view('pages.dashboard');
    })->name('dashboard');

    Route::get('/chat', function () {
        return view('pages.chat');
    })->name('chat');

    Route::get('/settings', function () {
        return view('pages.settings');
    })->name('settings');

    Route::get('/profile', function () {
        return view('pages.profile');
    })->name('profile');

    // Integrations
    Route::get('/integrations', [\App\Http\Controllers\Web\IntegrationController::class, 'index'])
        ->name('integrations.index');
    Route::get('/integrations/risment', [\App\Http\Controllers\Web\IntegrationController::class, 'risment'])
        ->name('integrations.risment');

    // RISMENT Integration Link page (legacy redirect)
    Route::get('/integration/risment', function () {
        return redirect()->route('integrations.risment');
    })->name('integration.risment');

    Route::get('/promotions', function () {
        return view('pages.promotions');
    })->name('promotions');

    Route::get('/analytics', function () {
        return view('pages.analytics');
    })->name('analytics');

    // Analytics sub-pages
    Route::prefix('analytics')->name('analytics.')->group(function () {
        Route::get('/revenue', function () {
            return view('pages.analytics');
        })->name('revenue');

        Route::get('/products', function () {
            return view('pages.analytics');
        })->name('products');

        Route::get('/abc', function () {
            return view('pages.analytics');
        })->name('abc');

        Route::get('/pnl', function () {
            return view('pages.analytics');
        })->name('pnl');

        Route::get('/stock', function () {
            return view('pages.analytics');
        })->name('stock');

        Route::get('/funnel', function () {
            return view('pages.analytics');
        })->name('funnel');
    });

    Route::get('/reviews', function () {
        return view('pages.reviews');
    })->name('reviews');

    Route::get('/products/categories', [\App\Http\Controllers\Web\CategoryController::class, 'index'])->name('web.categories.index');

    // Комплекты
    Route::prefix('bundles')->name('web.bundles.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Web\Products\BundleWebController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Web\Products\BundleWebController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [\App\Http\Controllers\Web\Products\BundleWebController::class, 'edit'])->name('edit');
    });

    Route::prefix('products')->name('web.products.')->group(function () {
        Route::get('/', [ProductWebController::class, 'index'])->name('index');
        Route::get('/purchase-prices', function () {
            return view('products.purchase-prices');
        })->name('purchase-prices');
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
        Route::get('/create', function () {
            return view('warehouse.warehouse-create');
        })->name('warehouse.create');
        Route::get('/{id}/edit', function ($id) {
            return view('warehouse.warehouse-edit', ['warehouseId' => $id]);
        })->name('warehouse.edit');
        Route::get('/documents', [WarehouseController::class, 'documents'])->name('documents');
        Route::get('/documents/{id}', [WarehouseController::class, 'document'])->name('documents.show');
        Route::get('/reservations', [WarehouseController::class, 'reservations'])->name('reservations');
        Route::get('/ledger', function () {
            $controller = app(WarehouseController::class);

            return $controller->ledger(request());
        })->name('ledger');

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
        Route::get('/create', function () {
            return view('warehouse.warehouse-create');
        })->name('cabinet.warehouse.create');
        Route::get('/{id}/edit', function ($id) {
            return view('warehouse.warehouse-edit', ['warehouseId' => $id]);
        })->name('cabinet.warehouse.edit');
        Route::get('/documents', [WarehouseController::class, 'documents'])->name('cabinet.warehouse.documents');
        Route::get('/documents/{id}', [WarehouseController::class, 'document'])->name('cabinet.warehouse.documents.show');
        Route::get('/reservations', [WarehouseController::class, 'reservations'])->name('cabinet.warehouse.reservations');
        Route::get('/ledger', function () {
            $controller = app(WarehouseController::class);

            return $controller->ledger(request());
        })->name('cabinet.warehouse.ledger');

        // Write-off
        Route::get('/write-off', [WarehouseController::class, 'writeOffs'])->name('cabinet.warehouse.write-offs');
        Route::get('/write-off/create', [WarehouseController::class, 'createWriteOff'])->name('cabinet.warehouse.write-off.create');
        Route::get('/write-off/{id}/edit', [WarehouseController::class, 'editWriteOff'])->name('cabinet.warehouse.write-off.edit');
        Route::get('/inventory/{id}/edit', [WarehouseController::class, 'editInventory'])->name('cabinet.warehouse.inventory.edit');
    });

    Route::get('/tasks', function () {
        return view('pages.tasks');
    })->name('tasks');

    // PWA-optimized tasks
    Route::get('/tasks-pwa', function () {
        return view('pages.tasks-pwa');
    })->name('tasks.pwa');

    // Agent Mode
    Route::get('/agent', function () {
        return view('pages.agent.index');
    })->name('agent.index');

    Route::get('/agent/create', function () {
        return view('pages.agent.create');
    })->name('agent.create');

    Route::get('/agent/{taskId}', function ($taskId) {
        return view('pages.agent.show', ['taskId' => $taskId]);
    })->name('agent.show');

    Route::get('/agent/run/{runId}', function ($runId) {
        return view('pages.agent.run', ['runId' => $runId]);
    })->name('agent.run');

    Route::get('/sales', function () {
        return view('sales.index');
    })->name('sales.index');

    Route::get('/sales/create', function () {
        return view('sales.create');
    })->name('sales.create');

    Route::get('/sales/{id}', function ($id) {
        return view('sales.show', ['orderId' => $id]);
    })->name('sales.show');

    // Sale print routes
    Route::get('/sales/{sale}/print/receipt', [\App\Http\Controllers\SalePrintController::class, 'receipt'])->name('sales.print.receipt');
    Route::get('/sales/{sale}/print/invoice', [\App\Http\Controllers\SalePrintController::class, 'invoice'])->name('sales.print.invoice');
    Route::get('/sales/{sale}/print/waybill', [\App\Http\Controllers\SalePrintController::class, 'waybill'])->name('sales.print.waybill');

    Route::get('/companies', function () {
        return view('companies.index');
    })->name('companies.index');

    Route::get('/company/profile', function () {
        return view('company.profile');
    })->name('company.profile');

    Route::get('/counterparties', function () {
        return view('counterparties.index');
    })->name('counterparties.index');

    Route::get('/inventory', function () {
        return view('inventory.index');
    })->name('inventory.index');

    Route::get('/replenishment', function () {
        return view('replenishment.index');
    })->name('replenishment.index');

    Route::get('/ap', function () {
        $companyId = auth()->user()?->company_id ?? \App\Models\Company::query()->value('id');
        $suppliers = Supplier::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('ap.index', [
            'suppliers' => $suppliers,
        ]);
    })->name('ap.index');

    Route::get('/finance', function () {
        return view('finance.index');
    })->name('finance.index');

    Route::get('/debts', function () {
        return view('debts.index');
    })->name('debts.index');

    Route::get('/debts/{id}', function ($id) {
        return view('debts.show', ['debtId' => $id]);
    })->name('debts.show');

    Route::get('/pricing', function () {
        return view('pricing.index');
    })->name('pricing.index');

    Route::get('/pricing/autopricing', function () {
        return view('pricing.autopricing');
    })->name('pricing.autopricing');

    Route::get('/pricing/calculator', function () {
        return view('pricing.calculator');
    })->name('pricing.calculator');

    // Subscription Plans (Public - can be accessed without auth)
    Route::get('/plans', function () {
        return view('plans.index');
    })->withoutMiddleware('auth')->name('plans.index');

    // Marketplace Module
    Route::get('/marketplace', function () {
        return view('pages.marketplace.index');
    })->name('marketplace.index');

    // PWA-optimized marketplace pages
    Route::get('/marketplace-pwa', function () {
        return view('pages.marketplace.index-pwa');
    })->name('marketplace.pwa');

    Route::get('/marketplace-pwa/{accountId}', function ($accountId) {
        return view('pages.marketplace.show-pwa', ['accountId' => $accountId]);
    })->name('marketplace.show.pwa');

    // Marketplace stocks dashboard - должен быть ПЕРЕД {accountId}
    Route::get('/marketplace/stocks', function () {
        return view('pages.marketplace-stocks');
    })->name('marketplace.stocks');

    // Marketplace sync logs (admin) - должен быть ПЕРЕД {accountId}
    Route::get('/marketplace/sync-logs', [MarketplaceSyncLogController::class, 'index'])
        ->name('marketplace.sync-logs');
    Route::get('/marketplace/sync-logs/json', [MarketplaceSyncLogController::class, 'json'])
        ->name('marketplace.sync-logs.json');

    // Остатки МП - должен быть ПЕРЕД {accountId}
    Route::get('/marketplace/stocks', function () {
        return view('pages.marketplace.stocks');
    })->name('marketplace.stocks');

    Route::get('/marketplace/stocks/json', function (\Illuminate\Http\Request $request) {
        $user = auth()->user();
        $companyId = $user->company_id;

        // Получить все активные аккаунты компании (все маркетплейсы)
        $accountsQuery = \App\Models\MarketplaceAccount::where('company_id', $companyId)
            ->where('is_active', true);

        // Фильтр по маркетплейсу
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

        // Себестоимость: карта marketplace_product_id => cost_price в UZS
        $financeSettings = \App\Models\Finance\FinanceSettings::getForCompany($companyId);
        $costPriceMap = [];

        // 1. Из variant_marketplace_links → product_variants (приоритет)
        $links = \DB::table('variant_marketplace_links as vml')
            ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('vml.marketplace_account_id', $accountIds)
            ->select('vml.marketplace_product_id', 'pv.purchase_price', 'pv.purchase_price_currency')
            ->get();
        foreach ($links as $link) {
            $price = (float) ($link->purchase_price ?? 0);
            if ($price > 0) {
                $currency = $link->purchase_price_currency ?? 'UZS';
                $costPriceMap[$link->marketplace_product_id] = $currency === 'UZS'
                    ? $price
                    : $financeSettings->convertToBase($price, $currency);
            }
        }

        // 2. Fallback: из marketplace_products.purchase_price (для товаров без связи)
        $directPrices = \DB::table('marketplace_products')
            ->whereIn('marketplace_account_id', $accountIds)
            ->whereNotNull('purchase_price')
            ->where('purchase_price', '>', 0)
            ->select('id', 'purchase_price', 'purchase_price_currency')
            ->get();
        foreach ($directPrices as $dp) {
            if (!isset($costPriceMap[$dp->id])) {
                $price = (float) $dp->purchase_price;
                $currency = $dp->purchase_price_currency ?? 'UZS';
                $costPriceMap[$dp->id] = $currency === 'UZS'
                    ? $price
                    : $financeSettings->convertToBase($price, $currency);
            }
        }

        $query = \App\Models\MarketplaceProduct::whereIn('marketplace_account_id', $accountIds)
            ->select([
                'id', 'marketplace_account_id', 'title', 'preview_image', 'external_product_id',
                'shop_id', 'status', 'stock_fbs', 'stock_fbo', 'stock_additional',
                'quantity_sold', 'quantity_returned', 'last_synced_stock', 'last_synced_price',
                'purchase_price', 'purchase_price_currency', 'last_synced_at',
            ]);

        // Фильтр по аккаунту
        if ($accountId = $request->get('account_id')) {
            $query->where('marketplace_account_id', (int) $accountId);
        }

        // Фильтр по магазину
        if ($shopId = $request->get('shop_id')) {
            $query->where('shop_id', $shopId);
        }

        // Поиск
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_product_id', 'like', "%{$search}%");
            });
        }

        // Фильтр по остаткам
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

        // Сортировка
        $sortBy = $request->get('sort_by', 'title');
        $sortDir = $request->get('sort_dir', 'asc');
        $allowedSorts = ['title', 'stock_fbs', 'stock_fbo', 'quantity_sold', 'quantity_returned', 'last_synced_at', 'last_synced_price', 'stock_value'];
        if ($sortBy === 'stock_value') {
            $query->orderByRaw('(COALESCE(stock_fbs, 0) + COALESCE(stock_fbo, 0)) * COALESCE((SELECT pv.purchase_price FROM variant_marketplace_links vml JOIN product_variants pv ON pv.id = vml.product_variant_id WHERE vml.marketplace_product_id = marketplace_products.id LIMIT 1), 0) '.($sortDir === 'desc' ? 'DESC' : 'ASC'));
        } elseif (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortDir === 'desc' ? 'desc' : 'asc');
        }

        // Summary
        $summaryBase = \App\Models\MarketplaceProduct::whereIn('marketplace_account_id', $accountIds);
        if ($accountId) {
            $summaryBase->where('marketplace_account_id', (int) $accountId);
        }
        // Счётчики — SQL
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

        // Стоимость остатков по себестоимости — PHP (для конвертации валют)
        $stockRows = (clone $summaryBase)->select('id', 'stock_fbs', 'stock_fbo')->get();
        $totalFbsValue = 0;
        $totalFboValue = 0;
        foreach ($stockRows as $row) {
            $cp = $costPriceMap[$row->id] ?? 0;
            $totalFbsValue += ($row->stock_fbs ?? 0) * $cp;
            $totalFboValue += ($row->stock_fbo ?? 0) * $cp;
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

        // Загрузить варианты для пагинированных товаров
        $productIds = collect($products->items())->pluck('id')->toArray();

        // Варианты через variant_marketplace_links (для не-Uzum маркетплейсов)
        $variantsMap = \DB::table('variant_marketplace_links as vml')
            ->join('product_variants as pv', 'pv.id', '=', 'vml.product_variant_id')
            ->whereIn('vml.marketplace_product_id', $productIds)
            ->select('vml.marketplace_product_id', 'pv.id as variant_id', 'pv.sku', 'pv.option_values_summary', 'pv.purchase_price', 'pv.purchase_price_currency')
            ->get()
            ->groupBy('marketplace_product_id');

        // Варианты Uzum из raw_payload.skuList
        $uzumAccountIds = $accounts->where('marketplace', 'uzum')->pluck('id')->toArray();
        $uzumRawData = [];
        if (!empty($uzumAccountIds) && !empty($productIds)) {
            $uzumRows = \DB::table('marketplace_products')
                ->whereIn('id', $productIds)
                ->whereIn('marketplace_account_id', $uzumAccountIds)
                ->select('id', 'raw_payload')
                ->get();
            foreach ($uzumRows as $row) {
                $payload = is_string($row->raw_payload) ? json_decode($row->raw_payload, true) : [];
                $uzumRawData[$row->id] = $payload['skuList'] ?? [];
            }
        }

        // Добавить себестоимость и варианты к каждому товару
        $products->getCollection()->transform(function ($product) use ($costPriceMap, $variantsMap, $financeSettings, $uzumAccountIds, $uzumRawData) {
            $product->cost_price = $costPriceMap[$product->id] ?? 0;
            $costUzs = $product->cost_price;
            $productPurchasePrice = (float) ($product->purchase_price ?? 0);
            $productPurchaseCurrency = $product->purchase_price_currency ?? 'UZS';
            $isUzum = in_array($product->marketplace_account_id, $uzumAccountIds);

            if ($isUzum && isset($uzumRawData[$product->id]) && !empty($uzumRawData[$product->id])) {
                // Uzum: варианты из raw_payload.skuList
                $product->variants = collect($uzumRawData[$product->id])
                    ->map(function ($sku) use ($costUzs, $productPurchasePrice, $productPurchaseCurrency) {
                        return [
                            'variant_id'            => null,
                            'sku_id'                => (string) ($sku['skuId'] ?? ''),
                            'sku'                   => (string) ($sku['barcode'] ?? $sku['skuId'] ?? ''),
                            'option_values_summary' => $sku['skuTitle'] ?? $sku['skuFullTitle'] ?? null,
                            'stock_fbs'             => (int) ($sku['quantityFbs'] ?? 0),
                            'stock_fbo'             => (int) ($sku['quantityActive'] ?? 0),
                            'stock_additional'      => (int) ($sku['quantityAdditional'] ?? 0),
                            'quantity_sold'         => (int) ($sku['quantitySold'] ?? 0),
                            'quantity_returned'     => (int) ($sku['quantityReturned'] ?? 0),
                            'price'                 => (float) ($sku['price'] ?? 0),
                            'purchase_price'        => $productPurchasePrice,
                            'purchase_price_currency' => $productPurchaseCurrency,
                            'cost_price'            => $costUzs,
                            'is_uzum_sku'           => true,
                        ];
                    })->values()->toArray();
            } else {
                // Другие маркетплейсы: варианты из variant_marketplace_links
                $product->variants = ($variantsMap->get($product->id) ?? collect())
                    ->map(function ($v) use ($financeSettings) {
                        $price = (float) ($v->purchase_price ?? 0);
                        $currency = $v->purchase_price_currency ?? 'UZS';
                        $vCostUzs = $price > 0
                            ? ($currency === 'UZS' ? $price : $financeSettings->convertToBase($price, $currency))
                            : 0;
                        return [
                            'variant_id'            => $v->variant_id,
                            'sku_id'                => null,
                            'sku'                   => $v->sku,
                            'option_values_summary' => $v->option_values_summary,
                            'stock_fbs'             => null,
                            'stock_fbo'             => null,
                            'stock_additional'      => null,
                            'quantity_sold'         => null,
                            'quantity_returned'     => null,
                            'price'                 => null,
                            'purchase_price'        => $price,
                            'purchase_price_currency' => $currency,
                            'cost_price'            => $vCostUzs,
                            'is_uzum_sku'           => false,
                        ];
                    })->values()->toArray();
            }

            return $product;
        });

        // Магазины для фильтра
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

    // Сохранить себестоимость товара
    Route::post('/marketplace/stocks/cost-price', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'product_id' => 'required|integer',
            'purchase_price' => 'required|numeric|min:0',
            'purchase_price_currency' => 'nullable|string|in:UZS,USD,RUB,EUR',
        ]);

        $user = auth()->user();
        $product = \App\Models\MarketplaceProduct::where('id', $request->product_id)
            ->whereHas('account', fn ($q) => $q->where('company_id', $user->company_id))
            ->firstOrFail();

        $product->update([
            'purchase_price' => $request->purchase_price,
            'purchase_price_currency' => $request->purchase_price_currency ?? 'UZS',
        ]);

        // Вернуть себестоимость в UZS
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
    })->name('marketplace.stocks.cost-price');

    // Сохранить себестоимость варианта товара
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

    Route::get('/marketplace/{accountId}', function ($accountId) {
        return view('pages.marketplace.show', ['accountId' => $accountId]);
    })->name('marketplace.show');

    Route::get('/marketplace/{accountId}/products', function ($accountId) {
        $account = \App\Models\MarketplaceAccount::findOrFail($accountId);

        return view('pages.marketplace.products', [
            'accountId' => $accountId,
            'accountMarketplace' => $account->marketplace,
            'accountName' => $account->name,
        ]);
    })->name('marketplace.products');

    // JSON для UI (чтение из БД без повторной синхронизации)
    Route::get('/marketplace/{accountId}/products/json', function (\Illuminate\Http\Request $request, $accountId) {
        $perPage = (int) $request->get('per_page', 50);
        $perPage = max(1, min($perPage, 100));
        $search = $request->get('search', '');
        $shopId = $request->get('shop_id', '');

        $query = \App\Models\MarketplaceProduct::where('marketplace_account_id', $accountId)
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
            ]);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('external_offer_id', 'like', "%{$search}%")
                    ->orWhere('external_sku', 'like', "%{$search}%");
            });
        }

        if ($shopId) {
            $query->where('shop_id', $shopId);
        }

        $products = $query->orderByDesc('id')->paginate($perPage);

        // Get linked variants for these products
        $productIds = collect($products->items())->pluck('id')->toArray();
        $links = \App\Models\VariantMarketplaceLink::whereIn('marketplace_product_id', $productIds)
            ->where('is_active', true)
            ->with(['variant:id,sku,stock_default,option_values_summary', 'variant.product:id,name'])
            ->get()
            ->keyBy('marketplace_product_id');

        // Map products with linked variant info
        $productsWithLinks = collect($products->items())->map(function ($product) use ($links) {
            $arr = $product->toArray();
            $link = $links->get($product->id);
            if ($link && $link->variant) {
                $arr['linked_variant'] = [
                    'id' => $link->variant->id,
                    'sku' => $link->variant->sku,
                    'name' => $link->variant->product?->name,
                    'stock' => $link->variant->stock_default,
                    'options' => $link->variant->option_values_summary,
                ];
            }

            return $arr;
        })->values();

        // Shops lookup (name by external_id)
        $shops = \App\Models\MarketplaceShop::where('marketplace_account_id', $accountId)
            ->get(['external_id', 'name']);

        return response()->json([
            'products' => $productsWithLinks,
            'shops' => $shops,
            'pagination' => [
                'total' => $products->total(),
                'per_page' => $products->perPage(),
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
            ],
        ]);
    })->name('marketplace.products.json');

    // Деталь продукта с raw_payload по ID
    Route::get('/marketplace/{accountId}/products/{productId}/json', function (\Illuminate\Http\Request $request, $accountId, $productId) {
        $product = \App\Models\MarketplaceProduct::where('marketplace_account_id', $accountId)
            ->findOrFail($productId);

        return response()->json([
            'product' => $product->only([
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
                'raw_payload',
            ]),
        ]);
    })->name('marketplace.products.show.json');

    // Generic orders route - redirects to marketplace-specific page
    Route::get('/marketplace/{accountId}/orders', function ($accountId) {
        $account = \App\Models\MarketplaceAccount::findOrFail($accountId);

        // Redirect to marketplace-specific orders page
        return match ($account->marketplace) {
            'wb' => redirect()->route('marketplace.wb-orders', $accountId),
            'uzum' => redirect()->route('marketplace.uzum-orders', $accountId),
            'ozon' => redirect()->route('marketplace.ozon-orders', $accountId),
            'ym' => redirect()->route('marketplace.ym-orders', $accountId),
            default => abort(404, 'Unsupported marketplace'),
        };
    })->name('marketplace.orders');

    // Explicit URL by marketplace - redirects to dedicated page
    Route::get('/marketplace/{accountId}/{marketplace}/orders', function ($accountId, $marketplace) {
        $account = \App\Models\MarketplaceAccount::findOrFail($accountId);
        if (strtolower($marketplace) !== strtolower($account->marketplace)) {
            abort(404);
        }

        return match ($account->marketplace) {
            'wb' => redirect()->route('marketplace.wb-orders', $accountId),
            'uzum' => redirect()->route('marketplace.uzum-orders', $accountId),
            'ozon' => redirect()->route('marketplace.ozon-orders', $accountId),
            'ym' => redirect()->route('marketplace.ym-orders', $accountId),
            default => abort(404, 'Unsupported marketplace'),
        };
    })->name('marketplace.orders.specific');

    Route::get('/marketplace/{accountId}/supplies', function ($accountId) {
        return view('pages.marketplace.supplies', ['accountId' => $accountId]);
    })->name('marketplace.supplies');

    Route::get('/marketplace/{accountId}/passes', function ($accountId) {
        return view('pages.marketplace.passes', ['accountId' => $accountId]);
    })->name('marketplace.passes');

    // Wildberries Settings
    Route::get('/marketplace/{accountId}/wb-settings', function ($accountId) {
        return view('pages.marketplace.wb-settings', ['accountId' => $accountId]);
    })->name('marketplace.wb-settings');

    // Wildberries Products (cards)
    Route::get('/marketplace/{accountId}/wb-products', function ($accountId) {
        return view('pages.marketplace.wb-products', ['accountId' => $accountId]);
    })->name('marketplace.wb-products');

    // Wildberries FBS Orders (new dedicated page)
    Route::get('/marketplace/{accountId}/wb-orders', function ($accountId) {
        $account = \App\Models\MarketplaceAccount::findOrFail($accountId);

        return view('pages.marketplace.wb-orders', [
            'accountId' => $accountId,
            'accountName' => $account->name,
        ]);
    })->name('marketplace.wb-orders');

    // Uzum Settings
    Route::get('/marketplace/{accountId}/uzum-settings', function ($accountId) {
        return view('pages.marketplace.uzum-settings', ['accountId' => $accountId]);
    })->name('marketplace.uzum-settings');

    // Yandex Market Settings
    Route::get('/marketplace/{accountId}/ym-settings', function ($accountId) {
        return view('pages.marketplace.ym-settings', ['accountId' => $accountId]);
    })->name('marketplace.ym-settings');

    // Yandex Market Orders
    Route::get('/marketplace/{accountId}/ym-orders', function ($accountId) {
        return view('pages.marketplace.ym-orders', ['accountId' => $accountId]);
    })->name('marketplace.ym-orders');

    // Yandex Market Orders JSON
    Route::get('/marketplace/{accountId}/ym-orders/json', function (\Illuminate\Http\Request $request, $accountId) {
        $query = \App\Models\YandexMarketOrder::where('marketplace_account_id', $accountId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('order_id', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at_ym', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'orders' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    })->name('marketplace.ym-orders.json');

    // Uzum FBS Orders (new dedicated page with brand design)
    Route::get('/marketplace/{accountId}/uzum-orders', function ($accountId) {
        $account = \App\Models\MarketplaceAccount::findOrFail($accountId);
        $uzumShops = \App\Models\MarketplaceShop::where('marketplace_account_id', $accountId)
            ->orderBy('name')
            ->get(['id', 'external_id', 'name']);

        return view('pages.marketplace.uzum-fbs-orders', [
            'accountId' => $accountId,
            'accountName' => $account->name,
            'uzumShops' => $uzumShops,
        ]);
    })->name('marketplace.uzum-orders');

    // Uzum Reviews
    Route::get('/marketplace/{accountId}/uzum-reviews', function ($accountId) {
        $account = \App\Models\MarketplaceAccount::findOrFail($accountId);
        return view('pages.marketplace.uzum-reviews', [
            'accountId' => $accountId,
            'accountName' => $account->name,
        ]);
    })->name('marketplace.uzum-reviews');

    // Ozon Settings
    Route::get('/marketplace/{accountId}/ozon-settings', function ($accountId) {
        return view('pages.marketplace.ozon-settings', ['accountId' => $accountId]);
    })->name('marketplace.ozon-settings');

    // Ozon Products
    Route::get('/marketplace/{accountId}/ozon-products', function ($accountId) {
        return view('pages.marketplace.partials.products_ozon', ['accountId' => $accountId]);
    })->name('marketplace.ozon-products');

    // Ozon Orders
    Route::get('/marketplace/{accountId}/ozon-orders', function ($accountId) {
        return view('pages.marketplace.ozon-orders', ['accountId' => $accountId]);
    })->name('marketplace.ozon-orders');

    // Ozon Orders JSON API
    Route::get('/marketplace/{accountId}/ozon-orders/json', function (\Illuminate\Http\Request $request, $accountId) {
        $query = \App\Models\OzonOrder::where('marketplace_account_id', $accountId);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('posting_number', 'like', "%{$search}%")
                    ->orWhere('order_id', 'like', "%{$search}%");
            });
        }

        $orders = $query->orderBy('created_at_ozon', 'desc')
            ->paginate($request->input('per_page', 20));

        return response()->json([
            'orders' => $orders->items(),
            'pagination' => [
                'total' => $orders->total(),
                'per_page' => $orders->perPage(),
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
            ],
        ]);
    })->name('marketplace.ozon-orders.json');

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
        Route::get('/', function () {
            return view('store.admin.dashboard');
        })->name('dashboard');

        Route::get('/{storeId}/theme', function ($storeId) {
            return view('store.admin.theme', ['storeId' => $storeId]);
        })->name('theme');

        Route::get('/{storeId}/catalog', function ($storeId) {
            return view('store.admin.catalog', ['storeId' => $storeId]);
        })->name('catalog');

        Route::get('/{storeId}/delivery', function ($storeId) {
            return view('store.admin.delivery', ['storeId' => $storeId]);
        })->name('delivery');

        Route::get('/{storeId}/payment', function ($storeId) {
            return view('store.admin.payment', ['storeId' => $storeId]);
        })->name('payment');

        Route::get('/{storeId}/orders', function ($storeId) {
            return view('store.admin.orders', ['storeId' => $storeId]);
        })->name('orders');

        Route::get('/{storeId}/orders/{orderId}', function ($storeId, $orderId) {
            return view('store.admin.order-show', ['storeId' => $storeId, 'orderId' => $orderId]);
        })->name('orders.show');

        Route::get('/{storeId}/pages', function ($storeId) {
            return view('store.admin.pages', ['storeId' => $storeId]);
        })->name('pages');

        Route::get('/{storeId}/analytics', function ($storeId) {
            return view('store.admin.analytics', ['storeId' => $storeId]);
        })->name('analytics');

        Route::get('/{storeId}/banners', function ($storeId) {
            return view('store.admin.banners', ['storeId' => $storeId]);
        })->name('banners');
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
    Route::get('/api/search', [\App\Http\Controllers\Storefront\CatalogController::class, 'search']);
    Route::get('/api/cart', [\App\Http\Controllers\Storefront\CartController::class, 'show']);
    Route::post('/api/cart/add', [\App\Http\Controllers\Storefront\CartController::class, 'add']);
    Route::put('/api/cart/update', [\App\Http\Controllers\Storefront\CartController::class, 'update']);
    Route::delete('/api/cart/remove', [\App\Http\Controllers\Storefront\CartController::class, 'remove']);
    Route::delete('/api/cart/clear', [\App\Http\Controllers\Storefront\CartController::class, 'clear']);
    Route::post('/api/cart/promocode', [\App\Http\Controllers\Storefront\CartController::class, 'applyPromocode']);
    Route::delete('/api/cart/promocode', [\App\Http\Controllers\Storefront\CartController::class, 'removePromocode']);
    Route::post('/api/checkout', [\App\Http\Controllers\Storefront\CheckoutController::class, 'store']);
    Route::post('/api/quick-order', [\App\Http\Controllers\Storefront\CheckoutController::class, 'quickOrder']);
    Route::post('/api/payment/{orderId}/initiate', [\App\Http\Controllers\Storefront\PaymentController::class, 'initiate']);

    // Wishlist page
    Route::get('/wishlist', [\App\Http\Controllers\Storefront\StorefrontController::class, 'wishlist'])->name('storefront.wishlist');
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
