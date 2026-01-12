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
        $user = $request->user();
        $companyId = $user?->company_id;

        // If no company, return empty results
        if (!$companyId) {
            return view('pages.marketplace.sync-logs', [
                'logs' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, 50),
                'marketplaces' => [],
                'accounts' => collect(),
                'filters' => [
                    'marketplace' => null,
                    'account_id' => null,
                    'status' => null,
                    'type' => null,
                ],
                'noCompany' => true,
            ]);
        }

        $query = MarketplaceSyncLog::query()
            ->with('account')
            ->whereHas('account', function ($q) use ($companyId) {
                $q->where('company_id', $companyId);
            })
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

        // Get unique marketplaces for filter (only for current company)
        $marketplaces = MarketplaceAccount::query()
            ->where('company_id', $companyId)
            ->select('marketplace')
            ->distinct()
            ->pluck('marketplace')
            ->all();

        // Get accounts for filter (only for current company)
        $accounts = MarketplaceAccount::query()
            ->where('company_id', $companyId)
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
            'noCompany' => false,
        ]);
    }
}
