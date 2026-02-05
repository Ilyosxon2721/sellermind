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
        Schema::create('ozon_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->onDelete('cascade');
            $table->string('external_product_id')->nullable()->index(); // product_id from Ozon
            $table->string('external_offer_id')->nullable()->index(); // offer_id from Ozon (SKU)
            $table->string('barcode')->nullable()->index();
            $table->string('name');
            $table->integer('category_id')->nullable();
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('old_price', 15, 2)->nullable();
            $table->decimal('premium_price', 15, 2)->nullable(); // Price for premium customers
            $table->integer('stock')->default(0);
            $table->string('status')->nullable(); // processing, moderating, processed, archived, failed_moderation
            $table->json('images')->nullable();
            $table->json('attributes')->nullable();
            $table->text('description')->nullable();
            $table->string('vat')->nullable(); // VAT rate
            $table->integer('width')->nullable(); // mm
            $table->integer('height')->nullable(); // mm
            $table->integer('depth')->nullable(); // mm
            $table->integer('weight')->nullable(); // grams
            $table->boolean('visible')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['marketplace_account_id', 'external_product_id'], 'ozon_products_account_external_unique');
            $table->index(['marketplace_account_id', 'barcode']);
            $table->index(['marketplace_account_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ozon_products');
    }
};
