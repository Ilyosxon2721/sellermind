<?php
// file: app/Http/Controllers/Api/MarketplaceSyncController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Marketplace\SyncMarketplaceOrdersJob;
use App\Jobs\Marketplace\SyncMarketplacePricesJob;
use App\Jobs\Marketplace\SyncMarketplaceProductsJob;
use App\Jobs\Marketplace\SyncMarketplaceStocksJob;
use App\Jobs\SyncWildberriesSupplies;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceSyncController extends Controller
{
    public function __construct(
        protected MarketplaceSyncService $syncService
    ) {}

    public function syncPrices(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'async' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('async', true)) {
            SyncMarketplacePricesJob::dispatch($account, $request->product_ids)
                ->onConnection($this->asyncConnection());

            return response()->json([
                'message' => 'Синхронизация цен запущена в фоновом режиме.',
                'queued' => true,
            ]);
        }

        try {
            $this->syncService->syncPrices($account, $request->product_ids);

            return response()->json([
                'message' => 'Цены успешно синхронизированы.',
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ошибка синхронизации: ' . $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncStocks(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'async' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('async', true)) {
            SyncMarketplaceStocksJob::dispatch($account, $request->product_ids)
                ->onConnection($this->asyncConnection());

            return response()->json([
                'message' => 'Синхронизация остатков запущена в фоновом режиме.',
                'queued' => true,
            ]);
        }

        try {
            $this->syncService->syncStocks($account, $request->product_ids);

            return response()->json([
                'message' => 'Остатки успешно синхронизированы.',
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ошибка синхронизации: ' . $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncProducts(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'async' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('async', true)) {
            SyncMarketplaceProductsJob::dispatch($account, $request->product_ids)
                ->onConnection($this->asyncConnection());

            return response()->json([
                'message' => 'Синхронизация товаров запущена в фоновом режиме.',
                'queued' => true,
            ]);
        }

        try {
            $this->syncService->syncProducts($account, $request->product_ids);

            return response()->json([
                'message' => 'Товары успешно синхронизированы.',
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ошибка синхронизации: ' . $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncOrders(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
            'async' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string'],
        ]);

        $from = $request->from ? Carbon::parse($request->from) : null;
        $to = $request->to ? Carbon::parse($request->to) : null;
        $statuses = null;
        if ($status = $request->get('status')) {
            $statuses = array_filter(array_map('trim', explode(',', $status)));
        }

        if ($request->boolean('async', true)) {
            SyncMarketplaceOrdersJob::dispatch($account, $from, $to, $statuses)
                ->onConnection($this->asyncConnection());

            return response()->json([
                'message' => 'Синхронизация заказов запущена в фоновом режиме.',
                'queued' => true,
            ]);
        }

        try {
            $this->syncService->syncOrders($account, $from, $to, $statuses);

            return response()->json([
                'message' => 'Заказы успешно синхронизированы.',
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ошибка синхронизации: ' . $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncSupplies(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'async' => ['nullable', 'boolean'],
        ]);

        if ($request->boolean('async', true)) {
            SyncWildberriesSupplies::dispatch($account)
                ->onConnection($this->asyncConnection());

            return response()->json([
                'message' => 'Синхронизация поставок запущена в фоновом режиме.',
                'queued' => true,
            ]);
        }

        try {
            SyncWildberriesSupplies::dispatchSync($account);

            return response()->json([
                'message' => 'Поставки успешно синхронизированы.',
                'success' => true,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Ошибка синхронизации: ' . $e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncAll(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        // Queue all sync jobs
        $connection = $this->asyncConnection();

        SyncMarketplaceProductsJob::dispatch($account)->onConnection($connection);
        SyncMarketplacePricesJob::dispatch($account)->onConnection($connection);
        SyncMarketplaceStocksJob::dispatch($account)->onConnection($connection);
        SyncMarketplaceOrdersJob::dispatch($account)->onConnection($connection);
        SyncWildberriesSupplies::dispatch($account)->onConnection($connection);

        return response()->json([
            'message' => 'Полная синхронизация запущена.',
            'queued' => true,
        ]);
    }

    /**
     * Определяем безопасное соединение для асинхронных задач.
     * Если по умолчанию стоит sync, принудительно уходим в database,
     * чтобы не держать HTTP-запрос 30+ секунд.
     */
    private function asyncConnection(): string
    {
        $default = config('queue.default', 'sync');
        if ($default === 'sync') {
            // fallback для локалки: таблица jobs уже есть в миграциях
            return 'database';
        }

        return $default;
    }
}
