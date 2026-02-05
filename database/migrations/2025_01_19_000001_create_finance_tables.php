<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Finance Categories - Категории расходов/доходов
        if (! Schema::hasTable('finance_categories')) {
            Schema::create('finance_categories', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id')->nullable(); // NULL = системные категории
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->enum('type', ['income', 'expense', 'both'])->default('expense');
                $table->string('code', 50)->nullable();
                $table->string('name');
                $table->boolean('is_system')->default(false);
                $table->boolean('is_active')->default(true);
                $table->string('tax_category', 50)->nullable(); // для налоговых отчётов
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index('company_id');
                $table->index('parent_id');
                $table->index(['company_id', 'type']);
                $table->index('code');
            });
        }

        // 2. Finance Settings - Настройки компании
        if (! Schema::hasTable('finance_settings')) {
            Schema::create('finance_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->string('base_currency_code', 8)->default('UZS');
                $table->enum('tax_system', ['simplified', 'general', 'both'])->default('simplified');
                $table->decimal('vat_rate', 5, 2)->default(12.00); // НДС в Узбекистане
                $table->decimal('income_tax_rate', 5, 2)->default(15.00); // Упрощёнка
                $table->decimal('social_tax_rate', 5, 2)->default(12.00);
                $table->boolean('auto_import_marketplace_fees')->default(true);
                $table->timestamps();

                $table->unique('company_id');
            });
        }

        // 3. Employees - Сотрудники
        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('user_id')->nullable(); // Связь с users
                $table->string('first_name');
                $table->string('last_name');
                $table->string('middle_name')->nullable();
                $table->string('phone', 32)->nullable();
                $table->string('email')->nullable();
                $table->string('position')->nullable();
                $table->string('department')->nullable();
                $table->date('hire_date')->nullable();
                $table->date('termination_date')->nullable();
                $table->enum('salary_type', ['fixed', 'hourly', 'commission'])->default('fixed');
                $table->decimal('base_salary', 18, 2)->default(0);
                $table->string('currency_code', 8)->default('UZS');
                $table->string('bank_name')->nullable();
                $table->string('bank_account')->nullable();
                $table->string('inn', 20)->nullable(); // ИНН
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->index('company_id');
                $table->index('user_id');
                $table->index(['company_id', 'is_active']);
            });
        }

        // 4. Finance Transactions - Главный журнал операций
        if (! Schema::hasTable('finance_transactions')) {
            Schema::create('finance_transactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->enum('type', ['income', 'expense']); // приход/расход
                $table->unsignedBigInteger('category_id')->nullable();
                $table->unsignedBigInteger('subcategory_id')->nullable();

                // Полиморфная связь с источником
                $table->string('source_type', 64)->nullable(); // supplier_invoice, marketplace_payout, salary, etc.
                $table->unsignedBigInteger('source_id')->nullable();

                // Контрагент или сотрудник
                $table->unsignedBigInteger('counterparty_id')->nullable(); // suppliers
                $table->unsignedBigInteger('employee_id')->nullable();

                // Суммы
                $table->decimal('amount', 18, 2); // в оригинальной валюте
                $table->string('currency_code', 8)->default('UZS');
                $table->decimal('exchange_rate', 18, 6)->default(1);
                $table->decimal('amount_base', 18, 2)->nullable(); // в базовой валюте

                $table->string('description')->nullable();
                $table->date('transaction_date');
                $table->string('reference', 64)->nullable(); // номер документа
                $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('draft');

                $table->json('tags')->nullable();
                $table->json('metadata')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('confirmed_by')->nullable();
                $table->dateTime('confirmed_at')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'type']);
                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'transaction_date']);
                $table->index(['company_id', 'category_id']);
                $table->index(['source_type', 'source_id']);
                $table->index('counterparty_id');
                $table->index('employee_id');
            });
        }

        // 5. Finance Debts - Долги (дебиторка/кредиторка)
        if (! Schema::hasTable('finance_debts')) {
            Schema::create('finance_debts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->enum('type', ['receivable', 'payable']); // дебиторка/кредиторка
                $table->unsignedBigInteger('counterparty_id')->nullable(); // suppliers
                $table->unsignedBigInteger('employee_id')->nullable();

                $table->string('description');
                $table->string('reference', 64)->nullable();

                $table->decimal('original_amount', 18, 2);
                $table->decimal('amount_paid', 18, 2)->default(0);
                $table->decimal('amount_outstanding', 18, 2);
                $table->string('currency_code', 8)->default('UZS');

                $table->date('debt_date');
                $table->date('due_date')->nullable();
                $table->enum('status', ['active', 'partially_paid', 'paid', 'written_off'])->default('active');

                // Полиморфная связь с источником
                $table->string('source_type', 64)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();

                $table->decimal('interest_rate', 5, 2)->nullable();
                $table->text('notes')->nullable();

                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'type']);
                $table->index(['company_id', 'status']);
                $table->index(['company_id', 'due_date']);
                $table->index('counterparty_id');
                $table->index('employee_id');
                $table->index(['source_type', 'source_id']);
            });
        }

        // 6. Finance Debt Payments - Погашения долгов
        if (! Schema::hasTable('finance_debt_payments')) {
            Schema::create('finance_debt_payments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('debt_id');
                $table->decimal('amount', 18, 2);
                $table->date('payment_date');
                $table->string('payment_method', 32)->default('cash'); // cash, bank, card
                $table->string('reference', 64)->nullable();
                $table->unsignedBigInteger('transaction_id')->nullable(); // связь с finance_transactions
                $table->enum('status', ['draft', 'posted', 'cancelled'])->default('draft');
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();

                $table->index('debt_id');
                $table->index(['company_id', 'payment_date']);
                $table->index('transaction_id');
            });
        }

        // 7. Salary Calculations - Расчёты зарплат (по месяцам)
        if (! Schema::hasTable('salary_calculations')) {
            Schema::create('salary_calculations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->smallInteger('period_year');
                $table->tinyInteger('period_month');
                $table->enum('status', ['draft', 'calculated', 'approved', 'paid'])->default('draft');
                $table->decimal('total_gross', 18, 2)->default(0);
                $table->decimal('total_deductions', 18, 2)->default(0);
                $table->decimal('total_taxes', 18, 2)->default(0);
                $table->decimal('total_net', 18, 2)->default(0);
                $table->unsignedBigInteger('approved_by')->nullable();
                $table->dateTime('approved_at')->nullable();
                $table->timestamps();

                $table->unique(['company_id', 'period_year', 'period_month']);
                $table->index(['company_id', 'status']);
            });
        }

        // 8. Salary Items - Позиции зарплаты сотрудников
        if (! Schema::hasTable('salary_items')) {
            Schema::create('salary_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('salary_calculation_id');
                $table->unsignedBigInteger('employee_id');
                $table->decimal('base_amount', 18, 2)->default(0);
                $table->decimal('bonuses', 18, 2)->default(0);
                $table->decimal('overtime', 18, 2)->default(0);
                $table->decimal('gross_amount', 18, 2)->default(0);
                $table->decimal('tax_amount', 18, 2)->default(0); // НДФЛ
                $table->decimal('pension_amount', 18, 2)->default(0); // Пенсионные
                $table->decimal('other_deductions', 18, 2)->default(0);
                $table->decimal('net_amount', 18, 2)->default(0);
                $table->boolean('is_paid')->default(false);
                $table->dateTime('paid_at')->nullable();
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index('salary_calculation_id');
                $table->index('employee_id');
                $table->unique(['salary_calculation_id', 'employee_id']);
            });
        }

        // 9. Tax Calculations - Расчёты налогов
        if (! Schema::hasTable('tax_calculations')) {
            Schema::create('tax_calculations', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->enum('tax_period_type', ['month', 'quarter', 'year']);
                $table->smallInteger('period_year');
                $table->tinyInteger('period_month')->nullable(); // для месяца
                $table->tinyInteger('period_quarter')->nullable(); // для квартала
                $table->string('tax_type', 32); // income_tax, vat, social_tax, simplified
                $table->decimal('taxable_base', 18, 2)->default(0);
                $table->decimal('tax_rate', 5, 2);
                $table->decimal('calculated_amount', 18, 2)->default(0);
                $table->decimal('paid_amount', 18, 2)->default(0);
                $table->enum('status', ['draft', 'calculated', 'paid', 'overdue'])->default('draft');
                $table->date('due_date')->nullable();
                $table->unsignedBigInteger('transaction_id')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['company_id', 'tax_type']);
                $table->index(['company_id', 'period_year', 'period_month']);
                $table->index(['company_id', 'status']);
            });
        }

        // 10. Marketplace Fee Imports - Импорт комиссий с маркетплейсов
        if (! Schema::hasTable('marketplace_fee_imports')) {
            Schema::create('marketplace_fee_imports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('company_id');
                $table->unsignedBigInteger('marketplace_account_id');
                $table->date('period_from');
                $table->date('period_to');
                $table->decimal('total_sales', 18, 2)->default(0);
                $table->decimal('commission_amount', 18, 2)->default(0);
                $table->decimal('logistics_amount', 18, 2)->default(0);
                $table->decimal('storage_amount', 18, 2)->default(0);
                $table->decimal('ads_amount', 18, 2)->default(0);
                $table->decimal('penalties_amount', 18, 2)->default(0);
                $table->decimal('other_fees', 18, 2)->default(0);
                $table->decimal('net_payout', 18, 2)->default(0);

                // Полиморфная связь с источником
                $table->string('source_type', 64)->nullable(); // marketplace_payout, uzum_finance_order
                $table->unsignedBigInteger('source_id')->nullable();

                $table->enum('status', ['draft', 'imported', 'posted'])->default('draft');
                $table->unsignedBigInteger('transaction_id')->nullable(); // связь с finance_transactions
                $table->timestamps();

                $table->index(['company_id', 'marketplace_account_id']);
                $table->index(['company_id', 'period_from', 'period_to']);
                $table->index(['source_type', 'source_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_fee_imports');
        Schema::dropIfExists('tax_calculations');
        Schema::dropIfExists('salary_items');
        Schema::dropIfExists('salary_calculations');
        Schema::dropIfExists('finance_debt_payments');
        Schema::dropIfExists('finance_debts');
        Schema::dropIfExists('finance_transactions');
        Schema::dropIfExists('employees');
        Schema::dropIfExists('finance_settings');
        Schema::dropIfExists('finance_categories');
    }
};
