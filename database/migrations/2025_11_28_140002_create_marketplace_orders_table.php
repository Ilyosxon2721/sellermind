<?php
// file: database/migrations/2025_11_28_140002_create_marketplace_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();

            $table->string('external_order_id')->index();     // ID заказа на МП
            $table->string('status', 50)->nullable();         // статус заказа на МП
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();

            $table->decimal('total_amount', 15, 2)->nullable();
            $table->string('currency', 10)->nullable();

            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('updated_at_mp')->nullable();   // время последнего обновления на МП

            $table->json('raw_payload')->nullable();         // исходный JSON заказа

            $table->timestamps();

            $table->unique(['marketplace_account_id', 'external_order_id'], 'mp_acc_order_unique');
            $table->index(['marketplace_account_id', 'status'], 'mp_orders_status_idx');
            $table->index(['marketplace_account_id', 'ordered_at'], 'mp_orders_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_orders');
    }
};
