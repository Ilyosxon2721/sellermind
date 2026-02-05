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
        Schema::table('yandex_market_orders', function (Blueprint $table) {
            if (! Schema::hasColumn('yandex_market_orders', 'status_normalized')) {
                $table->string('status_normalized')->nullable()->after('status');
            }
            if (! Schema::hasColumn('yandex_market_orders', 'customer_name')) {
                $table->string('customer_name')->nullable()->after('buyer_type');
            }
            if (! Schema::hasColumn('yandex_market_orders', 'customer_phone')) {
                $table->string('customer_phone')->nullable()->after('customer_name');
            }
            if (! Schema::hasColumn('yandex_market_orders', 'delivery_service')) {
                $table->string('delivery_service')->nullable()->after('delivery_type');
            }
            if (! Schema::hasColumn('yandex_market_orders', 'items_count')) {
                $table->integer('items_count')->default(0)->after('delivery_service');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('yandex_market_orders', function (Blueprint $table) {
            $table->dropColumn([
                'status_normalized',
                'customer_name',
                'customer_phone',
                'delivery_service',
                'items_count',
            ]);
        });
    }
};
