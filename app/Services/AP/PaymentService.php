<?php

namespace App\Services\AP;

use App\Models\AP\ApSetting;
use App\Models\AP\SupplierInvoice;
use App\Models\AP\SupplierPayment;
use App\Models\AP\SupplierPaymentAllocation;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class PaymentService
{
    public function create(array $data): SupplierPayment
    {
        return SupplierPayment::create($data);
    }

    public function updateDraft(int $paymentId, int $companyId, array $data): SupplierPayment
    {
        $payment = SupplierPayment::byCompany($companyId)->findOrFail($paymentId);
        if ($payment->status !== SupplierPayment::STATUS_DRAFT) {
            throw new RuntimeException('Payment not in draft');
        }
        $payment->update($data);
        return $payment;
    }

    public function setAllocations(int $paymentId, int $companyId, array $allocations): SupplierPayment
    {
        return DB::transaction(function () use ($paymentId, $companyId, $allocations) {
            $payment = SupplierPayment::byCompany($companyId)->findOrFail($paymentId);
            if ($payment->status !== SupplierPayment::STATUS_DRAFT) {
                throw new RuntimeException('Payment not in draft');
            }
            $payment->allocations()->delete();
            foreach ($allocations as $alloc) {
                SupplierPaymentAllocation::create([
                    'payment_id' => $payment->id,
                    'invoice_id' => $alloc['invoice_id'],
                    'amount_allocated' => $alloc['amount'],
                ]);
            }
            return $payment->fresh('allocations');
        });
    }

    public function post(int $paymentId, int $companyId, ?int $userId = null): SupplierPayment
    {
        return DB::transaction(function () use ($paymentId, $companyId, $userId) {
            $payment = SupplierPayment::byCompany($companyId)->with('allocations')->findOrFail($paymentId);
            if ($payment->status !== SupplierPayment::STATUS_DRAFT) {
                throw new RuntimeException('Payment not in draft');
            }

            $totalAlloc = $payment->allocations->sum('amount_allocated');
            if (round($totalAlloc, 2) !== round($payment->amount_total, 2)) {
                throw new RuntimeException('Allocations must sum to payment total');
            }

            // Check invoices and overpayment
            $allowOver = ApSetting::where('company_id', $companyId)->value('allow_overpayment') ?? false;
            foreach ($payment->allocations as $alloc) {
                $invoice = SupplierInvoice::byCompany($companyId)->findOrFail($alloc->invoice_id);
                if ($invoice->supplier_id !== $payment->supplier_id) {
                    throw new RuntimeException('Invoice supplier mismatch');
                }
                if (!$allowOver && ($alloc->amount_allocated > $invoice->amount_outstanding)) {
                    throw new RuntimeException('Allocation exceeds outstanding');
                }
            }

            $payment->status = SupplierPayment::STATUS_POSTED;
            $payment->posted_at = now();
            $payment->posted_by = $userId;
            $payment->save();

            // Refresh invoices
            $invoiceService = app(InvoiceService::class);
            foreach ($payment->allocations as $alloc) {
                $invoiceService->refreshPaidAndOutstanding($alloc->invoice_id);
            }

            return $payment->fresh('allocations');
        });
    }

    public function reverse(int $paymentId, int $companyId, ?int $userId = null): SupplierPayment
    {
        return DB::transaction(function () use ($paymentId, $companyId, $userId) {
            $original = SupplierPayment::byCompany($companyId)->with('allocations')->findOrFail($paymentId);
            if ($original->status !== SupplierPayment::STATUS_POSTED) {
                throw new RuntimeException('Only posted payments can be reversed');
            }

            $reversal = SupplierPayment::create([
                'company_id' => $original->company_id,
                'supplier_id' => $original->supplier_id,
                'payment_no' => $original->payment_no . '-REV',
                'status' => SupplierPayment::STATUS_DRAFT,
                'paid_at' => now(),
                'currency_code' => $original->currency_code,
                'exchange_rate' => $original->exchange_rate,
                'amount_total' => -1 * $original->amount_total,
                'method' => $original->method,
                'reference' => $original->reference,
                'comment' => 'Reversal of ' . $original->payment_no,
            ]);

            $allocations = $original->allocations->map(function ($alloc) {
                return [
                    'invoice_id' => $alloc->invoice_id,
                    'amount' => -1 * $alloc->amount_allocated,
                ];
            })->toArray();

            $this->setAllocations($reversal->id, $companyId, $allocations);
            $this->post($reversal->id, $companyId, $userId);

            $original->status = SupplierPayment::STATUS_CANCELLED;
            $original->save();

            return $reversal;
        });
    }
}
