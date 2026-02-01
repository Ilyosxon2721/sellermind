<?php

declare(strict_types=1);

// file: app/Http/Controllers/MarketplaceSyncLogController.php

namespace App\Http\Controllers;

use App\Models\MarketplaceAccount;
use App\Models\MarketplaceSyncLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MarketplaceSyncLogController extends Controller
{
    /**
     * Отображение страницы логов синхронизации
     */
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'marketplace' => ['nullable', 'string', 'max:50'],
            'account_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'in:success,error,partial'],
            'type' => ['nullable', 'string', 'max:50'],
        ]);

        $user = $request->user();
        // Сначала пробуем прямой company_id, затем fallback на связь companies
        $companyId = $user?->company_id ?? $user?->companies()->first()?->id;

        // Если нет компании — возвращаем пустые результаты
        if (! $companyId) {
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

        // Фильтр по маркетплейсу
        if (! empty($validated['marketplace'])) {
            $query->whereHas('account', function ($q) use ($validated) {
                $q->where('marketplace', $validated['marketplace']);
            });
        }

        // Фильтр по аккаунту
        if (! empty($validated['account_id'])) {
            $query->where('marketplace_account_id', $validated['account_id']);
        }

        // Фильтр по статусу
        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        // Фильтр по типу
        if (! empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $logs = $query->paginate(50);

        // Уникальные маркетплейсы для фильтра (только для текущей компании)
        $marketplaces = MarketplaceAccount::query()
            ->where('company_id', $companyId)
            ->select('marketplace')
            ->distinct()
            ->pluck('marketplace')
            ->all();

        // Аккаунты для фильтра (только для текущей компании)
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
                'marketplace' => $validated['marketplace'] ?? null,
                'account_id' => $validated['account_id'] ?? null,
                'status' => $validated['status'] ?? null,
                'type' => $validated['type'] ?? null,
            ],
            'noCompany' => false,
        ]);
    }
}
