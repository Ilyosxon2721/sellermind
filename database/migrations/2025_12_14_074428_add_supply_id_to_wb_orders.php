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
        if (Schema::hasTable('wb_orders')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('wb_orders', 'supply_id')) {
                    $table->string('supply_id')->nullable()->after('warehouse_id')->index();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wb_orders') && Schema::hasColumn('wb_orders', 'supply_id')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                $table->dropColumn('supply_id');
            });
        }
    }
};
