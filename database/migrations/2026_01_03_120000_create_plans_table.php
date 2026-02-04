<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');                    // Старт, Бизнес, Про, Enterprise
            $table->string('slug')->unique();          // start, business, pro, enterprise
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);           // Цена в сумах
            $table->string('currency', 3)->default('UZS');
            $table->enum('billing_period', ['monthly', 'quarterly', 'yearly'])->default('monthly');

            // Лимиты
            $table->integer('max_marketplace_accounts')->default(1);
            $table->integer('max_products')->default(200);
            $table->integer('max_orders_per_month')->default(500);
            $table->integer('max_users')->default(2);
            $table->integer('max_warehouses')->default(1);
            $table->integer('max_ai_requests')->default(100);
            $table->integer('data_retention_days')->default(30);

            // Флаги возможностей
            $table->boolean('has_api_access')->default(false);
            $table->boolean('has_priority_support')->default(false);
            $table->boolean('has_telegram_notifications')->default(true);
            $table->boolean('has_auto_pricing')->default(false);
            $table->boolean('has_analytics')->default(true);

            // Маркетплейсы (JSON array: ['uzum', 'wb', 'ozon', 'yandex'])
            $table->json('allowed_marketplaces')->nullable();

            // Дополнительные настройки
            $table->json('features')->nullable();      // Дополнительные фичи
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_popular')->default(false); // Метка "Популярный"

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
