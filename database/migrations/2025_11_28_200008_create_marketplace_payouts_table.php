<?php
// file: database/migrations/2025_11_28_200008_create_marketplace_payouts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_payouts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')
                ->constrained('marketplace_accounts')
                ->cascadeOnDelete();

            $table->string('external_payout_id', 100)->nullable(); // ID выплаты на МП

            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();

            $table->decimal('amount', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();

            // агрегированные суммы по типам операций
            $table->decimal('sales_amount', 15, 2)->nullable();
            $table->decimal('returns_amount', 15, 2)->nullable();
            $table->decimal('commission_amount', 15, 2)->nullable();
            $table->decimal('logistics_amount', 15, 2)->nullable();
            $table->decimal('storage_amount', 15, 2)->nullable();
            $table->decimal('ads_amount', 15, 2)->nullable();
            $table->decimal('penalties_amount', 15, 2)->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['marketplace_account_id', 'period_from', 'period_to'], 'mp_payouts_account_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_payouts');
    }
};
