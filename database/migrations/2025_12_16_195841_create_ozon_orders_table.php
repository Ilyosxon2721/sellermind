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
        Schema::create('ozon_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->index();
            $table->string('posting_number')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('substatus')->nullable();
            $table->json('order_data')->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->string('currency', 10)->default('RUB');
            $table->string('delivery_method')->nullable();
            $table->string('warehouse_id')->nullable();
            $table->timestamp('in_process_at')->nullable();
            $table->timestamp('shipment_date')->nullable();
            $table->timestamp('created_at_ozon')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ozon_orders');
    }
};
