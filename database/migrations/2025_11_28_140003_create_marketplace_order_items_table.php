<?php

// file: database/migrations/2025_11_28_140003_create_marketplace_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_order_id')->constrained('marketplace_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();

            $table->string('external_offer_id')->nullable();  // sku / nmId / offerId
            $table->string('name')->nullable();

            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('total_price', 15, 2)->nullable();

            $table->json('raw_payload')->nullable();

            $table->timestamps();

            $table->index(['marketplace_order_id', 'product_id'], 'mp_order_item_prod_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_order_items');
    }
};
