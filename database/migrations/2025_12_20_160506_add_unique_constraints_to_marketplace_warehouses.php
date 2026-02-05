<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop old unique constraint if exists
        try {
            DB::statement('ALTER TABLE marketplace_warehouses DROP INDEX mp_wh_wb_unique');
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }

        // Check if unique_mp_wh_per_account already exists
        $indexExists = DB::select("SHOW INDEX FROM marketplace_warehouses WHERE Key_name = 'unique_mp_wh_per_account'");

        if (empty($indexExists)) {
            Schema::table('marketplace_warehouses', function (Blueprint $table) {
                // Unique: одна пара marketplace_warehouse_id + account
                $table->unique(['marketplace_account_id', 'marketplace_warehouse_id'], 'unique_mp_wh_per_account');

                // Note: local_warehouse_id uniqueness (excluding NULLs) enforced at application level
                // MySQL doesn't support partial unique indexes
                // Validation: one local_warehouse_id per account in MarketplaceWarehouse model
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_warehouses', function (Blueprint $table) {
            $table->dropUnique('unique_mp_wh_per_account');
        });
    }
};
