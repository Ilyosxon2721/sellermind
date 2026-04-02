<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Основные данные клиента
            $table->string('name');
            $table->string('phone')->index();
            $table->text('address')->nullable();
            $table->string('city')->nullable();

            // Источник (маркетплейс)
            $table->enum('source', ['uzum', 'wb', 'ozon', 'ym'])->index();

            // Статистика заказов
            $table->unsignedInteger('orders_count')->default(1);
            $table->decimal('total_spent', 14, 2)->default(0);
            $table->timestamp('first_order_at')->nullable();
            $table->timestamp('last_order_at')->nullable();

            // Связь с последним заказом (полиморфная)
            $table->string('last_order_type')->nullable();
            $table->unsignedBigInteger('last_order_id')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Уникальность: один клиент по телефону на компанию и маркетплейс
            $table->unique(['company_id', 'phone', 'source'], 'mc_company_phone_source_unique');
            $table->index(['company_id', 'source']);
            $table->index(['company_id', 'last_order_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_customers');
    }
};
