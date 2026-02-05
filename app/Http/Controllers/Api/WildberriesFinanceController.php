<?php

// file: app/Http/Controllers/Api/WildberriesFinanceController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesFinanceService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class WildberriesFinanceController extends Controller
{
    protected WildberriesFinanceService $financeService;

    public function __construct(WildberriesFinanceService $financeService)
    {
        $this->financeService = $financeService;
    }

    /**
     * Get account balance
     */
    public function balance(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $balance = $this->financeService->getBalance($account);

            return response()->json([
                'success' => true,
                'balance' => $balance,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB balance', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить баланс: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed sales report
     */
    public function report(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'full' => 'sometimes|boolean',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : now();
        $full = $validated['full'] ?? false;

        try {
            if ($full) {
                $reportData = $this->financeService->getFullDetailedReport($account, $dateFrom, $dateTo);
            } else {
                $reportData = $this->financeService->getDetailedReport($account, $dateFrom, $dateTo);
            }

            $summary = $this->financeService->calculateSummary($reportData);

            return response()->json([
                'success' => true,
                'report' => $reportData,
                'summary' => $summary,
                'period' => [
                    'from' => $dateFrom->format('Y-m-d'),
                    'to' => $dateTo->format('Y-m-d'),
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB detailed report', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить детальный отчёт: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get document categories
     */
    public function documentCategories(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $categories = $this->financeService->getDocumentCategories($account);

            return response()->json([
                'success' => true,
                'categories' => $categories,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB document categories', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить категории документов: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get documents list
     */
    public function documents(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'category' => 'sometimes|string|max:100',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        $category = $validated['category'] ?? null;
        $dateFrom = isset($validated['date_from']) ? Carbon::parse($validated['date_from']) : null;
        $dateTo = isset($validated['date_to']) ? Carbon::parse($validated['date_to']) : null;

        try {
            $documents = $this->financeService->getDocuments($account, $category, $dateFrom, $dateTo);

            return response()->json([
                'success' => true,
                'documents' => $documents,
                'count' => count($documents),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB documents', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить список документов: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download document
     */
    public function downloadDocument(Request $request, MarketplaceAccount $account, string $documentId): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $result = $this->financeService->downloadDocument($account, $documentId, true);

            if ($result['file_path'] && Storage::disk('local')->exists($result['file_path'])) {
                $content = Storage::disk('local')->get($result['file_path']);

                return response($content, 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', "attachment; filename=\"{$result['filename']}\"");
            }

            return response()->json([
                'success' => false,
                'message' => 'Файл не найден после загрузки',
            ], 500);
        } catch (\Exception $e) {
            Log::error('Failed to download WB document', [
                'account_id' => $account->id,
                'document_id' => $documentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось скачать документ: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Download all documents for period
     */
    public function downloadAll(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'date_from' => 'required|date',
            'date_to' => 'required|date|after_or_equal:date_from',
        ]);

        $dateFrom = Carbon::parse($validated['date_from']);
        $dateTo = Carbon::parse($validated['date_to']);

        try {
            $downloaded = $this->financeService->downloadAllDocuments($account, $dateFrom, $dateTo, true);

            $successful = array_filter($downloaded, fn ($d) => ! isset($d['error']));
            $failed = array_filter($downloaded, fn ($d) => isset($d['error']));

            return response()->json([
                'success' => true,
                'message' => 'Документы успешно загружены',
                'total' => count($downloaded),
                'successful' => count($successful),
                'failed' => count($failed),
                'documents' => $downloaded,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to download all WB documents', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось загрузить документы: '.$e->getMessage(),
            ], 500);
        }
    }
}
