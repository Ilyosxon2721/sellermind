<?php
// file: database/migrations/2025_11_28_300001_add_wildberries_fields_to_marketplace_accounts.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Add Wildberries-specific token fields to marketplace_accounts table.
     * WB uses different tokens for different API categories.
     */
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            // WB category-specific tokens (encrypted via model accessors)
            $table->text('wb_content_token')->nullable()->after('credentials_json');
            $table->text('wb_marketplace_token')->nullable()->after('wb_content_token');
            $table->text('wb_prices_token')->nullable()->after('wb_marketplace_token');
            $table->text('wb_statistics_token')->nullable()->after('wb_prices_token');

            // Token validation status
            $table->boolean('wb_tokens_valid')->default(true)->after('wb_statistics_token');

            // Last successful API call timestamp for health monitoring
            $table->timestamp('wb_last_successful_call')->nullable()->after('wb_tokens_valid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropColumn([
                'wb_content_token',
                'wb_marketplace_token',
                'wb_prices_token',
                'wb_statistics_token',
                'wb_tokens_valid',
                'wb_last_successful_call',
            ]);
        });
    }
};
