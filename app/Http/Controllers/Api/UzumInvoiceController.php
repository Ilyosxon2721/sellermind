<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Services\Uzum\Api\UzumApiManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Контроллер накладных и возвратов Uzum Market
 */
final class UzumInvoiceController extends Controller
{
    // ─── FBS НАКЛАДНЫЕ ─────────────────────────────────────────

    /**
     * Список FBS накладных
     */
    public function fbsList(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);

        $statuses = $request->input('statuses', []);
        if (is_string($statuses)) {
            $statuses = array_filter(explode(',', $statuses));
        }

        $page = (int) $request->input('page', 0);
        $size = (int) $request->input('size', 20);

        $data = $uzum->invoices()->fbsList($statuses, $page, $size);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Создать FBS накладную
     */
    public function fbsCreate(Request $request, MarketplaceAccount $account): JsonResponse
    {
        $this->authorize('update', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->fbsCreate($request->all());

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Детали FBS накладной
     */
    public function fbsDetail(MarketplaceAccount $account, int $invoiceId): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->fbsDetail($invoiceId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Заказы в FBS накладной
     */
    public function fbsOrders(MarketplaceAccount $account, int $invoiceId): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->fbsOrders($invoiceId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Акт приёмки FBS накладной (PDF)
     */
    public function fbsClosingDocs(MarketplaceAccount $account, int $invoiceId): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->fbsClosingDocs($invoiceId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ─── ПОСТАВКИ FBO ──────────────────────────────────────────

    /**
     * Накладные поставки магазина
     */
    public function shopInvoices(MarketplaceAccount $account, int $shopId): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->shopInvoices($shopId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Состав накладной поставки
     */
    public function shopInvoiceProducts(MarketplaceAccount $account, int $shopId): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->shopInvoiceProducts($shopId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    // ─── ВОЗВРАТЫ ──────────────────────────────────────────────

    /**
     * Накладные возврата магазина
     */
    public function returns(MarketplaceAccount $account, int $shopId): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->returns($shopId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Детали возврата
     */
    public function returnDetail(MarketplaceAccount $account, int $shopId, int $returnId): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->returnDetail($shopId, $returnId);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Все возвраты продавца
     */
    public function allReturns(MarketplaceAccount $account): JsonResponse
    {
        $this->authorize('view', $account);

        $uzum = new UzumApiManager($account);
        $data = $uzum->invoices()->allReturns();

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
