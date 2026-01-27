<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Денежные счета (касса, банк, электронные кошельки)
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('name'); // "Основная касса", "Расчётный счёт Капитал"
            $table->enum('type', ['cash', 'bank', 'card', 'ewallet'])->default('cash');
            $table->string('currency_code', 8)->default('UZS');
            $table->decimal('balance', 18, 2)->default(0); // Текущий остаток
            $table->decimal('initial_balance', 18, 2)->default(0); // Начальный остаток
            $table->string('bank_name')->nullable(); // Для банковских счетов
            $table->string('account_number')->nullable(); // Номер счёта
            $table->string('card_number')->nullable(); // Для карт (последние 4 цифры)
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'type']);
        });

        // Движения денежных средств
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->onDelete('cascade');
            $table->enum('type', ['income', 'expense', 'transfer_in', 'transfer_out']);
            $table->decimal('amount', 18, 2);
            $table->decimal('balance_after', 18, 2); // Остаток после операции
            $table->string('currency_code', 8)->default('UZS');
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->foreignId('counterparty_id')->nullable()->constrained('counterparties')->nullOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('employees')->nullOnDelete();

            // Связь с финансовой транзакцией (если создана оттуда)
            $table->foreignId('finance_transaction_id')->nullable()->constrained('finance_transactions')->nullOnDelete();

            // Для переводов между счетами
            $table->foreignId('transfer_to_account_id')->nullable()->constrained('cash_accounts')->nullOnDelete();
            $table->foreignId('transfer_from_transaction_id')->nullable(); // ID парной транзакции

            $table->string('description')->nullable();
            $table->string('reference')->nullable(); // Номер чека, платёжки
            $table->date('transaction_date');
            $table->enum('status', ['draft', 'confirmed', 'cancelled'])->default('confirmed');
            $table->json('meta_json')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['company_id', 'transaction_date']);
            $table->index(['cash_account_id', 'transaction_date']);
            $table->index(['company_id', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
        Schema::dropIfExists('cash_accounts');
    }
};
