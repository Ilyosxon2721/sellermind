<?php
// file: app/Http/Controllers/Api/WildberriesSupplyController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WildberriesSupplyController extends Controller
{
    protected WildberriesOrderService $orderService;

    public function __construct(WildberriesOrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Get list of supplies
     */
    public function index(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $limit = $request->input('limit', 1000);
        $next = $request->input('next', 0);

        try {
            $result = $this->orderService->getSupplies($account, $limit, $next);

            return response()->json([
                'success' => true,
                'supplies' => $result['supplies'] ?? [],
                'next' => $result['next'] ?? null,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB supplies', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить список поставок: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create new supply
     */
    public function store(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $supply = $this->orderService->createSupply($account, $validated['name']);

            return response()->json([
                'success' => true,
                'message' => 'Поставка успешно создана',
                'supply' => $supply,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create WB supply', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось создать поставку: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supply details
     */
    public function show(Request $request, MarketplaceAccount $account, string $supplyId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $supply = $this->orderService->getSupplyDetails($account, $supplyId);

            return response()->json([
                'success' => true,
                'supply' => $supply,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB supply details', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить детали поставки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get orders in supply
     */
    public function orders(Request $request, MarketplaceAccount $account, string $supplyId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $orders = $this->orderService->getSupplyOrders($account, $supplyId);

            return response()->json([
                'success' => true,
                'orders' => $orders,
                'count' => count($orders),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB supply orders', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить заказы поставки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Add orders to supply
     */
    public function addOrders(Request $request, MarketplaceAccount $account, string $supplyId): JsonResponse
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
            $this->orderService->addOrdersToSupply($account, $supplyId, $validated['order_ids']);

            return response()->json([
                'success' => true,
                'message' => 'Заказы успешно добавлены в поставку',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to add orders to WB supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось добавить заказы в поставку: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove order from supply
     */
    public function removeOrder(Request $request, MarketplaceAccount $account, string $supplyId, int $orderId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $this->orderService->removeOrderFromSupply($account, $supplyId, $orderId);

            return response()->json([
                'success' => true,
                'message' => 'Заказ успешно удалён из поставки',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to remove order from WB supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось удалить заказ из поставки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get supply barcode/QR code
     */
    public function barcode(Request $request, MarketplaceAccount $account, string $supplyId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        $type = $request->input('type', 'png');

        try {
            $result = $this->orderService->getSupplyBarcode($account, $supplyId, $type);

            // Возвращаем base64 чтобы frontend мог скачать через blob
            // Это решает проблему с авторизацией при открытии в новой вкладке
            return response()->json([
                'success' => true,
                'file_content' => base64_encode($result['file_content']),
                'content_type' => $result['content_type'],
                'format' => $result['format'],
                'filename' => "supply-{$supplyId}-barcode.{$result['format']}",
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB supply barcode', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить баркод поставки: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cancel supply
     */
    public function cancel(Request $request, MarketplaceAccount $account, string $supplyId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $this->orderService->cancelSupply($account, $supplyId);

            return response()->json([
                'success' => true,
                'message' => 'Поставка успешно отменена',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to cancel WB supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось отменить поставку: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Deliver supply to warehouse
     */
    public function deliver(Request $request, MarketplaceAccount $account, string $supplyId): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $this->orderService->deliverSupply($account, $supplyId);

            return response()->json([
                'success' => true,
                'message' => 'Поставка отмечена как доставленная',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to deliver WB supply', [
                'account_id' => $account->id,
                'supply_id' => $supplyId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось отметить поставку как доставленную: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get orders requiring reshipment
     */
    public function reshipmentOrders(Request $request, MarketplaceAccount $account): JsonResponse
    {
        if (!$request->user()->hasCompanyAccess($account->company_id)) {
            return response()->json(['message' => 'Доступ запрещён.'], 403);
        }

        if (!$account->isWildberries()) {
            return response()->json(['message' => 'Аккаунт не является Wildberries.'], 400);
        }

        try {
            $orders = $this->orderService->getReshipmentOrders($account);

            return response()->json([
                'success' => true,
                'orders' => $orders,
                'count' => count($orders),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to get WB reshipment orders', [
                'account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Не удалось получить заказы для повторной отправки: ' . $e->getMessage(),
            ], 500);
        }
    }
}
