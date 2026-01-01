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
            // Composite index for main queries (account + date sorting)
            $table->index(['marketplace_account_id', 'ordered_at'], 'idx_orders_account_date');

            // Index for WB status filtering
            $table->index('wb_status_group', 'idx_orders_wb_status_group');

            // Index for status filtering
            $table->index('status', 'idx_orders_status');

            // Index for date range queries
            $table->index('ordered_at', 'idx_orders_date');

            // Index for supply grouping
            $table->index('supply_id', 'idx_orders_supply');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropIndex('idx_orders_account_date');
            $table->dropIndex('idx_orders_wb_status_group');
            $table->dropIndex('idx_orders_status');
            $table->dropIndex('idx_orders_date');
            $table->dropIndex('idx_orders_supply');
        });
    }
};
