<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->string('marketplace_code', 50)
                ->after('marketplace_account_id')
                ->nullable()
                ->comment('Marketplace code: wb, ozon, uzum, yandex_market');

            // Add index for faster queries
            $table->index(['marketplace_account_id', 'marketplace_code', 'is_active'], 'vml_account_marketplace_idx');
        });
    }

    public function down(): void
    {
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->dropIndex('vml_account_marketplace_idx');
            $table->dropColumn('marketplace_code');
        });
    }
};
