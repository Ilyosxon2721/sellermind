<?php

// file: database/migrations/2025_12_01_300009_add_supply_fields_to_wildberries_orders.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add supply-related fields to wildberries_orders table.
     */
    public function up(): void
    {
        Schema::table('wildberries_orders', function (Blueprint $table) {
            // Supply reference
            $table->string('supply_id', 100)->nullable()->after('income_id');

            // Order metadata (for marked products)
            $table->string('sgtin', 100)->nullable()->after('supply_id');  // Marking code for alcohol, tobacco
            $table->string('uin', 100)->nullable()->after('sgtin');        // UIN for furs, jewelry
            $table->string('imei', 100)->nullable()->after('uin');         // IMEI for electronics
            $table->string('gtin', 100)->nullable()->after('imei');        // GTIN code
            $table->date('expiration_date')->nullable()->after('gtin');    // Expiration date for food, cosmetics

            // Additional indexes
            $table->index(['marketplace_account_id', 'supply_id'], 'wb_ord_acc_supply_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wildberries_orders', function (Blueprint $table) {
            $table->dropIndex('wb_ord_acc_supply_idx');
            $table->dropColumn(['supply_id', 'sgtin', 'uin', 'imei', 'gtin', 'expiration_date']);
        });
    }
};
