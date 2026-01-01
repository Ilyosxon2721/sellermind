<?php
// file: app/Http/Controllers/MarketplaceSyncLogController.php

namespace App\Http\Controllers;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceSyncLogController extends Controller
{
    /**
     * Display sync logs page (web view)
     */
    public function index(Request $request): View
    {
        $query = MarketplaceSyncLog::query()
            ->with('account')
            ->orderByDesc('created_at');

        // Filter by marketplace
        if ($request->filled('marketplace')) {
            $query->whereHas('account', function ($q) use ($request) {
                $q->where('marketplace', $request->get('marketplace'));
            });
        }

        // Filter by account
        if ($request->filled('account_id')) {
            $query->where('marketplace_account_id', $request->get('account_id'));
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        // Filter by type
        if ($request->filled('type')) {
            $query->where('type', $request->get('type'));
        }

        $logs = $query->paginate(50);

        // Get unique marketplaces for filter
        $marketplaces = MarketplaceAccount::query()
            ->select('marketplace')
            ->distinct()
            ->pluck('marketplace')
            ->all();

        // Get accounts for filter
        $accounts = MarketplaceAccount::query()
            ->select('id', 'name', 'marketplace')
            ->orderBy('name')
            ->get();

        return view('pages.marketplace.sync-logs', [
            'logs' => $logs,
            'marketplaces' => $marketplaces,
            'accounts' => $accounts,
            'filters' => [
                'marketplace' => $request->get('marketplace'),
                'account_id' => $request->get('account_id'),
                'status' => $request->get('status'),
                'type' => $request->get('type'),
            ],
        ]);
    }
}
