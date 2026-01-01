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
        Schema::create('yandex_market_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->string('order_id')->index();
            $table->string('status')->nullable()->index();
            $table->string('substatus')->nullable();
            $table->string('payment_type')->nullable();
            $table->string('payment_method')->nullable();
            $table->json('order_data')->nullable();
            $table->decimal('total_price', 15, 2)->nullable();
            $table->string('currency', 10)->default('RUB');
            $table->string('buyer_type')->nullable();
            $table->string('delivery_region')->nullable();
            $table->string('delivery_type')->nullable();
            $table->timestamp('created_at_ym')->nullable();
            $table->timestamp('updated_at_ym')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('yandex_market_orders');
    }
};
