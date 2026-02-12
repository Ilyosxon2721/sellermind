<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы способов доставки магазина
     */
    public function up(): void
    {
        Schema::create('store_delivery_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type', 50); // pickup, courier, post, cdek, express
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('free_from', 15, 2)->nullable();
            $table->integer('min_days')->default(1);
            $table->integer('max_days')->default(3);
            $table->json('zones')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('store_delivery_methods');
    }
};
