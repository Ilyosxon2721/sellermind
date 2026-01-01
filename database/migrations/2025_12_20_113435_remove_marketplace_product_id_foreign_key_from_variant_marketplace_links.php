<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Remove foreign key constraint on marketplace_product_id
     * because WB products are in wildberries_products table, not marketplace_products
     */
    public function up(): void
    {
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            // Drop the foreign key constraint
            $table->dropForeign(['marketplace_product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            // Re-add the foreign key constraint
            $table->foreign('marketplace_product_id')
                ->references('id')
                ->on('marketplace_products')
                ->onDelete('cascade');
        });
    }
};
