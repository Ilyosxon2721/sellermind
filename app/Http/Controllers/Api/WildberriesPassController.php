<?php

// file: app/Http/Controllers/Api/WildberriesPassController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesPassService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WildberriesPassController extends Controller
{
    protected WildberriesPassService $passService;

    public function __construct(WildberriesPassService $passService)
    {
        $this->passService = $passService;
    }

    /**
     * Get list of warehouse offices requiring passes
     */
    public function offices(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $offices = $this->passService->getOfficesRequiringPasses($account);

            return response()->json([
                'success' => true,
                'offices' => $offices,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB offices', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить список офисов: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get list of passes
     */
    public function index(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $passes = $this->passService->getPasses($account);

            return response()->json([
                'success' => true,
                'passes' => $passes,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB passes', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить список пропусков: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new pass
     */
    public function store(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'firstName' => 'required|string|max:100',
            'lastName' => 'required|string|max:100',
            'carModel' => 'nullable|string|max:100',
            'carNumber' => 'nullable|string|max:50',
            'officeId' => 'required|string|max:100',
            'dateFrom' => 'required|date_format:Y-m-d',
            'dateTo' => 'required|date_format:Y-m-d|after_or_equal:dateFrom',
        ]);

        try {
            $pass = $this->passService->createPass($account, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Пропуск успешно создан',
                'pass' => $pass,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create WB pass', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать пропуск: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update existing pass
     */
    public function update(Request $request, MarketplaceAccount $account, string $passId): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'firstName' => 'sometimes|string|max:100',
            'lastName' => 'sometimes|string|max:100',
            'carModel' => 'nullable|string|max:100',
            'carNumber' => 'nullable|string|max:50',
            'dateFrom' => 'sometimes|date_format:Y-m-d',
            'dateTo' => 'sometimes|date_format:Y-m-d',
        ]);

        try {
            $pass = $this->passService->updatePass($account, $passId, $validated);

            return response()->json([
                'success' => true,
                'message' => 'Пропуск успешно обновлён',
                'pass' => $pass,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update WB pass', [
                'account_id' => $account->id,
                'pass_id' => $passId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось обновить пропуск: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete pass
     */
    public function destroy(Request $request, MarketplaceAccount $account, string $passId): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $this->passService->deletePass($account, $passId);

            return response()->json([
                'success' => true,
                'message' => 'Пропуск успешно удалён',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete WB pass', [
                'account_id' => $account->id,
                'pass_id' => $passId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось удалить пропуск: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get passes expiring soon
     */
    public function expiring(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $daysAhead = $request->input('days_ahead', 7);

        try {
            $expiring = $this->passService->getExpiringSoon($account, $daysAhead);

            return response()->json([
                'success' => true,
                'passes' => $expiring,
                'count' => count($expiring),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get expiring WB passes', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить истекающие пропуски: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cleanup expired passes
     */
    public function cleanup(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (! $account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $deleted = $this->passService->cleanupExpiredPasses($account);

            return response()->json([
                'success' => true,
                'message' => "Удалено просроченных пропусков: {$deleted}",
                'deleted_count' => $deleted,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired WB passes', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось очистить просроченные пропуски: '.$e->getMessage(),
            ], 500);
        }
    }
}
