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
        Schema::table('marketplace_warehouses', function (Blueprint $table) {
            // Add marketplace_warehouse_id for storing warehouse ID from marketplace API
            // For WB: stores ID from /api/v3/warehouses (FBS/DBS/EDBS/C&C warehouses)
            // Different from wildberries_warehouse_id which is from Statistics API
            $table->unsignedBigInteger('marketplace_warehouse_id')->nullable()->after('wildberries_warehouse_id');
            
            // Add index
            $table->index(['marketplace_account_id', 'marketplace_warehouse_id'], 'mp_wh_mp_wh_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_warehouses', function (Blueprint $table) {
            $table->dropIndex('mp_wh_mp_wh_id_idx');
            $table->dropColumn('marketplace_warehouse_id');
        });
    }
};
