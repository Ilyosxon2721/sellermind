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
        Schema::table('ozon_orders', function (Blueprint $table) {
            $table->json('products')->nullable()->after('order_data');
            $table->string('customer_name')->nullable()->after('products');
            $table->string('customer_phone')->nullable()->after('customer_name');
            $table->text('delivery_address')->nullable()->after('customer_phone');
            $table->string('tracking_number')->nullable()->after('delivery_address');
            $table->text('cancellation_reason')->nullable()->after('tracking_number');
            $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ozon_orders', function (Blueprint $table) {
            $table->dropColumn([
                'products',
                'customer_name',
                'customer_phone',
                'delivery_address',
                'tracking_number',
                'cancellation_reason',
                'cancelled_at',
            ]);
        });
    }
};
