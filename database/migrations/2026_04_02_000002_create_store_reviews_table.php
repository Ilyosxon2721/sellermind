<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Отзывы покупателей на товары витрины
     */
    public function up(): void
    {
        Schema::create('store_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->foreignId('store_product_id')->constrained('store_products')->cascadeOnDelete();
            $table->unsignedBigInteger('store_order_id')->nullable();

            // Автор
            $table->string('author_name');
            $table->string('author_email')->nullable();
            $table->string('author_phone', 50)->nullable();

            // Содержание
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('text')->nullable();
            $table->text('pros')->nullable();
            $table->text('cons')->nullable();

            // Модерация
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->text('admin_reply')->nullable();
            $table->timestamp('admin_replied_at')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'is_approved']);
            $table->index(['store_product_id', 'is_approved']);

            $table->foreign('store_order_id')
                ->references('id')
                ->on('store_orders')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_reviews');
    }
};
