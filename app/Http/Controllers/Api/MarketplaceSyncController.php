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
use App\Models\MarketplaceSyncLog;
use App\Services\Marketplaces\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceSyncController extends Controller
{
    public function __construct(
        protected MarketplaceSyncService $syncService
    ) {}

    public function syncPrices(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
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
            Log::error('Ошибка синхронизации цен', ['account_id' => $account->id, 'marketplace' => $account->marketplace, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ошибка синхронизации: '.$e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncStocks(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
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
            Log::error('Ошибка синхронизации остатков', ['account_id' => $account->id, 'marketplace' => $account->marketplace, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ошибка синхронизации: '.$e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncProducts(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
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
            Log::error('Ошибка синхронизации товаров', ['account_id' => $account->id, 'marketplace' => $account->marketplace, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ошибка синхронизации: '.$e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncOrders(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
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
            Log::error('Ошибка синхронизации заказов', ['account_id' => $account->id, 'marketplace' => $account->marketplace, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ошибка синхронизации: '.$e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncSupplies(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
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
            Log::error('Ошибка синхронизации поставок', ['account_id' => $account->id, 'marketplace' => $account->marketplace, 'error' => $e->getMessage()]);

            return response()->json([
                'message' => 'Ошибка синхронизации: '.$e->getMessage(),
                'success' => false,
            ], 500);
        }
    }

    public function syncAll(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
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
     * Статус последней синхронизации по типу (stocks/prices/products/orders)
     */
    public function syncStatus(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $type = $request->query('type', 'stocks');

        // since — unix timestamp (мс) момента нажатия кнопки
        // Если передан, возвращаем только логи строго позже этого момента
        $sinceMs = $request->query('since');
        $query = MarketplaceSyncLog::where('marketplace_account_id', $account->id)
            ->where('type', $type);

        if ($sinceMs) {
            $sinceCarbon = \Carbon\Carbon::createFromTimestampMs((int) $sinceMs);
            $query->where('started_at', '>=', $sinceCarbon);
        }

        $log = $query->latest('started_at')->first();

        // Нет лога после since — job ещё не начался, считаем pending
        if (! $log) {
            return response()->json(['status' => 'pending', 'is_running' => true]);
        }

        $duration = null;
        if ($log->started_at && $log->finished_at) {
            $duration = $log->finished_at->diffInSeconds($log->started_at);
        } elseif ($log->started_at && $log->status === MarketplaceSyncLog::STATUS_RUNNING) {
            $duration = now()->diffInSeconds($log->started_at);
        }

        return response()->json([
            'status'     => $log->status,
            'message'    => $log->message,
            'started_at' => $log->started_at?->toISOString(),
            'finished_at'=> $log->finished_at?->toISOString(),
            'duration'   => $duration,
            'is_running' => $log->status === MarketplaceSyncLog::STATUS_RUNNING,
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
