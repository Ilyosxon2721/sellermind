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
     * Таблица для хранения дерева категорий Uzum Market
     */
    public function up(): void
    {
        Schema::create('uzum_categories', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->primary()->comment('ID категории в Uzum');

            // Иерархия категорий
            $table->unsignedBigInteger('parent_id')->nullable()->index()->comment('Родительская категория');

            // Данные категории
            $table->string('title', 255)->comment('Название категории');
            $table->unsignedInteger('products_count')->default(0)->comment('Количество товаров в категории');

            // Метаданные
            $table->timestamp('last_synced_at')->nullable()->comment('Время последней синхронизации');
            $table->timestamps();

            // Foreign key для иерархии (self-referencing)
            $table->foreign('parent_id')
                ->references('id')
                ->on('uzum_categories')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uzum_categories');
    }
};
