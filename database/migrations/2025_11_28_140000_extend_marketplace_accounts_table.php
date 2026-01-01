<?php
// file: database/migrations/2025_11_28_140000_extend_marketplace_accounts_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            // Add user_id for individual account ownership
            $table->foreignId('user_id')->nullable()->after('id')->constrained()->nullOnDelete();

            // Add name for user-friendly identification
            $table->string('name', 255)->nullable()->after('marketplace');

            // Add separate credential fields for different auth methods
            $table->string('api_key')->nullable()->after('name');
            $table->string('client_id')->nullable()->after('api_key');
            $table->string('client_secret')->nullable()->after('client_id');
            $table->string('oauth_token')->nullable()->after('client_secret');
            $table->string('oauth_refresh_token')->nullable()->after('oauth_token');
            $table->string('shop_id')->nullable()->after('oauth_refresh_token'); // campaignId / shopId / warehouse
            $table->json('credentials_json')->nullable()->after('shop_id'); // additional fields

            // Update index
            $table->index(['user_id', 'company_id', 'marketplace'], 'mp_user_company_marketplace_idx');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropIndex('mp_user_company_marketplace_idx');

            $table->dropConstrainedForeignId('user_id');
            $table->dropColumn([
                'name',
                'api_key',
                'client_id',
                'client_secret',
                'oauth_token',
                'oauth_refresh_token',
                'shop_id',
                'credentials_json',
            ]);
        });
    }
};
