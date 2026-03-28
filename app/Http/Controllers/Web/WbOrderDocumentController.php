<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\WbOrder;
use App\Services\Marketplaces\Wildberries\WbOrderDocumentService;
use Illuminate\Http\Request;

/**
 * Контроллер печати документов для заказов Wildberries.
 *
 * Поддерживаемые типы документов:
 * - receipt  — Кассовый чек (80мм, формат для термопринтера)
 * - waybill  — Товарная накладная (А4)
 * - invoice  — Счёт-фактура (А4)
 */
class WbOrderDocumentController extends Controller
{
    public function __construct(
        private readonly WbOrderDocumentService $documentService,
    ) {}

    /**
     * Печать чека для одного заказа
     *
     * GET /marketplace/{accountId}/wb-orders/{orderId}/print/receipt
     */
    public function receipt(Request $request, int $accountId, int $orderId)
    {
        $order = $this->resolveOrder($request, $accountId, $orderId);

        $data = $this->documentService->getReceiptData($order);

        return view('pages.marketplace.wb-print.receipt', compact('data'));
    }

    /**
     * Печать товарной накладной для одного заказа
     *
     * GET /marketplace/{accountId}/wb-orders/{orderId}/print/waybill
     */
    public function waybill(Request $request, int $accountId, int $orderId)
    {
        $order = $this->resolveOrder($request, $accountId, $orderId);

        $data = $this->documentService->getWaybillData($order);

        return view('pages.marketplace.wb-print.waybill', compact('data'));
    }

    /**
     * Печать счёт-фактуры для одного заказа
     *
     * GET /marketplace/{accountId}/wb-orders/{orderId}/print/invoice
     */
    public function invoice(Request $request, int $accountId, int $orderId)
    {
        $order = $this->resolveOrder($request, $accountId, $orderId);

        $data = $this->documentService->getInvoiceData($order);

        return view('pages.marketplace.wb-print.invoice', compact('data'));
    }

    /**
     * Массовая печать документов для нескольких заказов
     *
     * GET /marketplace/{accountId}/wb-orders/print-bulk/{type}?ids=1,2,3
     */
    public function bulk(Request $request, int $accountId, string $type)
    {
        if (! in_array($type, ['receipt', 'waybill', 'invoice'])) {
            abort(400, 'Неверный тип документа. Допустимые: receipt, waybill, invoice');
        }

        $account = MarketplaceAccount::findOrFail($accountId);

        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            abort(403, 'Доступ запрещён.');
        }

        $ids = array_filter(
            array_map('intval', explode(',', $request->query('ids', '')))
        );

        if (empty($ids)) {
            abort(400, 'Укажите ID заказов в параметре ids.');
        }

        $documents = $this->documentService->getBulkDocumentData($ids, $account, $type);

        if (empty($documents)) {
            abort(404, 'Заказы не найдены.');
        }

        return view("pages.marketplace.wb-print.{$type}", ['data' => $documents[0]]);
    }

    /**
     * Найти и проверить доступ к заказу
     */
    private function resolveOrder(Request $request, int $accountId, int $orderId): WbOrder
    {
        $account = MarketplaceAccount::findOrFail($accountId);

        if (! $request->user()->hasCompanyAccess($account->company_id)) {
            abort(403, 'Доступ запрещён.');
        }

        return WbOrder::where('marketplace_account_id', $accountId)
            ->where('id', $orderId)
            ->with('items')
            ->firstOrFail();
    }
}
