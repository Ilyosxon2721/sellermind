<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы способов оплаты магазина
     */
    public function up(): void
    {
        Schema::create('store_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('type', 50); // cash, card, click, payme, uzcard, transfer
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('settings')->nullable();
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
        Schema::dropIfExists('store_payment_methods');
    }
};
