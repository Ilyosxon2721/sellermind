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
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->string('supply_id', 50)->nullable()->after('status')->comment('ID поставки WB (WB-GI-1234567)');
            $table->string('wb_supplier_status', 50)->nullable()->after('supply_id')->comment('supplierStatus от WB');
            $table->string('wb_status', 50)->nullable()->after('wb_supplier_status')->comment('wbStatus от WB');
            $table->timestamp('delivered_at')->nullable()->after('wb_status')->comment('Дата доставки клиенту');

            $table->index('supply_id');
            $table->index('wb_supplier_status');
            $table->index('wb_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropIndex(['supply_id']);
            $table->dropIndex(['wb_supplier_status']);
            $table->dropIndex(['wb_status']);

            $table->dropColumn(['supply_id', 'wb_supplier_status', 'wb_status', 'delivered_at']);
        });
    }
};
