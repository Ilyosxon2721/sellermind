<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_customer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_customer_id')->constrained()->onDelete('cascade');

            // Полиморфная связь с заказом (UzumOrder, WbOrder, OzonOrder)
            $table->string('order_type'); // App\Models\UzumOrder, etc.
            $table->unsignedBigInteger('order_id');

            // Кэшированные данные для быстрого отображения
            $table->string('external_order_id')->nullable(); // ID заказа в маркетплейсе
            $table->string('source'); // uzum, wb, ozon
            $table->string('status')->nullable(); // Текущий статус заказа
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('currency', 3)->default('UZS');
            $table->timestamp('ordered_at')->nullable();

            $table->timestamps();

            // Уникальность: один заказ привязан к клиенту только один раз
            $table->unique(['marketplace_customer_id', 'order_type', 'order_id'], 'mco_customer_order_unique');
            $table->index(['order_type', 'order_id']);
            $table->index('marketplace_customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_customer_orders');
    }
};
