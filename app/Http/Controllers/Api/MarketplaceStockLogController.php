<?php

// file: app/Http/Controllers/Api/MarketplaceStockLogController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceStockLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceStockLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'account_id' => ['required', 'exists:marketplace_accounts,id'],
            'limit' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $account = MarketplaceAccount::findOrFail($data['account_id']);
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён'], 403);
        }

        $limit = $request->integer('limit', 20);

        $logs = MarketplaceStockLog::where('marketplace_account_id', $account->id)
            ->latest()
            ->limit($limit)
            ->get();

        return response()->json(['logs' => $logs]);
    }
}
