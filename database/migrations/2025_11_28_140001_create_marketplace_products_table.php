<?php

// file: database/migrations/2025_11_28_140001_create_marketplace_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_products', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_account_id')->constrained('marketplace_accounts')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();

            $table->string('external_product_id')->nullable(); // ID товара на МП
            $table->string('external_offer_id')->nullable();   // offerId / nmId / sku
            $table->string('external_sku')->nullable();        // артикул/sku на МП
            $table->string('status', 30)->default('pending');  // pending, active, error, archived
            $table->string('shop_id')->nullable();             // магазин/витрина для Uzum и др.
            $table->string('title')->nullable();
            $table->string('category')->nullable();
            $table->string('preview_image')->nullable();
            $table->json('raw_payload')->nullable();

            $table->decimal('last_synced_price', 15, 2)->nullable();
            $table->integer('last_synced_stock')->nullable();

            $table->timestamp('last_synced_at')->nullable();
            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->unique(['marketplace_account_id', 'product_id'], 'mp_acc_prod_unique');
            $table->index(['marketplace_account_id', 'external_product_id'], 'mp_acc_ext_prod_idx');
            $table->unique(['marketplace_account_id', 'shop_id', 'external_product_id'], 'mp_acc_shop_ext_prod_unique');
            $table->index(['marketplace_account_id', 'status'], 'mp_acc_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_products');
    }
};
