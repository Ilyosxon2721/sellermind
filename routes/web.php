<?php

use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Web\Products\ProductWebController;
use App\Http\Controllers\Web\Warehouse\WarehouseController;
use App\Models\AP\Supplier;
use Illuminate\Support\Facades\Route;

// Public pages
Route::get('/', function () {
    $plans = \App\Models\Plan::where('is_active', true)
        ->orderBy('sort_order')
        ->get();
    return view('welcome', compact('plans'));
});

Route::get('/login', function () {
    return view('pages.login');
})->name('login');

Route::get('/register', function () {
    return view('pages.register');
})->name('register');

// App pages - Dashboard is the main page after login
Route::get('/home', function () {
    return view('pages.dashboard');
})->name('home');

Route::get('/dashboard', function () {
    return view('pages.dashboard');
})->name('dashboard');

Route::get('/chat', function () {
    return view('pages.chat');
})->name('chat');

Route::get('/settings', function () {
    return view('pages.settings');
})->name('settings');

Route::get('/promotions', function () {
    return view('pages.promotions');
})->name('promotions');

Route::get('/analytics', function () {
    return view('pages.analytics');
})->name('analytics');

Route::prefix('products')->name('web.products.')->group(function () {
    Route::get('/', [ProductWebController::class, 'index'])->name('index');
    Route::get('/create', [ProductWebController::class, 'create'])->name('create');
    Route::post('/', [ProductWebController::class, 'store'])->name('store');
    Route::get('/{product}/edit', [ProductWebController::class, 'edit'])->name('edit');
    Route::put('/{product}', [ProductWebController::class, 'update'])->name('update');
    Route::delete('/{product}', [ProductWebController::class, 'destroy'])->name('destroy');
    Route::post('/{product}/publish', [ProductWebController::class, 'publish'])->name('publish');
});

Route::prefix('warehouse')->name('warehouse.')->group(function () {
    Route::get('/', [WarehouseController::class, 'dashboard'])->name('dashboard');
    Route::get('/balance', [WarehouseController::class, 'balance'])->name('balance');
    Route::get('/in', [WarehouseController::class, 'receipts'])->name('in');
    Route::get('/in/create', [WarehouseController::class, 'createReceipt'])->name('in.create');
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
});

Route::get('/tasks', function () {
    return view('pages.tasks');
})->name('tasks');

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

Route::get('/companies', function () {
    return view('companies.index');
})->name('companies.index');

Route::middleware('auth.any')->get('/company/profile', function () {
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
        ->when($companyId, fn($q) => $q->where('company_id', $companyId))
        ->orderBy('name')
        ->get(['id', 'name']);

    return view('ap.index', [
        'suppliers' => $suppliers,
    ]);
})->name('ap.index');

Route::get('/pricing', function () {
    return view('pricing.index');
})->name('pricing.index');

Route::get('/pricing/autopricing', function () {
    return view('pricing.autopricing');
})->name('pricing.autopricing');

// Marketplace Module
Route::get('/marketplace', function () {
    return view('pages.marketplace.index');
})->name('marketplace.index');

// Marketplace sync logs (admin) - должен быть ПЕРЕД {accountId}
use App\Http\Controllers\MarketplaceSyncLogController;
use App\Models\MarketplaceAccount;
use Illuminate\Support\Facades\Auth;
Route::middleware('auth.any')->get('/marketplace/sync-logs', [MarketplaceSyncLogController::class, 'index'])
    ->name('marketplace.sync-logs');

Route::get('/marketplace/{accountId}', function ($accountId) {
    return view('pages.marketplace.show', ['accountId' => $accountId]);
})->name('marketplace.show');

Route::get('/marketplace/{accountId}/products', function ($accountId) {
    $account = MarketplaceAccount::findOrFail($accountId);
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
        $query->where(function($q) use ($search) {
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
    $productsWithLinks = collect($products->items())->map(function($product) use ($links) {
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

Route::get('/marketplace/{accountId}/orders', function ($accountId) {
    $account = \App\Models\MarketplaceAccount::findOrFail($accountId);
    $uzumShops = $account->marketplace === 'uzum'
        ? \App\Models\MarketplaceShop::where('marketplace_account_id', $accountId)
            ->orderBy('name')
            ->get(['external_id', 'name'])
        : collect();

    return view('pages.marketplace.orders', [
        'accountId' => $accountId,
        'accountMarketplace' => $account->marketplace,
        'accountName' => $account->name,
        'uzumShops' => $uzumShops,
    ]);
})->name('marketplace.orders');

// Явные URL по маркетплейсу, чтобы разделить страницы заказов
Route::get('/marketplace/{accountId}/{marketplace}/orders', function ($accountId, $marketplace) {
    $account = \App\Models\MarketplaceAccount::findOrFail($accountId);
    if (strtolower($marketplace) !== strtolower($account->marketplace)) {
        abort(404);
    }
    $uzumShops = $account->marketplace === 'uzum'
        ? \App\Models\MarketplaceShop::where('marketplace_account_id', $accountId)
            ->orderBy('name')
            ->get(['external_id', 'name'])
        : collect();

    return view('pages.marketplace.orders', [
        'accountId' => $accountId,
        'accountMarketplace' => $account->marketplace,
        'accountName' => $account->name,
        'uzumShops' => $uzumShops,
    ]);
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

// Wildberries Orders
Route::get('/marketplace/{accountId}/wb-orders', function ($accountId) {
    $account = \App\Models\MarketplaceAccount::findOrFail($accountId);

    return view('pages.marketplace.orders', [
        'accountId' => $accountId,
        'accountMarketplace' => $account->marketplace,
        'accountName' => $account->name,
        'uzumShops' => collect(),
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
        $query->where(function($q) use ($search) {
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
        ]
    ]);
})->name('marketplace.ym-orders.json');

// Uzum Orders
Route::get('/marketplace/{accountId}/uzum-orders', function ($accountId) {
    $account = \App\Models\MarketplaceAccount::findOrFail($accountId);
    $uzumShops = $account->marketplace === 'uzum'
        ? \App\Models\MarketplaceShop::where('marketplace_account_id', $accountId)
            ->orderBy('name')
            ->get(['external_id', 'name'])
        : collect();

    return view('pages.marketplace.orders', [
        'accountId' => $accountId,
        'accountMarketplace' => $account->marketplace,
        'accountName' => $account->name,
        'uzumShops' => $uzumShops,
    ]);
})->name('marketplace.uzum-orders');

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
        $query->where(function($q) use ($search) {
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
        ]
    ]);
})->name('marketplace.ozon-orders.json');

// VPC Sessions - DISABLED: Module not complete, enable via VPC_ENABLED=true in .env
use App\Http\Controllers\VpcSessionController;
use App\Http\Controllers\VpcControlApiController;

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

// Telegram webhook
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);
