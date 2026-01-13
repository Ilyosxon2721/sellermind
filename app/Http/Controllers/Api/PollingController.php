<?php

namespace App\Http\Controllers\Api;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceSyncLog;
use App\Models\Supply;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

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
     * @param Request $request
     * @param int $accountId
     * @return JsonResponse
     */
    public function checkMarketplaceOrders(Request $request, $accountId): JsonResponse
    {
        $request->validate([
            'last_check' => 'nullable|date',
        ]);

        $account = MarketplaceAccount::findOrFail($accountId);

        // Verify user has access to this account
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $lastCheck = $request->input('last_check', now()->subMinutes(5));

        // Cache key unique per user and account
        $cacheKey = "polling:orders:{$accountId}:" . auth()->id() . ":" . $lastCheck;

        $data = Cache::remember($cacheKey, config('polling.cache_ttl', 5), function () use ($accountId, $lastCheck) {
            // Count new orders
            $newOrders = MarketplaceOrder::where('marketplace_account_id', $accountId)
                ->where('created_at', '>', $lastCheck)
                ->count();

            // Count updated orders (where updated_at != created_at)
            $updatedOrders = MarketplaceOrder::where('marketplace_account_id', $accountId)
                ->where('updated_at', '>', $lastCheck)
                ->whereColumn('updated_at', '!=', 'created_at')
                ->count();

            return [
                'has_new' => $newOrders > 0,
                'has_updates' => $updatedOrders > 0,
                'new_count' => $newOrders,
                'updated_count' => $updatedOrders,
                'total_pending' => MarketplaceOrder::where('marketplace_account_id', $accountId)
                    ->whereIn('status', ['new', 'awaiting_packaging', 'awaiting_deliver'])
                    ->count(),
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Check marketplace synchronization status
     *
     * @param Request $request
     * @param int $accountId
     * @return JsonResponse
     */
    public function checkSyncStatus(Request $request, $accountId): JsonResponse
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $cacheKey = "polling:sync:{$accountId}:" . auth()->id();

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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkNotifications(Request $request): JsonResponse
    {
        $request->validate([
            'last_check' => 'nullable|date',
        ]);

        $lastCheck = $request->input('last_check', now()->subMinutes(5));

        $cacheKey = "polling:notifications:" . auth()->id() . ":" . $lastCheck;

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
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getDashboardStats(Request $request): JsonResponse
    {
        $companyId = $request->user()->company_id;

        $cacheKey = "polling:dashboard:{$companyId}:" . auth()->id();

        $data = Cache::remember($cacheKey, config('polling.cache_ttl', 5), function () use ($companyId) {
            // Today's sales
            $todaySales = MarketplaceOrder::whereHas('account', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->whereDate('created_at', today())
                ->count();

            // Total orders
            $totalOrders = MarketplaceOrder::whereHas('account', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })->count();

            // Today's revenue
            $todayRevenue = MarketplaceOrder::whereHas('account', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->whereDate('created_at', today())
                ->sum('total_price');

            // Pending orders
            $pendingOrders = MarketplaceOrder::whereHas('account', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
                ->whereIn('status', ['new', 'awaiting_packaging', 'awaiting_deliver'])
                ->count();

            return [
                'today_sales' => $todaySales,
                'total_orders' => $totalOrders,
                'today_revenue' => (float) $todayRevenue,
                'pending_orders' => $pendingOrders,
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($data);
    }

    /**
     * Check supplies status
     *
     * @param Request $request
     * @param int $accountId
     * @return JsonResponse
     */
    public function checkSupplies(Request $request, $accountId): JsonResponse
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        $request->validate([
            'last_check' => 'nullable|date',
        ]);

        $lastCheck = $request->input('last_check', now()->subMinutes(5));

        $cacheKey = "polling:supplies:{$accountId}:" . auth()->id() . ":" . $lastCheck;

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
}
