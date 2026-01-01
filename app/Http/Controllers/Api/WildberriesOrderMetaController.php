<?php
// file: app/Http/Controllers/Api/WildberriesOrderMetaController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesOrderMetaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WildberriesOrderMetaController extends Controller
{
    protected WildberriesOrderMetaService $metaService;

    public function __construct(WildberriesOrderMetaService $metaService)
    {
        $this->metaService = $metaService;
    }

    /**
     * Attach SGTIN marking code to order
     */
    public function attachSGTIN(Request $request, MarketplaceAccount $account, int $orderId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'sgtin' => 'required|string|max:100',
        ]);

        try {
            $this->metaService->attachSGTIN($account, $orderId, $validated['sgtin']);

            return response()->json([
                'success' => true,
                'message' => 'SGTIN код успешно прикреплён к заказу',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach SGTIN to WB order', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось прикрепить SGTIN код: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach UIN to order
     */
    public function attachUIN(Request $request, MarketplaceAccount $account, int $orderId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'uin' => 'required|string|max:100',
        ]);

        try {
            $this->metaService->attachUIN($account, $orderId, $validated['uin']);

            return response()->json([
                'success' => true,
                'message' => 'UIN код успешно прикреплён к заказу',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach UIN to WB order', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось прикрепить UIN код: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach IMEI to order
     */
    public function attachIMEI(Request $request, MarketplaceAccount $account, int $orderId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'imei' => 'required|string|max:100',
        ]);

        try {
            $this->metaService->attachIMEI($account, $orderId, $validated['imei']);

            return response()->json([
                'success' => true,
                'message' => 'IMEI код успешно прикреплён к заказу',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach IMEI to WB order', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось прикрепить IMEI код: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach GTIN to order
     */
    public function attachGTIN(Request $request, MarketplaceAccount $account, int $orderId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'gtin' => 'required|string|max:100',
        ]);

        try {
            $this->metaService->attachGTIN($account, $orderId, $validated['gtin']);

            return response()->json([
                'success' => true,
                'message' => 'GTIN код успешно прикреплён к заказу',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach GTIN to WB order', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось прикрепить GTIN код: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach expiration date to order
     */
    public function attachExpiration(Request $request, MarketplaceAccount $account, int $orderId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'expiration_date' => 'required|date_format:Y-m-d',
        ]);

        try {
            $this->metaService->attachExpiration($account, $orderId, $validated['expiration_date']);

            return response()->json([
                'success' => true,
                'message' => 'Срок годности успешно прикреплён к заказу',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to attach expiration date to WB order', [
                'account_id' => $account->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось прикрепить срок годности: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Batch attach metadata to orders
     */
    public function batchAttach(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'metadata' => 'required|array|min:1',
            'metadata.*.order_id' => 'required|integer',
            'metadata.*.type' => 'required|string|in:sgtin,uin,imei,gtin,expiration',
            'metadata.*.value' => 'required|string|max:100',
        ]);

        try {
            $results = $this->metaService->batchAttachMeta($account, $validated['metadata']);

            $successful = array_filter($results, fn($r) => $r['success']);
            $failed = array_filter($results, fn($r) => !$r['success']);

            return response()->json([
                'success' => true,
                'message' => 'Массовое прикрепление метаданных завершено',
                'total' => count($results),
                'successful' => count($successful),
                'failed' => count($failed),
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to batch attach metadata to WB orders', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось выполнить массовое прикрепление: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get orders metadata
     */
    public function getMeta(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer',
        ]);

        try {
            $metadata = $this->metaService->getOrdersMeta($account, $validated['order_ids']);

            return response()->json([
                'success' => true,
                'metadata' => $metadata,
                'count' => count($metadata),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB orders metadata', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить метаданные заказов: ' . $e->getMessage(),
            ], 500);
        }
    }
}
