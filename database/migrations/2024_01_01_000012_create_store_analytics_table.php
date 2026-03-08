<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы аналитики магазина
     */
    public function up(): void
    {
        Schema::create('store_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->date('date');
            $table->integer('visits')->default(0);
            $table->integer('unique_visitors')->default(0);
            $table->integer('page_views')->default(0);
            $table->integer('cart_additions')->default(0);
            $table->integer('checkouts_started')->default(0);
            $table->integer('orders_completed')->default(0);
            $table->decimal('revenue', 15, 2)->default(0);
            $table->decimal('average_order', 15, 2)->default(0);

            $table->timestamp('created_at')->nullable();

            $table->unique(['store_id', 'date']);
            $table->index(['store_id', 'date']);
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('store_analytics');
    }
};
