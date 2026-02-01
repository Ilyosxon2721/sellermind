<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccountIssue;
use App\Services\Marketplaces\IssueDetectorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceIssuesController extends Controller
{
    protected IssueDetectorService $issueDetector;

    public function __construct(IssueDetectorService $issueDetector)
    {
        $this->issueDetector = $issueDetector;
    }

    /**
     * Получить список проблем для компании
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
            'status' => 'nullable|in:active,resolved,ignored',
            'severity' => 'nullable|in:critical,warning,info',
        ]);

        $query = MarketplaceAccountIssue::with('account')
            ->where('company_id', $request->company_id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('severity')) {
            $query->where('severity', $request->severity);
        }

        $issues = $query->orderBy('severity', 'desc')
            ->orderBy('last_occurred_at', 'desc')
            ->get();

        return response()->json([
            'issues' => $issues->map(function($issue) {
                return [
                    'id' => $issue->id,
                    'marketplace_account_id' => $issue->marketplace_account_id,
                    'account_name' => $issue->account->name ?? $issue->account->getDisplayName(),
                    'marketplace' => $issue->account->marketplace,
                    'type' => $issue->type,
                    'type_label' => $issue->getTypeLabel(),
                    'severity' => $issue->severity,
                    'severity_icon' => $issue->getSeverityIcon(),
                    'severity_color' => $issue->getSeverityColor(),
                    'title' => $issue->title,
                    'description' => $issue->description,
                    'status' => $issue->status,
                    'occurrences' => $issue->occurrences,
                    'last_occurred_at' => $issue->last_occurred_at->toIso8601String(),
                    'created_at' => $issue->created_at->toIso8601String(),
                    'resolution_steps' => $this->issueDetector->getResolutionSteps(
                        $issue->type,
                        $issue->account->marketplace
                    ),
                ];
            }),
        ]);
    }

    /**
     * Отметить проблему как решённую
     */
    public function resolve(int $id): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $issue = MarketplaceAccountIssue::where('company_id', $companyId)->findOrFail($id);
        $issue->markAsResolved();

        return response()->json([
            'message' => 'Проблема отмечена как решённая',
            'issue' => $issue,
        ]);
    }

    /**
     * Игнорировать проблему
     */
    public function ignore(int $id): JsonResponse
    {
        $companyId = auth()->user()->company_id;
        $issue = MarketplaceAccountIssue::where('company_id', $companyId)->findOrFail($id);
        $issue->markAsIgnored();

        return response()->json([
            'message' => 'Проблема проигнорирована',
            'issue' => $issue,
        ]);
    }

    /**
     * Получить количество активных проблем
     */
    public function count(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'required|exists:companies,id',
        ]);

        $counts = [
            'total' => MarketplaceAccountIssue::forCompany($request->company_id)
                ->active()
                ->count(),
            'critical' => MarketplaceAccountIssue::forCompany($request->company_id)
                ->active()
                ->critical()
                ->count(),
            'warning' => MarketplaceAccountIssue::forCompany($request->company_id)
                ->active()
                ->where('severity', 'warning')
                ->count(),
            'info' => MarketplaceAccountIssue::forCompany($request->company_id)
                ->active()
                ->where('severity', 'info')
                ->count(),
        ];

        return response()->json($counts);
    }
}
