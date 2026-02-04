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
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->string('external_sku_id')->nullable()->after('external_offer_id')
                ->comment('SKU ID for marketplaces with multi-SKU products (e.g., Uzum)');
            $table->index('external_sku_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->dropIndex(['external_sku_id']);
            $table->dropColumn('external_sku_id');
        });
    }
};
