<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use App\Models\Supply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Polling Controller
 *
 * Provides endpoints for HTTP polling to check for updates
 * Replaces WebSocket (Reverb) for cPanel compatibility
 */
class PollingController extends Controller
{
    /**
     * Check for new or updated marketplace orders
     *
     * @param  int  $accountId
     */
    public function checkMarketplaceOrders(Request $request, $accountId): JsonResponse
    {
        $request->validate([
            'last_check' => 'nullable|date',
        ]);

        $account = MarketplaceAccount::findOrFail($accountId);

        // Verify user has access to this account
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $lastCheck = $request->input('last_check', now()->subMinutes(5));

        // Cache key unique per user and account
        $cacheKey = "polling:orders:{$accountId}:".auth()->id().':'.$lastCheck;

        $data = Cache::remember($cacheKey, config('polling.cache_ttl', 5), function () use ($account, $accountId, $lastCheck) {
            $orderModel = $this->getOrderModelForAccount($account);
            if (! $orderModel) {
                return [
                    'has_new' => false,
                    'has_updates' => false,
                    'new_count' => 0,
                    'updated_count' => 0,
                    'total_pending' => 0,
                    'timestamp' => now()->toIso8601String(),
                ];
            }

            $newOrders = $orderModel::where('marketplace_account_id', $accountId)
                ->where('created_at', '>', $lastCheck)
                ->count();

            $updatedOrders = $orderModel::where('marketplace_account_id', $accountId)
                ->where('updated_at', '>', $lastCheck)
                ->whereColumn('updated_at', '!=', 'created_at')
                ->count();

            $pendingStatuses = match ($account->marketplace) {
                'wb' => ['new'],
                'uzum' => ['new', 'awaiting_packaging', 'awaiting_deliver'],
                'ozon' => ['awaiting_packaging', 'awaiting_deliver'],
                'ym' => ['new', 'pending'],
                default => ['new'],
            };

            return [
                'has_new' => $newOrders > 0,
                'has_updates' => $updatedOrders > 0,
                'new_count' => $newOrders,
                'updated_count' => $updatedOrders,
                'total_pending' => $orderModel::where('marketplace_account_id', $accountId)
                    ->whereIn($account->marketplace === 'ym' ? 'status_normalized' : 'status', $pendingStatuses)
                    ->count(),
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Check marketplace synchronization status
     *
     * @param  int  $accountId
     */
    public function checkSyncStatus(Request $request, $accountId): JsonResponse
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $cacheKey = "polling:sync:{$accountId}:".auth()->id();

        $data = Cache::remember($cacheKey, config('polling.cache_ttl', 5), function () use ($accountId) {
            // Get latest sync log
            $latestSync = MarketplaceSyncLog::where('marketplace_account_id', $accountId)
                ->latest()
                ->first();

            $isSyncing = $latestSync &&
                         $latestSync->status === 'in_progress' &&
                         $latestSync->started_at->isAfter(now()->subMinutes(10));

            $completedRecently = $latestSync &&
                                $latestSync->status === 'completed' &&
                                $latestSync->completed_at &&
                                $latestSync->completed_at->isAfter(now()->subMinutes(2));

            return [
                'is_syncing' => $isSyncing,
                'completed_recently' => $completedRecently,
                'progress' => $latestSync?->metadata['progress'] ?? 0,
                'sync_type' => $latestSync?->sync_type ?? null,
                'last_sync_at' => $latestSync?->completed_at?->toIso8601String(),
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Check for new notifications
     */
    public function checkNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'last_check' => 'nullable|date',
        ]);

        $lastCheck = $request->input('last_check', now()->subMinutes(5));

        $cacheKey = 'polling:notifications:'.auth()->id().':'.$lastCheck;

        $data = Cache::remember($cacheKey, config('polling.cache_ttl', 5), function () use ($request, $lastCheck) {
            $newNotifications = $request->user()
                ->unreadNotifications()
                ->where('created_at', '>', $lastCheck)
                ->get();

            return [
                'has_new' => $newNotifications->isNotEmpty(),
                'count' => $newNotifications->count(),
                'total_unread' => $request->user()->unreadNotifications()->count(),
                'latest' => $newNotifications->take(5)->map(function ($notification) {
                    return [
                        'id' => $notification->id,
                        'type' => $notification->type,
                        'data' => $notification->data,
                        'created_at' => $notification->created_at->toIso8601String(),
                    ];
                }),
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $cacheKey = "polling:dashboard:{$companyId}:".auth()->id();

        $data = Cache::remember($cacheKey, config('polling.cache_ttl', 5), function () use ($companyId) {
            $accountIds = MarketplaceAccount::where('company_id', $companyId)
                ->where('is_active', true)
                ->pluck('id', 'marketplace');

            $todaySales = 0;
            $totalOrders = 0;
            $todayRevenue = 0.0;
            $pendingOrders = 0;

            $models = [
                'wb' => [\App\Models\WbOrder::class, 'total_amount', 'status', ['new']],
                'uzum' => [\App\Models\UzumOrder::class, 'total_amount', 'status', ['new', 'awaiting_packaging', 'awaiting_deliver']],
                'ozon' => [\App\Models\OzonOrder::class, 'total_price', 'status', ['awaiting_packaging', 'awaiting_deliver']],
                'ym' => [\App\Models\YandexMarketOrder::class, 'total_price', 'status_normalized', ['new', 'pending']],
            ];

            foreach ($accountIds as $marketplace => $id) {
                $config = $models[$marketplace] ?? null;
                if (! $config) {
                    continue;
                }
                [$modelClass, $amountField, $statusField, $pendingStatuses] = $config;

                $todaySales += $modelClass::where('marketplace_account_id', $id)
                    ->whereDate('created_at', today())
                    ->count();

                $totalOrders += $modelClass::where('marketplace_account_id', $id)->count();

                $todayRevenue += (float) $modelClass::where('marketplace_account_id', $id)
                    ->whereDate('created_at', today())
                    ->sum($amountField);

                $pendingOrders += $modelClass::where('marketplace_account_id', $id)
                    ->whereIn($statusField, $pendingStatuses)
                    ->count();
            }

            return [
                'today_sales' => $todaySales,
                'total_orders' => $totalOrders,
                'today_revenue' => $todayRevenue,
                'pending_orders' => $pendingOrders,
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Check supplies status
     *
     * @param  int  $accountId
     */
    public function checkSupplies(Request $request, $accountId): JsonResponse
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'last_check' => 'nullable|date',
        ]);

        $lastCheck = $request->input('last_check', now()->subMinutes(5));

        $cacheKey = "polling:supplies:{$accountId}:".auth()->id().':'.$lastCheck;

        $data = Cache::remember($cacheKey, config('polling.cache_ttl', 5), function () use ($accountId, $lastCheck) {
            // New supplies
            $newSupplies = Supply::where('marketplace_account_id', $accountId)
                ->where('created_at', '>', $lastCheck)
                ->count();

            // Updated supplies
            $updatedSupplies = Supply::where('marketplace_account_id', $accountId)
                ->where('updated_at', '>', $lastCheck)
                ->whereColumn('updated_at', '!=', 'created_at')
                ->count();

            // Open supplies count
            $openSupplies = Supply::where('marketplace_account_id', $accountId)
                ->whereIn('status', ['draft', 'in_assembly'])
                ->whereNull('closed_at')
                ->count();

            return [
                'has_new' => $newSupplies > 0,
                'has_updates' => $updatedSupplies > 0,
                'new_count' => $newSupplies,
                'updated_count' => $updatedSupplies,
                'open_supplies' => $openSupplies,
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Получить класс модели заказов для аккаунта маркетплейса
     */
    private function getOrderModelForAccount(MarketplaceAccount $account): ?string
    {
        return match ($account->marketplace) {
            'wb' => \App\Models\WbOrder::class,
            'uzum' => \App\Models\UzumOrder::class,
            'ozon' => \App\Models\OzonOrder::class,
            'ym' => \App\Models\YandexMarketOrder::class,
            default => null,
        };
    }
}
