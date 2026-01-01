<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wb_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->string('external_order_id')->index();
            $table->string('status')->nullable();
            $table->string('status_normalized')->nullable();
            $table->string('wb_status')->nullable();
            $table->string('wb_status_group')->nullable();
            $table->string('wb_supplier_status')->nullable();
            $table->string('wb_delivery_type')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('currency', 8)->nullable();
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'external_order_id'], 'wb_orders_acc_ext_unique');
            $table->index(['marketplace_account_id', 'status'], 'wb_orders_acc_status_idx');
            $table->index(['marketplace_account_id', 'ordered_at'], 'wb_orders_acc_ordered_idx');
        });

        Schema::create('wb_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wb_order_id')->constrained('wb_orders')->cascadeOnDelete();
            $table->string('external_offer_id')->nullable()->index();
            $table->string('name')->nullable();
            $table->integer('quantity')->default(1);
            $table->decimal('price', 14, 2)->nullable();
            $table->decimal('total_price', 14, 2)->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wb_order_items');
        Schema::dropIfExists('wb_orders');
    }
};
