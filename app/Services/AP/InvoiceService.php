<?php

namespace App\Services\AP;

use App\Models\AP\Supplier;
use App\Models\AP\SupplierInvoice;
use App\Models\AP\SupplierInvoiceLine;
use App\Models\Finance\FinanceDebt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class InvoiceService
{
    public function create(array $data, array $lines = []): SupplierInvoice
    {
        return DB::transaction(function () use ($data, $lines) {
            $invoice = SupplierInvoice::create($data);
            if (! empty($lines)) {
                $this->replaceLines($invoice, $lines);
            }
            $this->recalc($invoice->id);

            return $invoice->fresh('lines');
        });
    }

    public function updateDraft(int $invoiceId, int $companyId, array $data, array $lines = []): SupplierInvoice
    {
        return DB::transaction(function () use ($invoiceId, $companyId, $data, $lines) {
            $invoice = SupplierInvoice::byCompany($companyId)->findOrFail($invoiceId);
            if ($invoice->status !== SupplierInvoice::STATUS_DRAFT) {
                throw new RuntimeException('Invoice not in draft');
            }
            $invoice->update($data);
            if (! empty($lines)) {
                $this->replaceLines($invoice, $lines);
            }
            $this->recalc($invoice->id);

            return $invoice->fresh('lines');
        });
    }

    public function confirm(int $invoiceId, int $companyId, ?int $userId = null): SupplierInvoice
    {
        return DB::transaction(function () use ($invoiceId, $companyId, $userId) {
            $invoice = SupplierInvoice::byCompany($companyId)->findOrFail($invoiceId);
            if ($invoice->status !== SupplierInvoice::STATUS_DRAFT) {
                throw new RuntimeException('Already confirmed');
            }
            $this->recalc($invoice->id);
            $invoice->refresh();
            $invoice->status = SupplierInvoice::STATUS_CONFIRMED;
            $invoice->confirmed_at = now();
            $invoice->confirmed_by = $userId;
            $invoice->save();

            // Автоматически создаём долг (мы должны поставщику)
            if ($invoice->amount_total > 0) {
                $this->createDebtFromInvoice($invoice, $userId);
            }

            return $invoice;
        });
    }

    /**
     * Создать долг из подтверждённого счёта поставщика
     */
    protected function createDebtFromInvoice(SupplierInvoice $invoice, ?int $userId = null): FinanceDebt
    {
        // Проверяем что долг ещё не создавался
        $existing = FinanceDebt::where('source_type', SupplierInvoice::class)
            ->where('source_id', $invoice->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $supplier = $invoice->supplier_id ? Supplier::find($invoice->supplier_id) : null;

        $debt = FinanceDebt::create([
            'company_id' => $invoice->company_id,
            'type' => FinanceDebt::TYPE_PAYABLE,
            'purpose' => FinanceDebt::PURPOSE_DEBT,
            'counterparty_id' => $invoice->supplier_id,
            'description' => 'Счёт поставщика '.($invoice->invoice_no ?? '#'.$invoice->id),
            'reference' => $invoice->invoice_no,
            'original_amount' => $invoice->amount_total,
            'amount_paid' => 0,
            'amount_outstanding' => $invoice->amount_total,
            'currency_code' => $invoice->currency_code ?? 'UZS',
            'debt_date' => $invoice->issue_date ?? now(),
            'due_date' => $invoice->due_date,
            'status' => FinanceDebt::STATUS_ACTIVE,
            'source_type' => SupplierInvoice::class,
            'source_id' => $invoice->id,
            'created_by' => $userId,
        ]);

        Log::info('Debt auto-created from supplier invoice', [
            'debt_id' => $debt->id,
            'invoice_id' => $invoice->id,
            'supplier_id' => $invoice->supplier_id,
            'amount' => $invoice->amount_total,
        ]);

        return $debt;
    }

    public function replaceLines(SupplierInvoice $invoice, array $lines): void
    {
        $invoice->lines()->delete();
        foreach ($lines as $line) {
            SupplierInvoiceLine::create([
                'invoice_id' => $invoice->id,
                'sku_id' => $line['sku_id'] ?? null,
                'description' => $line['description'] ?? null,
                'qty' => $line['qty'] ?? null,
                'unit_cost' => $line['unit_cost'] ?? null,
                'amount_line' => $line['amount_line'] ?? (($line['qty'] ?? 0) * ($line['unit_cost'] ?? 0)),
                'meta_json' => $line['meta_json'] ?? null,
            ]);
        }
    }

    public function recalc(int $invoiceId): SupplierInvoice
    {
        return DB::transaction(function () use ($invoiceId) {
            $invoice = SupplierInvoice::with('lines')->findOrFail($invoiceId);
            // Если есть линии — пересчитываем суммы, иначе оставляем вручную введенные totals
            if ($invoice->lines->count() > 0) {
                $subtotal = $invoice->lines->sum('amount_line');
                $invoice->amount_subtotal = $subtotal;
                $invoice->amount_total = $subtotal + (float) $invoice->amount_tax;
                $invoice->save();
            }
            $this->refreshPaidAndOutstanding($invoice->id);

            return $invoice;
        });
    }

    public function refreshPaidAndOutstanding(int $invoiceId): SupplierInvoice
    {
        return DB::transaction(function () use ($invoiceId) {
            $invoice = SupplierInvoice::findOrFail($invoiceId);
            $paid = DB::table('supplier_payment_allocations')
                ->where('invoice_id', $invoice->id)
                ->sum('amount_allocated');
            $invoice->amount_paid = (float) $paid;
            $invoice->amount_outstanding = (float) $invoice->amount_total - $invoice->amount_paid;
            if ($invoice->amount_outstanding <= 0) {
                $invoice->status = SupplierInvoice::STATUS_PAID;
                $invoice->amount_outstanding = 0;
            } elseif ($invoice->amount_paid > 0) {
                $invoice->status = SupplierInvoice::STATUS_PARTIALLY_PAID;
            } else {
                if ($invoice->status === SupplierInvoice::STATUS_PAID) {
                    $invoice->status = SupplierInvoice::STATUS_CONFIRMED;
                }
            }
            $invoice->save();

            return $invoice;
        });
    }
}
