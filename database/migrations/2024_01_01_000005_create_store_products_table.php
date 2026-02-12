<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы товаров магазина
     */
    public function up(): void
    {
        Schema::create('store_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();

            $table->string('custom_name')->nullable();
            $table->text('custom_description')->nullable();
            $table->decimal('custom_price', 15, 2)->nullable();
            $table->boolean('is_visible')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->integer('position')->default(0);

            $table->timestamp('created_at')->nullable();

            $table->unique(['store_id', 'product_id']);
            $table->index(['store_id', 'is_featured', 'is_visible']);
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('store_products');
    }
};
