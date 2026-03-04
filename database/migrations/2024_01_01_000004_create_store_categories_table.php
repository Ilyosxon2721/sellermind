<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы категорий магазина
     */
    public function up(): void
    {
        Schema::create('store_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('product_categories')->cascadeOnDelete();

            $table->string('custom_name')->nullable();
            $table->text('custom_description')->nullable();
            $table->string('custom_image', 500)->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_visible')->default(true);
            $table->boolean('show_in_menu')->default(true);

            $table->timestamp('created_at')->nullable();

            $table->unique(['store_id', 'category_id']);
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('store_categories');
    }
};
