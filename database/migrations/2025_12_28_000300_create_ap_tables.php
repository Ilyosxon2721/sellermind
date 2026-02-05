<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Suppliers (минимальный, если отсутствует)
        if (! Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('currency_code')->nullable();
                $table->timestamps();
                $table->index('company_id');
                $table->unique(['company_id', 'name']);
            });
        }

        // AP settings (или отдельная таблица)
        if (! Schema::hasTable('ap_settings')) {
            Schema::create('ap_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('base_currency_code', 8)->default('USD');
                $table->boolean('allow_overpayment')->default(false);
                $table->timestamps();
                $table->unique('company_id');
            });
        }

        if (! Schema::hasTable('supplier_invoices')) {
            Schema::create('supplier_invoices', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('supplier_id');
                $table->string('invoice_no');
                $table->string('status', 24)->default('DRAFT');
                $table->date('issue_date')->nullable();
                $table->date('due_date')->nullable();
                $table->string('currency_code', 8)->default('USD');
                $table->decimal('exchange_rate', 18, 6)->nullable();
                $table->decimal('amount_subtotal', 18, 2)->default(0);
                $table->decimal('amount_tax', 18, 2)->default(0);
                $table->decimal('amount_total', 18, 2)->default(0);
                $table->decimal('amount_paid', 18, 2)->default(0);
                $table->decimal('amount_outstanding', 18, 2)->default(0);
                $table->string('related_type', 16)->nullable();
                $table->unsignedBigInteger('related_id')->nullable();
                $table->text('notes')->nullable();
                $table->dateTime('confirmed_at')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('confirmed_by')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'supplier_id', 'invoice_no'], 'supplier_invoice_unique');
                $table->index(['company_id', 'status']);
                $table->index('supplier_id');
                $table->index('due_date');
            });
        }

        if (! Schema::hasTable('supplier_invoice_lines')) {
            Schema::create('supplier_invoice_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('invoice_id');
                $table->unsignedBigInteger('sku_id')->nullable();
                $table->string('description')->nullable();
                $table->decimal('qty', 18, 3)->nullable();
                $table->decimal('unit_cost', 18, 2)->nullable();
                $table->decimal('amount_line', 18, 2)->default(0);
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->index('invoice_id');
                $table->index('sku_id');
            });
        }

        if (! Schema::hasTable('supplier_payments')) {
            Schema::create('supplier_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('supplier_id');
                $table->string('payment_no');
                $table->string('status', 24)->default('DRAFT');
                $table->dateTime('paid_at');
                $table->string('currency_code', 8)->default('USD');
                $table->decimal('exchange_rate', 18, 6)->nullable();
                $table->decimal('amount_total', 18, 2)->default(0);
                $table->string('method', 16)->default('BANK');
                $table->string('reference')->nullable();
                $table->text('comment')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('posted_by')->nullable();
                $table->dateTime('posted_at')->nullable();
                $table->timestamps();
                $table->unique(['company_id', 'payment_no'], 'supplier_payment_unique');
                $table->index('supplier_id');
                $table->index('paid_at');
                $table->index('status');
            });
        }

        if (! Schema::hasTable('supplier_payment_allocations')) {
            Schema::create('supplier_payment_allocations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('payment_id');
                $table->unsignedBigInteger('invoice_id');
                $table->decimal('amount_allocated', 18, 2);
                $table->timestamps();
                $table->unique(['payment_id', 'invoice_id'], 'payment_allocation_unique');
                $table->index('invoice_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payment_allocations');
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoice_lines');
        Schema::dropIfExists('supplier_invoices');
        Schema::dropIfExists('ap_settings');
        // Suppliers и другие ядровые таблицы не трогаем при откате
    }
};
