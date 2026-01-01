<?php
// file: database/migrations/2025_12_07_000006_add_stock_strategies_to_marketplace_accounts.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->string('stock_sync_strategy', 30)->default('wb_priority'); // wb_priority|local_priority|last_write
            $table->string('stock_size_strategy', 30)->default('by_total');    // by_total|by_link
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropColumn(['stock_sync_strategy', 'stock_size_strategy']);
        });
    }
};
