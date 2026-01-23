<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use Illuminate\Http\Request;

class SalePrintController extends Controller
{
    /**
     * Check user access to sale
     */
    private function checkAccess(Sale $sale): void
    {
        if (!auth()->user()->hasCompanyAccess($sale->company_id)) {
            abort(403, 'Доступ запрещён');
        }
    }

    /**
     * Print receipt (чек)
     */
    public function receipt(Sale $sale)
    {
        $this->checkAccess($sale);

        $sale->load(['items.productVariant', 'counterparty', 'createdBy', 'warehouse']);
        $company = $sale->company;

        return view('sales.print.receipt', compact('sale', 'company'));
    }

    /**
     * Print invoice (счёт-фактура)
     */
    public function invoice(Sale $sale)
    {
        $this->checkAccess($sale);

        $sale->load(['items.productVariant', 'counterparty', 'createdBy', 'warehouse']);
        $company = $sale->company;

        return view('sales.print.invoice', compact('sale', 'company'));
    }

    /**
     * Print waybill (накладная)
     */
    public function waybill(Sale $sale)
    {
        $this->checkAccess($sale);

        $sale->load(['items.productVariant', 'counterparty', 'createdBy', 'warehouse']);
        $company = $sale->company;

        return view('sales.print.waybill', compact('sale', 'company'));
    }
}
