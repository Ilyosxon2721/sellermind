<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы тем оформления магазинов
     */
    public function up(): void
    {
        Schema::create('store_themes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            // Шаблон
            $table->string('template', 50)->default('default');

            // Цвета
            $table->string('primary_color', 7)->default('#007AFF');
            $table->string('secondary_color', 7)->default('#5856D6');
            $table->string('accent_color', 7)->default('#FF9500');
            $table->string('background_color', 7)->default('#FFFFFF');
            $table->string('text_color', 7)->default('#1C1C1E');

            // Шрифты
            $table->string('heading_font', 100)->default('Inter');
            $table->string('body_font', 100)->default('Inter');

            // Шапка
            $table->string('header_style', 20)->default('default');
            $table->string('header_bg_color', 7)->default('#FFFFFF');
            $table->string('header_text_color', 7)->default('#1C1C1E');
            $table->boolean('show_search')->default(true);
            $table->boolean('show_cart')->default(true);
            $table->boolean('show_phone')->default(true);

            // Герой-баннер
            $table->boolean('hero_enabled')->default(true);
            $table->string('hero_title')->nullable();
            $table->text('hero_subtitle')->nullable();
            $table->string('hero_image', 500)->nullable();
            $table->string('hero_button_text', 100)->default('Смотреть каталог');
            $table->string('hero_button_url', 255)->default('/catalog');

            // Каталог
            $table->integer('products_per_page')->default(12);
            $table->string('product_card_style', 20)->default('default');
            $table->boolean('show_quick_view')->default(true);
            $table->boolean('show_add_to_cart')->default(true);

            // Подвал
            $table->string('footer_style', 20)->default('default');
            $table->string('footer_bg_color', 7)->default('#1C1C1E');
            $table->string('footer_text_color', 7)->default('#FFFFFF');
            $table->text('footer_text')->nullable();

            // Кастомный CSS
            $table->text('custom_css')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('store_themes');
    }
};
