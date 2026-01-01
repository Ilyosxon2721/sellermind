<?php

namespace Tests\Feature\AP;

use App\Models\AP\Supplier;
use App\Models\AP\SupplierInvoice;
use App\Models\AP\SupplierPayment;
use App\Models\AP\SupplierPaymentAllocation;
use App\Models\Company;
use App\Models\User;
use App\Models\UserCompanyRole;
use App\Services\AP\InvoiceService;
use App\Services\AP\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ApFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Company $company;
    protected Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->company = Company::create(['name' => 'TestCo']);
        $user = User::create([
            'name' => 'Demo',
            'email' => 'demo@test.com',
            'password' => Hash::make('secret'),
        ]);
        UserCompanyRole::create([
            'user_id' => $user->id,
            'company_id' => $this->company->id,
            'role' => 'owner',
        ]);
        $this->supplier = Supplier::create([
            'company_id' => $this->company->id,
            'name' => 'Supplier A',
        ]);
    }

    public function test_confirm_invoice_and_post_payment(): void
    {
        $invoice = SupplierInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'INV-1',
            'amount_total' => 300,
            'amount_subtotal' => 300,
            'amount_outstanding' => 300,
        ]);

        app(InvoiceService::class)->confirm($invoice->id, $this->company->id, null);
        $invoice->refresh();
        $this->assertEquals(SupplierInvoice::STATUS_CONFIRMED, $invoice->status);

        $payment = SupplierPayment::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'payment_no' => 'PAY-1',
            'status' => SupplierPayment::STATUS_DRAFT,
            'paid_at' => now(),
            'amount_total' => 300,
            'currency_code' => 'USD',
            'method' => 'BANK',
        ]);

        app(PaymentService::class)->setAllocations($payment->id, $this->company->id, [
            ['invoice_id' => $invoice->id, 'amount' => 300],
        ]);
        app(PaymentService::class)->post($payment->id, $this->company->id, null);

        $invoice->refresh();
        $this->assertEquals(0, $invoice->amount_outstanding);
        $this->assertEquals(SupplierInvoice::STATUS_PAID, $invoice->status);
    }

    public function test_overpayment_blocked(): void
    {
        $invoice = SupplierInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'INV-2',
            'amount_total' => 100,
            'amount_outstanding' => 100,
        ]);

        $payment = SupplierPayment::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'payment_no' => 'PAY-2',
            'status' => SupplierPayment::STATUS_DRAFT,
            'paid_at' => now(),
            'amount_total' => 200,
            'currency_code' => 'USD',
            'method' => 'BANK',
        ]);

        $this->expectException(\RuntimeException::class);
        app(PaymentService::class)->setAllocations($payment->id, $this->company->id, [
            ['invoice_id' => $invoice->id, 'amount' => 200],
        ]);
        app(PaymentService::class)->post($payment->id, $this->company->id, null);
    }

    public function test_reverse_payment_restores_outstanding(): void
    {
        $invoice = SupplierInvoice::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'invoice_no' => 'INV-3',
            'amount_total' => 150,
            'amount_outstanding' => 150,
        ]);

        $payment = SupplierPayment::create([
            'company_id' => $this->company->id,
            'supplier_id' => $this->supplier->id,
            'payment_no' => 'PAY-3',
            'status' => SupplierPayment::STATUS_DRAFT,
            'paid_at' => now(),
            'amount_total' => 150,
            'currency_code' => 'USD',
            'method' => 'BANK',
        ]);

        app(PaymentService::class)->setAllocations($payment->id, $this->company->id, [
            ['invoice_id' => $invoice->id, 'amount' => 150],
        ]);
        app(PaymentService::class)->post($payment->id, $this->company->id, null);
        app(PaymentService::class)->reverse($payment->id, $this->company->id, null);

        $invoice->refresh();
        $this->assertEquals(150, $invoice->amount_outstanding);
    }
}
