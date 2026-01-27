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
        Schema::create('marketplace_expense_cache', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('marketplace_account_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace', 20)->index(); // wb, ozon, uzum, yandex
            $table->string('period_type', 20)->default('30days'); // 7days, 30days, 90days, custom
            $table->date('period_from');
            $table->date('period_to');

            // Expense breakdown
            $table->decimal('commission', 15, 2)->default(0);
            $table->decimal('logistics', 15, 2)->default(0);
            $table->decimal('storage', 15, 2)->default(0);
            $table->decimal('advertising', 15, 2)->default(0);
            $table->decimal('penalties', 15, 2)->default(0);
            $table->decimal('returns', 15, 2)->default(0);
            $table->decimal('other', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);

            // Revenue info
            $table->decimal('gross_revenue', 15, 2)->default(0);
            $table->unsignedInteger('orders_count')->default(0);
            $table->unsignedInteger('returns_count')->default(0);

            // Currency info
            $table->string('currency', 10)->default('UZS');
            $table->decimal('total_uzs', 15, 2)->default(0); // Total in UZS

            // Sync metadata
            $table->timestamp('synced_at')->nullable();
            $table->string('sync_status', 20)->default('pending'); // pending, success, error
            $table->text('sync_error')->nullable();

            $table->timestamps();

            // Unique constraint for period per account
            $table->unique(['marketplace_account_id', 'period_type'], 'mec_account_period_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_expense_cache');
    }
};
