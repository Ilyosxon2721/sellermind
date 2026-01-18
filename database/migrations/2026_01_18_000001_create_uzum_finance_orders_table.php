<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uzum_finance_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->cascadeOnDelete();

            // Identifiers from Uzum Finance API
            $table->unsignedBigInteger('uzum_id')->comment('id from API');
            $table->unsignedBigInteger('order_id')->comment('orderId from API');
            $table->unsignedBigInteger('shop_id');
            $table->unsignedBigInteger('product_id')->nullable();

            // Product info
            $table->string('sku_title')->nullable();
            $table->string('product_image_url')->nullable();

            // Status
            $table->string('status', 50)->comment('PROCESSING, CANCELED, COMPLETED, etc.');
            $table->string('status_normalized', 50)->nullable()->comment('Internal normalized status');

            // Financial data (all in tiyin, divide by 100 for UZS)
            $table->bigInteger('sell_price')->default(0)->comment('Price per unit in tiyin');
            $table->bigInteger('purchase_price')->nullable()->comment('Cost price in tiyin');
            $table->bigInteger('commission')->default(0)->comment('Uzum commission in tiyin');
            $table->bigInteger('seller_profit')->default(0)->comment('Seller profit in tiyin');
            $table->bigInteger('logistic_delivery_fee')->default(0)->comment('Delivery fee in tiyin');
            $table->bigInteger('withdrawn_profit')->default(0)->comment('Already withdrawn profit');

            // Quantities
            $table->integer('amount')->default(0)->comment('Quantity sold');
            $table->integer('amount_returns')->default(0)->comment('Quantity returned');

            // Dates
            $table->timestamp('order_date')->nullable()->comment('Order creation date');
            $table->timestamp('date_issued')->nullable()->comment('Delivery/issue date');

            // Cancel/return info
            $table->string('comment')->nullable()->comment('Cancel comment');
            $table->string('return_cause')->nullable()->comment('Return reason');

            // Raw data
            $table->json('raw_data')->nullable();

            $table->timestamps();

            // Indexes
            $table->unique(['marketplace_account_id', 'uzum_id'], 'uzum_finance_account_uzum_id_unique');
            $table->index(['marketplace_account_id', 'status']);
            $table->index(['marketplace_account_id', 'order_date']);
            $table->index(['marketplace_account_id', 'order_id']);
            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uzum_finance_orders');
    }
};
