<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('sku', 100);
            $table->string('barcode', 20)->nullable();
            $table->string('article_suffix', 50)->nullable();
            $table->string('option_values_summary', 255)->nullable();
            $table->decimal('purchase_price', 12, 2)->nullable();
            $table->decimal('price_default', 12, 2)->nullable();
            $table->decimal('old_price_default', 12, 2)->nullable();
            $table->integer('stock_default')->nullable();
            $table->integer('weight_g')->nullable();
            $table->integer('length_mm')->nullable();
            $table->integer('width_mm')->nullable();
            $table->integer('height_mm')->nullable();
            $table->foreignId('main_image_id')->nullable()->constrained('product_images')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_deleted')->default(false);
            $table->timestamps();

            $table->unique(['product_id', 'sku']);
            $table->index(['product_id']);
            $table->index(['company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
