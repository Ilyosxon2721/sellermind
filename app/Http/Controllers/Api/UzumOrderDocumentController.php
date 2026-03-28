<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceAccount;
use App\Models\MarketplaceShop;
use App\Models\UzumOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Генерация документов для заказов Uzum Market:
 * чек (receipt), накладная (waybill), счёт-фактура (invoice)
 */
final class UzumOrderDocumentController extends Controller
{
    /**
     * Генерация чека
     */
    public function receipt(Request $request, MarketplaceAccount $account, string $orderId): Response
    {
        $order = $this->findOrder($account, $orderId);

        $pdf = Pdf::loadView('pdf.uzum-receipt', $this->viewData($order, $account));
        $pdf->setPaper([0, 0, 226.77, 800], 'portrait');

        return $request->boolean('download')
            ? $pdf->download("receipt-{$orderId}.pdf")
            : $pdf->stream("receipt-{$orderId}.pdf");
    }

    /**
     * Генерация товарной накладной
     */
    public function waybill(Request $request, MarketplaceAccount $account, string $orderId): Response
    {
        $order = $this->findOrder($account, $orderId);

        $pdf = Pdf::loadView('pdf.uzum-waybill', $this->viewData($order, $account));
        $pdf->setPaper('a4', 'portrait');

        return $request->boolean('download')
            ? $pdf->download("waybill-{$orderId}.pdf")
            : $pdf->stream("waybill-{$orderId}.pdf");
    }

    /**
     * Генерация счёт-фактуры
     */
    public function invoice(Request $request, MarketplaceAccount $account, string $orderId): Response
    {
        $order = $this->findOrder($account, $orderId);

        $data = $this->viewData($order, $account);
        $data['invoiceNumber'] = 'SF-' . $orderId . '-' . now()->format('ymd');

        $pdf = Pdf::loadView('pdf.uzum-invoice', $data);
        $pdf->setPaper('a4', 'portrait');

        return $request->boolean('download')
            ? $pdf->download("invoice-{$orderId}.pdf")
            : $pdf->stream("invoice-{$orderId}.pdf");
    }

    private function findOrder(MarketplaceAccount $account, string $orderId): UzumOrder
    {
        $order = UzumOrder::where('marketplace_account_id', $account->id)
            ->where('external_order_id', $orderId)
            ->with('items')
            ->first();

        if (! $order) {
            abort(404, "Заказ #{$orderId} не найден.");
        }

        return $order;
    }

    private function viewData(UzumOrder $order, MarketplaceAccount $account): array
    {
        $shop = MarketplaceShop::where('marketplace_account_id', $account->id)
            ->where('external_id', $order->shop_id)
            ->first();

        return [
            'order' => $order,
            'items' => $order->items,
            'account' => $account,
            'shopName' => $shop?->name ?? $account->name ?? 'Uzum Market',
            'date' => now()->format('d.m.Y H:i'),
        ];
    }
}
