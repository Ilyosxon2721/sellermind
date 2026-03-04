<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы баннеров магазина
     */
    public function up(): void
    {
        Schema::create('store_banners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('title')->nullable();
            $table->text('subtitle')->nullable();
            $table->string('image', 500);
            $table->string('image_mobile', 500)->nullable();
            $table->string('url', 500)->nullable();
            $table->string('button_text', 100)->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['store_id', 'is_active']);
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('store_banners');
    }
};
