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
        Schema::create('promotion_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_variant_id')->constrained()->onDelete('cascade');

            // Price tracking
            $table->decimal('original_price', 10, 2);
            $table->decimal('discounted_price', 10, 2);
            $table->decimal('discount_amount', 10, 2);

            // Stats
            $table->integer('units_sold')->default(0);
            $table->decimal('revenue_generated', 12, 2)->default(0);

            // Performance metrics (for slow-moving detection)
            $table->integer('days_since_last_sale')->nullable();
            $table->integer('stock_at_promotion_start')->nullable();
            $table->decimal('turnover_rate_before', 8, 4)->nullable();

            $table->timestamps();

            $table->unique(['promotion_id', 'product_variant_id']);
            $table->index('promotion_id');
            $table->index('product_variant_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotion_products');
    }
};
