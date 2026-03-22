<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Таблица для хранения снепшотов цен и метрик товаров Uzum
     */
    public function up(): void
    {
        Schema::create('uzum_products_snapshots', function (Blueprint $table) {
            $table->id();

            // Идентификаторы товара и категории
            $table->unsignedBigInteger('product_id')->index()->comment('ID товара в Uzum');
            $table->unsignedBigInteger('category_id')->index()->comment('ID категории в Uzum');
            $table->string('shop_slug', 255)->index()->comment('Slug магазина');

            // Данные товара
            $table->string('title', 500)->comment('Название товара');
            $table->decimal('price', 12, 2)->comment('Текущая цена (сум)');
            $table->decimal('original_price', 12, 2)->nullable()->comment('Цена до скидки');

            // Метрики
            $table->decimal('rating', 3, 2)->default(0)->comment('Рейтинг товара (0-5)');
            $table->unsignedInteger('reviews_count')->default(0)->comment('Количество отзывов');
            $table->unsignedInteger('orders_count')->default(0)->comment('Количество заказов');

            // Временные метки
            $table->timestamp('scraped_at')->index()->comment('Время сбора данных');
            $table->timestamps();

            // Индексы для быстрого поиска
            $table->index(['product_id', 'scraped_at'], 'product_time_idx');
            $table->index(['category_id', 'scraped_at'], 'category_time_idx');
            $table->index(['shop_slug', 'scraped_at'], 'shop_time_idx');
        });

        // Примечание: Партиционирование по месяцам необходимо настроить вручную
        // после создания таблицы через raw SQL (ALTER TABLE ... PARTITION BY ...)
        // так как Laravel не поддерживает партиционирование из коробки
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uzum_products_snapshots');
    }
};
