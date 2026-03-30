<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создать таблицу кассовых смен для POS-терминала
     */
    public function up(): void
    {
        Schema::create('cash_shifts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('cash_account_id')->nullable()->constrained('cash_accounts')->onDelete('set null');
            $table->foreignId('warehouse_id')->nullable()->constrained('warehouses')->onDelete('set null');
            $table->foreignId('opened_by')->constrained('users')->onDelete('cascade');
            $table->foreignId('closed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->enum('status', ['open', 'closed'])->default('open');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('closing_balance', 15, 2)->nullable();

            // Итоги смены (обновляются при каждой продаже)
            $table->integer('total_sales_count')->default(0);
            $table->decimal('total_sales_amount', 15, 2)->default(0);
            $table->decimal('total_cash_received', 15, 2)->default(0);
            $table->decimal('total_card_received', 15, 2)->default(0);
            $table->decimal('total_transfer_received', 15, 2)->default(0);
            $table->decimal('total_refunds', 15, 2)->default(0);
            $table->decimal('total_cash_in', 15, 2)->default(0);
            $table->decimal('total_cash_out', 15, 2)->default(0);

            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();
            $table->text('close_notes')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'opened_at']);
        });
    }

    /**
     * Откатить миграцию
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_shifts');
    }
};
