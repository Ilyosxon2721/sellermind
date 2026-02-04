<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            // First, create a regular index on product_variant_id for the FK to use
            $table->index('product_variant_id', 'vml_product_variant_idx');
        });

        // Now we can drop the old unique index (FK will use the new index)
        DB::statement('ALTER TABLE variant_marketplace_links DROP INDEX variant_mp_unique');

        // Create new unique index (marketplace_product + external_sku_id)
        // This allows one variant to be linked to multiple marketplace products,
        // but prevents duplicate links to the same marketplace product SKU
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->unique(['marketplace_product_id', 'external_sku_id'], 'variant_mp_sku_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new unique index
        DB::statement('ALTER TABLE variant_marketplace_links DROP INDEX variant_mp_sku_unique');

        // Restore the old unique index
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->unique(['product_variant_id', 'marketplace_product_id'], 'variant_mp_unique');
        });

        // Drop the helper index on product_variant_id
        Schema::table('variant_marketplace_links', function (Blueprint $table) {
            $table->dropIndex('vml_product_variant_idx');
        });
    }
};
