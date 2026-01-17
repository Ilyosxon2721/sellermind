<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\DialogController;
use App\Http\Controllers\Api\GenerationTaskController;
use App\Http\Controllers\Api\MarketplaceAccountController;
use App\Http\Controllers\Api\MarketplaceProductController;
use App\Http\Controllers\Api\MarketplaceSyncController;
use App\Http\Controllers\Api\MarketplaceOrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProductBulkController;
use App\Http\Controllers\Api\ProductDescriptionController;
use App\Http\Controllers\Api\TelegramController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\SalesAnalyticsController;
use App\Http\Controllers\Api\ReviewResponseController;
use App\Http\Controllers\Api\ProductImageController;
use App\Http\Controllers\Api\Admin\DialogAdminController;
use App\Http\Controllers\Api\AgentTaskController;
use App\Http\Controllers\Api\MarketplaceDashboardController;
use App\Http\Controllers\Api\MarketplaceInsightsController;
use App\Http\Controllers\Api\WildberriesSettingsController;
use App\Http\Controllers\Api\WildberriesPassController;
use App\Http\Controllers\Api\WildberriesStickerController;
use App\Http\Controllers\Api\WildberriesFinanceController;
use App\Http\Controllers\Api\WildberriesAnalyticsController;
use App\Http\Controllers\Api\WildberriesSupplyController;
use App\Http\Controllers\Api\WildberriesOrderMetaController;
use App\Http\Controllers\Api\UzumSettingsController;
use App\Http\Controllers\Api\WildberriesProductController;
use App\Http\Controllers\Api\HealthCheckController;
use App\Http\Controllers\Api\PlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\MarketplaceWebhookController;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| SellerMind AI API v1
|
*/

// Public routes - Health Check
Route::get('health', [HealthCheckController::class, 'index']);
Route::get('health/detailed', [HealthCheckController::class, 'detailed']);

Route::middleware('web')->prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
});

// Marketplace webhooks (public, no auth required)
Route::prefix('webhooks/marketplaces')->group(function () {
    Route::post('{marketplace}', [MarketplaceWebhookController::class, 'handle']);
    Route::post('{marketplace}/{accountId}', [MarketplaceWebhookController::class, 'handleForAccount']);
});

// Sales API (uses web session auth)
Route::middleware(['web', 'auth.any'])->group(function () {
    // Sales display/viewing (marketplace + manual combined)
    Route::get('sales', [\App\Http\Controllers\Api\SalesController::class, 'index']);
    Route::get('sales/{id}', [\App\Http\Controllers\Api\SalesController::class, 'show']);
    Route::post('sales/manual', [\App\Http\Controllers\Api\SalesController::class, 'storeManual']);

    // Sales Management (CRUD for Sales model)
    Route::prefix('sales-management')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\SalesManagementController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\SalesManagementController::class, 'store']);
        Route::get('/statistics', [\App\Http\Controllers\Api\SalesManagementController::class, 'statistics']);
        Route::get('/counterparties', [\App\Http\Controllers\Api\SalesManagementController::class, 'getCounterparties']);
        Route::get('/products', [\App\Http\Controllers\Api\SalesManagementController::class, 'getProducts']);
        Route::get('/warehouses', [\App\Http\Controllers\Api\SalesManagementController::class, 'getWarehouses']);
        Route::get('/{id}', [\App\Http\Controllers\Api\SalesManagementController::class, 'show']);
        Route::put('/{id}', [\App\Http\Controllers\Api\SalesManagementController::class, 'update']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\SalesManagementController::class, 'destroy']);
        Route::post('/{id}/confirm', [\App\Http\Controllers\Api\SalesManagementController::class, 'confirm']);
        Route::post('/{id}/complete', [\App\Http\Controllers\Api\SalesManagementController::class, 'complete']);
        Route::post('/{id}/cancel', [\App\Http\Controllers\Api\SalesManagementController::class, 'cancel']);
        Route::post('/{id}/ship', [\App\Http\Controllers\Api\SalesManagementController::class, 'ship']);
        Route::get('/{id}/reservations', [\App\Http\Controllers\Api\SalesManagementController::class, 'getReservations']);
        Route::post('/{id}/items', [\App\Http\Controllers\Api\SalesManagementController::class, 'addItem']);
        Route::put('/{saleId}/items/{itemId}', [\App\Http\Controllers\Api\SalesManagementController::class, 'updateItem']);
        Route::delete('/{saleId}/items/{itemId}', [\App\Http\Controllers\Api\SalesManagementController::class, 'deleteItem']);
    });

    // Dashboard API
    Route::get('dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);
    Route::get('dashboard/full', [\App\Http\Controllers\Api\DashboardController::class, 'full']);
    Route::get('dashboard/alerts', [\App\Http\Controllers\Api\DashboardController::class, 'alerts']);
    Route::get('dashboard/ai-status', [\App\Http\Controllers\Api\DashboardController::class, 'aiStatus']);
    Route::get('dashboard/subscription', [\App\Http\Controllers\Api\DashboardController::class, 'subscription']);
    Route::get('dashboard/team', [\App\Http\Controllers\Api\DashboardController::class, 'team']);

    // Currency Settings API
    Route::prefix('currency')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\CurrencySettingsController::class, 'index']);
        Route::put('/display', [\App\Http\Controllers\Api\CurrencySettingsController::class, 'updateDisplayCurrency']);
        Route::put('/rate', [\App\Http\Controllers\Api\CurrencySettingsController::class, 'updateRate']);
        Route::post('/convert', [\App\Http\Controllers\Api\CurrencySettingsController::class, 'convert']);
    });
    
    // Counterparties API
    Route::get('counterparties', [\App\Http\Controllers\Api\CounterpartyController::class, 'index']);
    Route::post('counterparties', [\App\Http\Controllers\Api\CounterpartyController::class, 'store']);
    Route::get('counterparties/{id}', [\App\Http\Controllers\Api\CounterpartyController::class, 'show']);
    Route::put('counterparties/{id}', [\App\Http\Controllers\Api\CounterpartyController::class, 'update']);
    Route::delete('counterparties/{id}', [\App\Http\Controllers\Api\CounterpartyController::class, 'destroy']);
    
    // Counterparty contracts
    Route::get('counterparties/{id}/contracts', [\App\Http\Controllers\Api\CounterpartyController::class, 'contracts']);
    Route::post('counterparties/{id}/contracts', [\App\Http\Controllers\Api\CounterpartyController::class, 'storeContract']);
    Route::put('counterparties/{id}/contracts/{contractId}', [\App\Http\Controllers\Api\CounterpartyController::class, 'updateContract']);
    Route::delete('counterparties/{id}/contracts/{contractId}', [\App\Http\Controllers\Api\CounterpartyController::class, 'destroyContract']);
    
    // Inventory API
    Route::get('inventories', [\App\Http\Controllers\Api\InventoryController::class, 'index']);
    Route::post('inventories', [\App\Http\Controllers\Api\InventoryController::class, 'store']);
    Route::get('inventories/{id}', [\App\Http\Controllers\Api\InventoryController::class, 'show']);
    Route::put('inventories/{id}', [\App\Http\Controllers\Api\InventoryController::class, 'update']);
    Route::delete('inventories/{id}', [\App\Http\Controllers\Api\InventoryController::class, 'destroy']);
    Route::put('inventories/{id}/items/{itemId}', [\App\Http\Controllers\Api\InventoryController::class, 'updateItem']);
    Route::put('inventories/{id}/items', [\App\Http\Controllers\Api\InventoryController::class, 'updateItems']);
    Route::post('inventories/{id}/complete', [\App\Http\Controllers\Api\InventoryController::class, 'complete']);
    Route::post('inventories/{id}/apply', [\App\Http\Controllers\Api\InventoryController::class, 'apply']);
    
    // Warehouses for inventory
    Route::get('warehouses', [\App\Http\Controllers\Api\InventoryController::class, 'warehouses']);
});
// Broadcasting auth routes (must be before protected routes to avoid middleware issues)
Broadcast::routes(['middleware' => ['auth:sanctum']]);

// Protected routes - using auth.any to support both sanctum tokens and web sessions
Route::middleware('auth.any')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Current user
    Route::get('me', [AuthController::class, 'me']);
    Route::put('me', [AuthController::class, 'updateProfile']);
    Route::put('me/password', [AuthController::class, 'changePassword']);

    // Telegram Notifications
    Route::prefix('telegram')->group(function () {
        Route::get('status', [TelegramController::class, 'status']);
        Route::post('generate-link-code', [TelegramController::class, 'generateLinkCode']);
        Route::post('disconnect', [TelegramController::class, 'disconnect']);
        Route::get('notification-settings', [TelegramController::class, 'getSettings']);
        Route::put('notification-settings', [TelegramController::class, 'updateSettings']);
    });

    // Polling API (для real-time обновлений без WebSocket)
    Route::middleware('throttle:120,1')->prefix('polling')->group(function () {
        Route::get('marketplace/orders/{accountId}', [\App\Http\Controllers\Api\PollingController::class, 'checkMarketplaceOrders']);
        Route::get('marketplace/sync-status/{accountId}', [\App\Http\Controllers\Api\PollingController::class, 'checkSyncStatus']);
        Route::get('notifications', [\App\Http\Controllers\Api\PollingController::class, 'checkNotifications']);
        Route::get('dashboard/stats', [\App\Http\Controllers\Api\PollingController::class, 'getDashboardStats']);
        Route::get('supplies/{accountId}', [\App\Http\Controllers\Api\PollingController::class, 'checkSupplies']);
    });

    // Companies
    Route::apiResource('companies', CompanyController::class)->names([
        'index' => 'api.companies.index',
        'store' => 'api.companies.store',
        'show' => 'api.companies.show',
        'update' => 'api.companies.update',
        'destroy' => 'api.companies.destroy',
    ]);
    Route::get('companies/{company}/members', [CompanyController::class, 'getMembers'])->name('api.companies.members');
    Route::post('companies/{company}/members', [CompanyController::class, 'addMember'])
        ->middleware('plan.limits:users,1')
        ->name('api.companies.addMember');
    Route::delete('companies/{company}/members/{userId}', [CompanyController::class, 'removeMember'])->name('api.companies.removeMember');
    Route::post('companies/{company}/transfer-ownership', [CompanyController::class, 'transferOwnership'])->name('api.companies.transferOwnership');

    // Plans (Public - can view without auth, but included in auth group for consistency)
    Route::get('plans', [PlanController::class, 'index'])->name('api.plans.index');
    Route::get('plans/{slugOrId}', [PlanController::class, 'show'])->name('api.plans.show');

    // Subscriptions
    Route::prefix('subscription')->group(function () {
        Route::get('status', [SubscriptionController::class, 'status'])->name('api.subscription.status');
        Route::post('subscribe', [SubscriptionController::class, 'subscribe'])->name('api.subscription.subscribe');
        Route::post('cancel', [SubscriptionController::class, 'cancel'])->name('api.subscription.cancel');
        Route::post('renew', [SubscriptionController::class, 'renew'])->name('api.subscription.renew');
        Route::get('history', [SubscriptionController::class, 'history'])->name('api.subscription.history');
    });

    // Warehouse Management (top-level endpoints)
    Route::get('warehouse/list', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'index']);
    Route::get('warehouses', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'index']);
    Route::post('warehouse', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'store'])
        ->middleware('plan.limits:warehouses,1');
    Route::post('warehouses', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'store'])
        ->middleware('plan.limits:warehouses,1');
    Route::get('warehouse/{id}', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'show']);
    Route::put('warehouse/{id}', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'update']);
    Route::put('warehouses/{id}', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'update']);
    Route::post('warehouse/{id}/default', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'makeDefault']);
    Route::post('warehouses/{id}/default', [\App\Http\Controllers\Api\Warehouse\WarehouseManageController::class, 'makeDefault']);

    // Products
    Route::apiResource('products', ProductController::class)->only(['index', 'show', 'update', 'destroy']);
    Route::post('products', [ProductController::class, 'store'])->middleware('plan.limits:products,1');
    Route::post('products/{product}/publish', [ProductController::class, 'publish']);
    Route::post('products/{product}/publish/{channel}', [ProductController::class, 'publishChannel']);

    // Product Bulk Operations
    Route::prefix('products/bulk')->group(function () {
        Route::post('export', [ProductBulkController::class, 'export']);
        Route::post('import/preview', [ProductBulkController::class, 'previewImport']);
        Route::post('import/apply', [ProductBulkController::class, 'applyImport']);
        Route::post('update', [ProductBulkController::class, 'bulkUpdate']);
    });

    // Product Descriptions
    Route::get('products/{product}/descriptions', [ProductDescriptionController::class, 'index']);
    Route::post('products/{product}/descriptions', [ProductDescriptionController::class, 'store']);
    Route::get('products/{product}/descriptions/versions', [ProductDescriptionController::class, 'versions']);
    Route::get('products/{product}/descriptions/{description}', [ProductDescriptionController::class, 'show']);
    Route::delete('products/{product}/descriptions/{description}', [ProductDescriptionController::class, 'destroy']);

    // Product Images
    Route::get('products/{product}/images', [ProductImageController::class, 'index']);
    Route::post('products/{product}/images/upload', [ProductImageController::class, 'upload']);
    Route::post('products/{product}/images/generate', [ProductImageController::class, 'generate']);
    Route::put('products/{product}/images/{image}/primary', [ProductImageController::class, 'setPrimary']);
    Route::delete('products/{product}/images/{image}', [ProductImageController::class, 'destroy']);
    
    // Generic image upload (for new products without ID)
    Route::post('products/upload-image', [ProductImageController::class, 'uploadTemp']);

    // Promotions (Smart Promotions)
    Route::prefix('promotions')->group(function () {
        Route::get('/', [PromotionController::class, 'index']);
        Route::post('/', [PromotionController::class, 'store']);
        Route::get('detect-slow-moving', [PromotionController::class, 'detectSlowMoving']);
        Route::post('create-automatic', [PromotionController::class, 'createAutomatic']);
        Route::get('{promotion}', [PromotionController::class, 'show']);
        Route::put('{promotion}', [PromotionController::class, 'update']);
        Route::delete('{promotion}', [PromotionController::class, 'destroy']);
        Route::post('{promotion}/apply', [PromotionController::class, 'apply']);
        Route::post('{promotion}/remove', [PromotionController::class, 'remove']);
        Route::get('{promotion}/stats', [PromotionController::class, 'stats']);
    });

    // Sales Analytics
    Route::prefix('analytics')->group(function () {
        Route::get('dashboard', [SalesAnalyticsController::class, 'dashboard']);
        Route::get('overview', [SalesAnalyticsController::class, 'overview']);
        Route::get('sales-by-day', [SalesAnalyticsController::class, 'salesByDay']);
        Route::get('top-products', [SalesAnalyticsController::class, 'topProducts']);
        Route::get('flop-products', [SalesAnalyticsController::class, 'flopProducts']);
        Route::get('sales-by-category', [SalesAnalyticsController::class, 'salesByCategory']);
        Route::get('sales-by-marketplace', [SalesAnalyticsController::class, 'salesByMarketplace']);
        Route::get('product/{productId}/performance', [SalesAnalyticsController::class, 'productPerformance']);
    });

    // Review Response Generator
    Route::prefix('reviews')->group(function () {
        Route::get('/', [ReviewResponseController::class, 'index']);
        Route::post('/', [ReviewResponseController::class, 'store']);
        Route::get('statistics', [ReviewResponseController::class, 'statistics']);
        Route::get('templates', [ReviewResponseController::class, 'templates']);
        Route::post('templates', [ReviewResponseController::class, 'storeTemplate']);
        Route::post('bulk-generate', [ReviewResponseController::class, 'bulkGenerate']);
        Route::get('{review}', [ReviewResponseController::class, 'show']);
        Route::post('{review}/generate', [ReviewResponseController::class, 'generateResponse']);
        Route::post('{review}/save-response', [ReviewResponseController::class, 'saveResponse']);
        Route::get('{review}/suggest-templates', [ReviewResponseController::class, 'suggestTemplates']);
    });

    // Dialogs
    Route::apiResource('dialogs', DialogController::class);
    Route::post('dialogs/{dialog}/hide', [ChatController::class, 'hideDialog']);

    // Chat
    Route::post('chat', [ChatController::class, 'send']);
    Route::post('chat/generate-card', [ChatController::class, 'generateCard']);
    Route::post('chat/generate-review-response', [ChatController::class, 'generateReviewResponse']);

    // Generation Tasks (Legacy)
    Route::prefix('generation')->group(function () {
        Route::get('tasks', [GenerationTaskController::class, 'index']);
        Route::post('tasks', [GenerationTaskController::class, 'store']);
        Route::get('tasks/{task}', [GenerationTaskController::class, 'show']);
        Route::post('tasks/{task}/cancel', [GenerationTaskController::class, 'cancel']);
        Route::post('tasks/{task}/retry', [GenerationTaskController::class, 'retry']);
    });

    // Agent Mode
    Route::prefix('agent')->group(function () {
        Route::get('agents', [AgentTaskController::class, 'agents']);
        Route::get('tasks', [AgentTaskController::class, 'index']);
        Route::post('tasks', [AgentTaskController::class, 'store']);
        Route::get('tasks/{task}', [AgentTaskController::class, 'show']);
        Route::post('tasks/{task}/run', [AgentTaskController::class, 'run']);
        Route::delete('tasks/{task}', [AgentTaskController::class, 'destroy']);
        Route::get('runs/{run}', [AgentTaskController::class, 'showRun']);
        Route::post('runs/{run}/message', [AgentTaskController::class, 'sendMessage']);
    });

    // Marketplace Accounts
    Route::prefix('marketplace')->group(function () {
        // Dashboard
        Route::get('dashboard', [MarketplaceDashboardController::class, 'index']);
        Route::get('dashboard/stats', [MarketplaceDashboardController::class, 'stats']);
        Route::get('dashboard/charts-data', [MarketplaceDashboardController::class, 'chartsData']);

        // Accounts
        Route::get('accounts/requirements', [MarketplaceAccountController::class, 'requirements'])
            ->withoutMiddleware('auth.any'); // Публичный доступ для просмотра требований
        Route::get('accounts', [MarketplaceAccountController::class, 'index']);
        Route::post('accounts', [MarketplaceAccountController::class, 'store'])
            ->middleware('plan.limits:marketplace_accounts,1');
        Route::get('accounts/{account}', [MarketplaceAccountController::class, 'show']);
        Route::delete('accounts/{account}', [MarketplaceAccountController::class, 'destroy']);
        Route::post('accounts/{account}/test', [MarketplaceAccountController::class, 'test']);
        Route::get('accounts/{account}/logs', [MarketplaceAccountController::class, 'syncLogs']);
        Route::get('accounts/{account}/logs/stream', [MarketplaceAccountController::class, 'syncLogsStream'])
            ->withoutMiddleware('auth.any'); // SSE авторизация токеном в query/bearer внутри контроллера
        Route::post('accounts/{account}/monitoring/start', [MarketplaceAccountController::class, 'startMonitoring']);
        Route::post('accounts/{account}/monitoring/stop', [MarketplaceAccountController::class, 'stopMonitoring']);
        Route::get('accounts/{account}/sync-settings', [MarketplaceAccountController::class, 'getSyncSettings']);
        Route::put('accounts/{account}/sync-settings', [MarketplaceAccountController::class, 'updateSyncSettings']);
        Route::get('uzum/accounts/{account}/shops', [MarketplaceOrderController::class, 'uzumShops']);

        // Sync operations
        Route::post('accounts/{account}/sync/prices', [MarketplaceSyncController::class, 'syncPrices']);
        Route::post('accounts/{account}/sync/stocks', [MarketplaceSyncController::class, 'syncStocks']);
        Route::post('accounts/{account}/sync/products', [MarketplaceSyncController::class, 'syncProducts']);
        Route::post('accounts/{account}/sync/orders', [MarketplaceSyncController::class, 'syncOrders']);
        Route::post('accounts/{account}/sync/supplies', [MarketplaceSyncController::class, 'syncSupplies']);
        Route::post('accounts/{account}/sync/all', [MarketplaceSyncController::class, 'syncAll']);

        // Marketplace Products
        Route::get('products', [MarketplaceProductController::class, 'index']);
        Route::post('products', [MarketplaceProductController::class, 'store']);
        Route::get('products/unlinked', [MarketplaceProductController::class, 'unlinkedProducts']);
        Route::get('products/available', [MarketplaceProductController::class, 'availableProducts']);
        Route::get('products/search', [MarketplaceProductController::class, 'searchProducts']);
        Route::post('products/bulk-link', [MarketplaceProductController::class, 'bulkLink']);
        Route::put('products/{marketplaceProduct}', [MarketplaceProductController::class, 'update']);
        Route::delete('products/{marketplaceProduct}', [MarketplaceProductController::class, 'destroy']);
        Route::get('stock/logs', [\App\Http\Controllers\Api\MarketplaceStockLogController::class, 'index']);
        Route::get('warehouses', [\App\Http\Controllers\Api\MarketplaceWarehouseController::class, 'index']);
        Route::get('warehouses/local', [\App\Http\Controllers\Api\WarehouseMappingController::class, 'localWarehouses']);
        Route::put('warehouses/{warehouse}', [\App\Http\Controllers\Api\MarketplaceWarehouseUpdateController::class, 'update']);

        // Variant Links - shared across all marketplaces
        Route::prefix('variant-links')->group(function () {
            Route::get('variants/search', [\App\Http\Controllers\Api\VariantLinkController::class, 'searchVariants']);
            Route::prefix('accounts/{account}')->group(function () {
                Route::post('products/{productId}/link', [\App\Http\Controllers\Api\VariantLinkController::class, 'linkVariant']);
                Route::delete('products/{productId}/unlink', [\App\Http\Controllers\Api\VariantLinkController::class, 'unlinkVariant']);
                Route::get('products/{productId}/links', [\App\Http\Controllers\Api\VariantLinkController::class, 'getProductLinks']);
                Route::post('products/{productId}/sync-stock', [\App\Http\Controllers\Api\VariantLinkController::class, 'syncProductStock']);
                Route::post('sync-all-stocks', [\App\Http\Controllers\Api\VariantLinkController::class, 'syncAllStocks']);
            });
        });

        // Marketplace Orders
        Route::get('orders', [MarketplaceOrderController::class, 'index']);
        Route::get('orders/stats', [MarketplaceOrderController::class, 'stats']);
        Route::get('orders/new', [MarketplaceOrderController::class, 'getNew']);
        Route::post('orders/stickers', [MarketplaceOrderController::class, 'getStickers']);
        Route::get('orders/{order}', [MarketplaceOrderController::class, 'show']);
        Route::post('orders/{order}/confirm', [MarketplaceOrderController::class, 'confirm']);
        Route::post('orders/{order}/cancel', [MarketplaceOrderController::class, 'cancel']);

        // Order Stock Returns (для ручной обработки возвратов)
        Route::prefix('returns')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\OrderStockReturnController::class, 'index']);
            Route::get('/stats', [\App\Http\Controllers\Api\OrderStockReturnController::class, 'stats']);
            Route::get('/{id}', [\App\Http\Controllers\Api\OrderStockReturnController::class, 'show']);
            Route::post('/{id}/return-to-stock', [\App\Http\Controllers\Api\OrderStockReturnController::class, 'returnToStock']);
            Route::post('/{id}/write-off', [\App\Http\Controllers\Api\OrderStockReturnController::class, 'writeOff']);
            Route::post('/{id}/reject', [\App\Http\Controllers\Api\OrderStockReturnController::class, 'reject']);
        });

        // Internal Supplies Management
        Route::get('supplies', [\App\Http\Controllers\Api\SupplyController::class, 'index']);
        Route::get('supplies/open', [\App\Http\Controllers\Api\SupplyController::class, 'open']);
        Route::post('supplies', [\App\Http\Controllers\Api\SupplyController::class, 'store']);
        Route::get('supplies/{supply}', [\App\Http\Controllers\Api\SupplyController::class, 'show']);
        Route::put('supplies/{supply}', [\App\Http\Controllers\Api\SupplyController::class, 'update']);
        Route::delete('supplies/{supply}', [\App\Http\Controllers\Api\SupplyController::class, 'destroy']);
        Route::post('supplies/{supply}/orders', [\App\Http\Controllers\Api\SupplyController::class, 'addOrder']);
        Route::delete('supplies/{supply}/orders', [\App\Http\Controllers\Api\SupplyController::class, 'removeOrder']);

        // Tares (Boxes) Management
        Route::get('supplies/{supply}/tares', [\App\Http\Controllers\Api\TareController::class, 'index']);
        Route::post('supplies/{supply}/tares', [\App\Http\Controllers\Api\TareController::class, 'store']);
        Route::get('tares/{tare}', [\App\Http\Controllers\Api\TareController::class, 'show']);
        Route::put('tares/{tare}', [\App\Http\Controllers\Api\TareController::class, 'update']);
        Route::delete('tares/{tare}', [\App\Http\Controllers\Api\TareController::class, 'destroy']);
        Route::post('tares/{tare}/orders', [\App\Http\Controllers\Api\TareController::class, 'addOrder']);
        Route::delete('tares/{tare}/orders', [\App\Http\Controllers\Api\TareController::class, 'removeOrder']);
        Route::get('tares/{tare}/barcode', [\App\Http\Controllers\Api\TareController::class, 'getBarcode']);
        Route::post('supplies/{supply}/close', [\App\Http\Controllers\Api\SupplyController::class, 'close']);
        Route::post('supplies/{supply}/sync-wb', [\App\Http\Controllers\Api\SupplyController::class, 'syncWithWb']);
        Route::get('supplies/{supply}/barcode', [\App\Http\Controllers\Api\SupplyController::class, 'barcode']);
        Route::get('supplies/{supply}/tares', [\App\Http\Controllers\Api\SupplyController::class, 'tares']);
        Route::post('supplies/{supply}/deliver', [\App\Http\Controllers\Api\SupplyController::class, 'deliver']);

        // Insights (AI integration)
        Route::prefix('insights')->group(function () {
            Route::get('summary', [MarketplaceInsightsController::class, 'summary']);
            Route::get('problems', [MarketplaceInsightsController::class, 'problems']);
            Route::get('recommendations', [MarketplaceInsightsController::class, 'recommendations']);
            Route::get('for-agent', [MarketplaceInsightsController::class, 'forAgent']);
            Route::get('text-summary', [MarketplaceInsightsController::class, 'textSummary']);
        });

        // Wildberries Settings (WB-specific token management)
        Route::prefix('wb')->group(function () {
            Route::get('accounts/{account}/settings', [WildberriesSettingsController::class, 'show']);
            Route::put('accounts/{account}/settings', [WildberriesSettingsController::class, 'update']);
            Route::post('accounts/{account}/test', [WildberriesSettingsController::class, 'test']);
            Route::post('accounts/{account}/test/{category}', [WildberriesSettingsController::class, 'testCategory']);

            // Warehouse Passes
            Route::get('accounts/{account}/passes/offices', [WildberriesPassController::class, 'offices']);
            Route::get('accounts/{account}/passes', [WildberriesPassController::class, 'index']);
            Route::post('accounts/{account}/passes', [WildberriesPassController::class, 'store']);
            Route::put('accounts/{account}/passes/{passId}', [WildberriesPassController::class, 'update']);
            Route::delete('accounts/{account}/passes/{passId}', [WildberriesPassController::class, 'destroy']);
            Route::get('accounts/{account}/passes/expiring', [WildberriesPassController::class, 'expiring']);
            Route::post('accounts/{account}/passes/cleanup', [WildberriesPassController::class, 'cleanup']);

            // Order Stickers
            Route::post('accounts/{account}/stickers/generate', [WildberriesStickerController::class, 'generate']);
            Route::post('accounts/{account}/stickers/cross-border', [WildberriesStickerController::class, 'crossBorder']);
            Route::get('accounts/{account}/stickers/download', [WildberriesStickerController::class, 'download'])
                ->name('api.wildberries.stickers.download');
            Route::post('stickers/cleanup', [WildberriesStickerController::class, 'cleanup']);

            // Finance & Reports
            Route::get('accounts/{account}/finance/balance', [WildberriesFinanceController::class, 'balance']);
            Route::get('accounts/{account}/finance/report', [WildberriesFinanceController::class, 'report']);
            Route::get('accounts/{account}/finance/documents/categories', [WildberriesFinanceController::class, 'documentCategories']);
            Route::get('accounts/{account}/finance/documents', [WildberriesFinanceController::class, 'documents']);
            Route::get('accounts/{account}/finance/documents/{documentId}', [WildberriesFinanceController::class, 'downloadDocument']);
            Route::post('accounts/{account}/finance/documents/download-all', [WildberriesFinanceController::class, 'downloadAll']);

            // Analytics
            Route::get('accounts/{account}/analytics/sales-funnel', [WildberriesAnalyticsController::class, 'salesFunnel']);
            Route::get('accounts/{account}/analytics/sales-funnel-history', [WildberriesAnalyticsController::class, 'salesFunnelHistory']);
            Route::get('accounts/{account}/analytics/search-report', [WildberriesAnalyticsController::class, 'searchReport']);
            Route::get('accounts/{account}/analytics/products/{nmId}/search-texts', [WildberriesAnalyticsController::class, 'productSearchTexts']);
            Route::get('accounts/{account}/analytics/stocks-report', [WildberriesAnalyticsController::class, 'stocksReport']);
            Route::get('accounts/{account}/analytics/antifraud', [WildberriesAnalyticsController::class, 'antifraud']);
            Route::get('accounts/{account}/analytics/blocked-products', [WildberriesAnalyticsController::class, 'blockedProducts']);
            Route::get('accounts/{account}/analytics/dashboard', [WildberriesAnalyticsController::class, 'dashboard']);

            // Supplies Management
            Route::get('accounts/{account}/supplies', [WildberriesSupplyController::class, 'index']);
            Route::post('accounts/{account}/supplies', [WildberriesSupplyController::class, 'store']);
            Route::get('accounts/{account}/supplies/{supplyId}', [WildberriesSupplyController::class, 'show']);
            Route::get('accounts/{account}/supplies/{supplyId}/orders', [WildberriesSupplyController::class, 'orders']);
            Route::post('accounts/{account}/supplies/{supplyId}/orders', [WildberriesSupplyController::class, 'addOrders']);
            Route::delete('accounts/{account}/supplies/{supplyId}/orders/{orderId}', [WildberriesSupplyController::class, 'removeOrder']);
            Route::get('accounts/{account}/supplies/{supplyId}/barcode', [WildberriesSupplyController::class, 'barcode']);
            Route::post('accounts/{account}/supplies/{supplyId}/cancel', [WildberriesSupplyController::class, 'cancel']);
            Route::post('accounts/{account}/supplies/{supplyId}/deliver', [WildberriesSupplyController::class, 'deliver']);
            Route::get('accounts/{account}/supplies/reshipment-orders', [WildberriesSupplyController::class, 'reshipmentOrders']);

            // Order Metadata (marking codes)
            Route::post('accounts/{account}/orders/{orderId}/meta/sgtin', [WildberriesOrderMetaController::class, 'attachSGTIN']);
            Route::post('accounts/{account}/orders/{orderId}/meta/uin', [WildberriesOrderMetaController::class, 'attachUIN']);
            Route::post('accounts/{account}/orders/{orderId}/meta/imei', [WildberriesOrderMetaController::class, 'attachIMEI']);
            Route::post('accounts/{account}/orders/{orderId}/meta/gtin', [WildberriesOrderMetaController::class, 'attachGTIN']);
            Route::post('accounts/{account}/orders/{orderId}/meta/expiration', [WildberriesOrderMetaController::class, 'attachExpiration']);
            Route::post('accounts/{account}/orders/meta/batch', [WildberriesOrderMetaController::class, 'batchAttach']);
            Route::post('accounts/{account}/orders/meta', [WildberriesOrderMetaController::class, 'getMeta']);

            // WB Products (cards)
            Route::get('accounts/{account}/products/suggestions', [WildberriesProductController::class, 'suggestions']);
            Route::get('accounts/{account}/products/search', [WildberriesProductController::class, 'search']);
            Route::get('accounts/{account}/products', [WildberriesProductController::class, 'index']);
            Route::post('accounts/{account}/products', [WildberriesProductController::class, 'store']);
            Route::get('accounts/{account}/products/{product}', [WildberriesProductController::class, 'show']);
            Route::put('accounts/{account}/products/{product}', [WildberriesProductController::class, 'update']);
            Route::delete('accounts/{account}/products/{product}', [WildberriesProductController::class, 'destroy']);
            Route::post('accounts/{account}/warehouses/sync', [\App\Http\Controllers\Api\MarketplaceWarehouseController::class, 'sync']);
            
            // Warehouse Mappings
            Route::get('/{account}/warehouse-mappings', [\App\Http\Controllers\Api\WarehouseMappingController::class, 'index']);
            Route::get('/{account}/available-warehouses', [\App\Http\Controllers\Api\WarehouseMappingController::class, 'availableWarehouses']);
            Route::post('/{account}/warehouse-mappings/sync', [\App\Http\Controllers\Api\WarehouseMappingController::class, 'syncWarehouses']);
            Route::post('/{account}/warehouse-mappings', [\App\Http\Controllers\Api\WarehouseMappingController::class, 'store']);
            Route::put('/{account}/warehouse-mappings/{mapping}', [\App\Http\Controllers\Api\WarehouseMappingController::class, 'update']);
            Route::delete('/{account}/warehouse-mappings/{mapping}', [\App\Http\Controllers\Api\WarehouseMappingController::class, 'destroy']);
        });

        // Uzum Settings (single token)
        Route::prefix('uzum')->group(function () {
            Route::get('accounts/{account}/settings', [UzumSettingsController::class, 'show']);
            Route::put('accounts/{account}/settings', [UzumSettingsController::class, 'update']);
            Route::post('accounts/{account}/test', [UzumSettingsController::class, 'test']);
            Route::get('accounts/{account}/shops', [UzumSettingsController::class, 'shops']);
        });

        // Yandex Market
        Route::prefix('yandex-market')->group(function () {
            Route::post('accounts/{account}/ping', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'ping']);
            Route::get('accounts/{account}/campaigns', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'campaigns']);
            Route::put('accounts/{account}/settings', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'saveSettings']);
            Route::post('accounts/{account}/sync-catalog', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'syncCatalog']);
            Route::post('accounts/{account}/sync-orders', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'syncOrders']);
            Route::get('accounts/{account}/orders', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'orders']);
            
            // Order actions
            Route::post('accounts/{account}/orders/{orderId}/ready-to-ship', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'readyToShip']);
            Route::get('accounts/{account}/orders/{orderId}/labels', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'downloadLabels']);
            Route::put('accounts/{account}/orders/{orderId}/boxes', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'setBoxes']);
            Route::post('accounts/{account}/orders/{orderId}/cancel', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'cancelOrder']);
            Route::get('accounts/{account}/orders/{orderId}/details', [\App\Http\Controllers\Api\Marketplace\YandexMarketController::class, 'getOrderDetails']);
        });

        // Ozon Integration  
        Route::prefix('ozon')->group(function () {
            // Ozon Settings & Configuration
            Route::get('accounts/{account}/settings', [\App\Http\Controllers\Api\OzonSettingsController::class, 'show']);
            Route::put('accounts/{account}/settings', [\App\Http\Controllers\Api\OzonSettingsController::class, 'update']);
            Route::post('accounts/{account}/test', [\App\Http\Controllers\Api\OzonSettingsController::class, 'test']);
            
            // Warehouse Management
            Route::get('accounts/{account}/warehouses', [\App\Http\Controllers\Api\OzonSettingsController::class, 'getWarehouses']);
            Route::get('accounts/{account}/warehouses/mapping', [\App\Http\Controllers\Api\OzonSettingsController::class, 'getWarehouseMapping']);
            Route::put('accounts/{account}/warehouses/mapping', [\App\Http\Controllers\Api\OzonSettingsController::class, 'saveWarehouseMapping']);
            
            // Ozon Products Management
            Route::get('accounts/{account}/products', [\App\Http\Controllers\Api\OzonProductController::class, 'index']);
            Route::post('accounts/{account}/products', [\App\Http\Controllers\Api\OzonProductController::class, 'store']);
            Route::get('accounts/{account}/products/suggestions', [\App\Http\Controllers\Api\OzonProductController::class, 'suggestions']);
            Route::get('accounts/{account}/products/search', [\App\Http\Controllers\Api\OzonProductController::class, 'search']);
            Route::get('accounts/{account}/products/{product}', [\App\Http\Controllers\Api\OzonProductController::class, 'show']);
            Route::put('accounts/{account}/products/{product}', [\App\Http\Controllers\Api\OzonProductController::class, 'update']);
            Route::delete('accounts/{account}/products/{product}', [\App\Http\Controllers\Api\OzonProductController::class, 'destroy']);
            
            // Ozon Product Synchronization
            Route::post('accounts/{account}/sync-catalog', [\App\Http\Controllers\Api\OzonProductController::class, 'syncCatalog']);
            Route::post('accounts/{account}/sync-products', [\App\Http\Controllers\Api\MarketplaceSyncController::class, 'syncProducts']);
            Route::post('accounts/{account}/sync-orders', [\App\Http\Controllers\Api\MarketplaceSyncController::class, 'syncOrders']);

            // Ozon Orders Management
            Route::get('accounts/{account}/orders', [\App\Http\Controllers\Api\OzonOrderController::class, 'index']);
            Route::get('accounts/{account}/orders/{order}', [\App\Http\Controllers\Api\OzonOrderController::class, 'show']);
            Route::post('accounts/{account}/orders/{order}/cancel', [\App\Http\Controllers\Api\OzonOrderController::class, 'cancel']);
            Route::post('accounts/{account}/orders/{order}/ship', [\App\Http\Controllers\Api\OzonOrderController::class, 'ship']);
            Route::post('accounts/{account}/orders/{order}/label', [\App\Http\Controllers\Api\OzonOrderController::class, 'getLabel']);
            Route::post('accounts/{account}/orders/labels', [\App\Http\Controllers\Api\OzonOrderController::class, 'getLabels']);
            Route::get('accounts/{account}/cancel-reasons', [\App\Http\Controllers\Api\OzonOrderController::class, 'getCancelReasons']);
        });

        // Warehouse Core API
        Route::prefix('stock')->group(function () {
            Route::get('balance', [\App\Http\Controllers\Api\Warehouse\StockController::class, 'balance']);
            Route::get('ledger', [\App\Http\Controllers\Api\Warehouse\StockController::class, 'ledger']);

            Route::get('reservations', [\App\Http\Controllers\Api\Warehouse\ReservationController::class, 'index']);
            Route::post('reserve', [\App\Http\Controllers\Api\Warehouse\ReservationController::class, 'reserve']);
            Route::post('reservations/{id}/release', [\App\Http\Controllers\Api\Warehouse\ReservationController::class, 'release']);
            Route::post('reservations/{id}/consume', [\App\Http\Controllers\Api\Warehouse\ReservationController::class, 'consume']);
        });

        Route::prefix('inventory')->group(function () {
            Route::get('documents', [\App\Http\Controllers\Api\Warehouse\DocumentController::class, 'index']);
            Route::get('documents/{id}', [\App\Http\Controllers\Api\Warehouse\DocumentController::class, 'show']);
            Route::post('documents', [\App\Http\Controllers\Api\Warehouse\DocumentController::class, 'store']);
            Route::post('documents/{id}/lines', [\App\Http\Controllers\Api\Warehouse\DocumentController::class, 'addLines']);
            Route::post('documents/{id}/post', [\App\Http\Controllers\Api\Warehouse\DocumentController::class, 'post']);
            Route::post('documents/{id}/reverse', [\App\Http\Controllers\Api\Warehouse\DocumentController::class, 'reverse']);
        });

        Route::post('channels/orders/import', [\App\Http\Controllers\Api\Warehouse\ChannelImportController::class, 'import']);

        // Replenishment
        Route::prefix('replenishment')->group(function () {
            Route::get('settings', [\App\Http\Controllers\Api\Replenishment\ReplenishmentSettingsController::class, 'index']);
            Route::post('settings', [\App\Http\Controllers\Api\Replenishment\ReplenishmentSettingsController::class, 'store']);
            Route::put('settings/{id}', [\App\Http\Controllers\Api\Replenishment\ReplenishmentSettingsController::class, 'update']);

            Route::get('recommendations', [\App\Http\Controllers\Api\Replenishment\ReplenishmentRecommendationController::class, 'index']);
            Route::post('recommendations/calculate', [\App\Http\Controllers\Api\Replenishment\ReplenishmentRecommendationController::class, 'calculate']);

            Route::post('purchase-draft', [\App\Http\Controllers\Api\Replenishment\PurchaseDraftController::class, 'store']);
        });

        // Accounts Payable (AP)
        Route::prefix('ap')->group(function () {
            Route::get('invoices', [\App\Http\Controllers\Api\AP\InvoiceController::class, 'index']);
            Route::get('invoices/{id}', [\App\Http\Controllers\Api\AP\InvoiceController::class, 'show']);
            Route::post('invoices', [\App\Http\Controllers\Api\AP\InvoiceController::class, 'store']);
            Route::put('invoices/{id}', [\App\Http\Controllers\Api\AP\InvoiceController::class, 'update']);
            Route::post('invoices/{id}/lines', [\App\Http\Controllers\Api\AP\InvoiceController::class, 'lines']);
            Route::post('invoices/{id}/confirm', [\App\Http\Controllers\Api\AP\InvoiceController::class, 'confirm']);

            Route::get('payments', [\App\Http\Controllers\Api\AP\PaymentController::class, 'index']);
            Route::get('payments/{id}', [\App\Http\Controllers\Api\AP\PaymentController::class, 'show']);
            Route::post('payments', [\App\Http\Controllers\Api\AP\PaymentController::class, 'store']);
            Route::put('payments/{id}', [\App\Http\Controllers\Api\AP\PaymentController::class, 'update']);
            Route::post('payments/{id}/allocations', [\App\Http\Controllers\Api\AP\PaymentController::class, 'allocations']);
            Route::post('payments/{id}/post', [\App\Http\Controllers\Api\AP\PaymentController::class, 'post']);
            Route::post('payments/{id}/reverse', [\App\Http\Controllers\Api\AP\PaymentController::class, 'reverse']);

            Route::get('reports/aging', [\App\Http\Controllers\Api\AP\ReportController::class, 'aging']);
            Route::get('reports/overdue', [\App\Http\Controllers\Api\AP\ReportController::class, 'overdue']);
            Route::get('reports/calendar', [\App\Http\Controllers\Api\AP\ReportController::class, 'calendar']);
        });

        // Pricing (Price Engine)
        Route::prefix('pricing')->group(function () {
            Route::get('scenarios', [\App\Http\Controllers\Api\Pricing\ScenarioController::class, 'index']);
            Route::post('scenarios', [\App\Http\Controllers\Api\Pricing\ScenarioController::class, 'store']);
            Route::put('scenarios/{id}', [\App\Http\Controllers\Api\Pricing\ScenarioController::class, 'update']);
            Route::post('scenarios/{id}/set-default', [\App\Http\Controllers\Api\Pricing\ScenarioController::class, 'setDefault']);

            Route::get('overrides/channel', [\App\Http\Controllers\Api\Pricing\OverrideController::class, 'channel']);
            Route::post('overrides/channel', [\App\Http\Controllers\Api\Pricing\OverrideController::class, 'storeChannel']);
            Route::get('overrides/sku', [\App\Http\Controllers\Api\Pricing\OverrideController::class, 'sku']);
            Route::post('overrides/sku', [\App\Http\Controllers\Api\Pricing\OverrideController::class, 'storeSku']);

            Route::post('calculate', [\App\Http\Controllers\Api\Pricing\CalculationController::class, 'calculate']);
            Route::get('calculations', [\App\Http\Controllers\Api\Pricing\CalculationController::class, 'index']);

            Route::post('publish-jobs', [\App\Http\Controllers\Api\Pricing\PublishJobController::class, 'store']);
            Route::post('publish-jobs/{id}/queue', [\App\Http\Controllers\Api\Pricing\PublishJobController::class, 'queue']);
            Route::post('publish-jobs/{id}/run', [\App\Http\Controllers\Api\Pricing\PublishJobController::class, 'run']);
            Route::get('publish-jobs', [\App\Http\Controllers\Api\Pricing\PublishJobController::class, 'index']);
            Route::get('publish-jobs/{id}/export.csv', [\App\Http\Controllers\Api\Pricing\PublishJobController::class, 'exportCsv']);
        });

        // Autopricing
        Route::prefix('autopricing')->group(function () {
            Route::get('policies', [\App\Http\Controllers\Api\Autopricing\PolicyController::class, 'index']);
            Route::post('policies', [\App\Http\Controllers\Api\Autopricing\PolicyController::class, 'store']);
            Route::put('policies/{id}', [\App\Http\Controllers\Api\Autopricing\PolicyController::class, 'update']);

            Route::get('rules', [\App\Http\Controllers\Api\Autopricing\RuleController::class, 'index']);
            Route::post('rules', [\App\Http\Controllers\Api\Autopricing\RuleController::class, 'store']);
            Route::put('rules/{id}', [\App\Http\Controllers\Api\Autopricing\RuleController::class, 'update']);

            Route::get('proposals', [\App\Http\Controllers\Api\Autopricing\ProposalController::class, 'index']);
            Route::post('calc', [\App\Http\Controllers\Api\Autopricing\ProposalController::class, 'calculate']);
            Route::post('proposals/{id}/approve', [\App\Http\Controllers\Api\Autopricing\ProposalController::class, 'approve']);
            Route::post('proposals/{id}/reject', [\App\Http\Controllers\Api\Autopricing\ProposalController::class, 'reject']);
            Route::post('apply', [\App\Http\Controllers\Api\Autopricing\ProposalController::class, 'apply']);

            Route::get('log', [\App\Http\Controllers\Api\Autopricing\LogController::class, 'index']);
        });
    });

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('dialogs/hidden', [DialogAdminController::class, 'hiddenDialogs']);
        Route::get('dialogs/hidden/{dialog}', [DialogAdminController::class, 'showHiddenDialog']);
        Route::get('dialogs/stats', [DialogAdminController::class, 'stats']);
    });
});

/*
|--------------------------------------------------------------------------
| Client API (для Risment)
|--------------------------------------------------------------------------
| API endpoints для клиентов фулфилмент-компании через Risment портал
*/

use App\Http\Controllers\Api\ClientApiController;

// Регистрация и авторизация клиентов (без auth middleware)
Route::prefix('client/auth')->group(function () {
    Route::post('register', [AuthController::class, 'registerClient']);
    Route::post('login', [AuthController::class, 'loginClient']);
});

// Client API endpoints (требуется auth:sanctum)
Route::prefix('client')->middleware(['auth:sanctum'])->group(function () {
    // Профиль
    Route::get('/profile', [ClientApiController::class, 'getProfile']);
    
    // Товары (READ)
    Route::get('/products', [ClientApiController::class, 'getProducts']);
    
    // Товары (WRITE)
    Route::post('/products', [ClientApiController::class, 'createProduct']);
    Route::put('/products/{id}', [ClientApiController::class, 'updateProduct']);
    Route::delete('/products/{id}', [ClientApiController::class, 'deleteProduct']);
    
    // Заказы
    Route::get('/orders', [ClientApiController::class, 'getOrders']);
    
    // Остатки
    Route::get('/inventory', [ClientApiController::class, 'getInventory']);
    
    // Статистика
    Route::get('/statistics', [ClientApiController::class, 'getStatistics']);
});
