<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Создание таблицы заказов магазина
     */
    public function up(): void
    {
        Schema::create('store_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();

            $table->string('order_number', 50)->unique();

            // Покупатель
            $table->string('customer_name');
            $table->string('customer_phone', 50);
            $table->string('customer_email')->nullable();

            // Доставка
            $table->foreignId('delivery_method_id')->nullable()->constrained('store_delivery_methods')->nullOnDelete();
            $table->text('delivery_address')->nullable();
            $table->string('delivery_city')->nullable();
            $table->text('delivery_comment')->nullable();
            $table->decimal('delivery_price', 15, 2)->default(0);

            // Оплата
            $table->foreignId('payment_method_id')->nullable()->constrained('store_payment_methods')->nullOnDelete();
            $table->string('payment_status', 50)->default('pending');
            $table->string('payment_id')->nullable();

            // Суммы
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('total', 15, 2);

            // Статус и заметки
            $table->string('status', 50)->default('new');
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();

            // Связь с основной системой заказов
            $table->unsignedBigInteger('sellermind_order_id')->nullable();

            $table->timestamps();

            $table->index(['store_id', 'status']);
            $table->index('order_number');
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::dropIfExists('store_orders');
    }
};
