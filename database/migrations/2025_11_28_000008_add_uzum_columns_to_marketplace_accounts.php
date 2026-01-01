<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('marketplace_accounts')) {
            return;
        }

        $hasShopId = Schema::hasColumn('marketplace_accounts', 'shop_id');

        Schema::table('marketplace_accounts', function (Blueprint $table) use ($hasShopId) {
            if (!Schema::hasColumn('marketplace_accounts', 'uzum_client_id')) {
                $column = $table->string('uzum_client_id')->nullable();
                if ($hasShopId) {
                    $column->after('shop_id');
                }
            }
            if (!Schema::hasColumn('marketplace_accounts', 'uzum_client_secret')) {
                $column = $table->text('uzum_client_secret')->nullable();
                if ($hasShopId) {
                    $column->after('uzum_client_id');
                }
            }
            if (!Schema::hasColumn('marketplace_accounts', 'uzum_api_key')) {
                $column = $table->text('uzum_api_key')->nullable();
                if ($hasShopId) {
                    $column->after('uzum_client_secret');
                }
            }
            if (!Schema::hasColumn('marketplace_accounts', 'uzum_refresh_token')) {
                $column = $table->text('uzum_refresh_token')->nullable();
                if ($hasShopId) {
                    $column->after('uzum_api_key');
                }
            }
            if (!Schema::hasColumn('marketplace_accounts', 'uzum_access_token')) {
                $column = $table->text('uzum_access_token')->nullable();
                if ($hasShopId) {
                    $column->after('uzum_refresh_token');
                }
            }
            if (!Schema::hasColumn('marketplace_accounts', 'uzum_token_expires_at')) {
                $column = $table->dateTime('uzum_token_expires_at')->nullable();
                if ($hasShopId) {
                    $column->after('uzum_access_token');
                }
            }
            if (!Schema::hasColumn('marketplace_accounts', 'uzum_settings')) {
                $column = $table->json('uzum_settings')->nullable();
                if ($hasShopId) {
                    $column->after('uzum_token_expires_at');
                }
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('marketplace_accounts')) {
            return;
        }

        Schema::table('marketplace_accounts', function (Blueprint $table) {
            foreach ([
                'uzum_settings',
                'uzum_token_expires_at',
                'uzum_access_token',
                'uzum_refresh_token',
                'uzum_api_key',
                'uzum_client_secret',
                'uzum_client_id',
            ] as $column) {
                if (Schema::hasColumn('marketplace_accounts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
