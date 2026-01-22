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
            $table->string('marketplace_barcode')->nullable()->after('external_sku')
                ->comment('Баркод товара на маркетплейсе (может отличаться от internal barcode)');

            // Index for faster lookups by marketplace barcode
            $table->index(['marketplace_account_id', 'marketplace_barcode'], 'vml_account_barcode_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->dropIndex('vml_account_barcode_idx');
            $table->dropColumn('marketplace_barcode');
        });
    }
};
