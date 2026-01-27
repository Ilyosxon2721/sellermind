<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('yandex_market_orders', function (Blueprint $table) {
            // Stock tracking fields (same as ozon_orders)
            $table->string('stock_status', 20)->default('none')->after('status_normalized')
                ->comment('none=not processed, reserved=stock reserved, sold=sale completed, released=cancelled, returned=returned');
            $table->timestamp('stock_reserved_at')->nullable()->after('stock_status');
            $table->timestamp('stock_sold_at')->nullable()->after('stock_reserved_at')
                ->comment('When set, this order counts as completed sale (revenue)');
            $table->timestamp('stock_released_at')->nullable()->after('stock_sold_at');

            // Add index for faster queries on stock_status
            $table->index(['stock_status', 'stock_sold_at']);
        });
    }

    public function down(): void
    {
        Schema::table('yandex_market_orders', function (Blueprint $table) {
            $table->dropIndex(['stock_status', 'stock_sold_at']);
            $table->dropColumn(['stock_status', 'stock_reserved_at', 'stock_sold_at', 'stock_released_at']);
        });
    }
};
