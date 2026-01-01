<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('supplies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_account_id')->constrained()->onDelete('cascade');
            $table->string('external_supply_id')->nullable(); // WB supply ID (например, WB-GI-12345678)
            $table->string('name'); // Название поставки
            $table->enum('status', ['draft', 'in_assembly', 'ready', 'sent', 'delivered', 'cancelled'])->default('draft');
            $table->text('description')->nullable();
            $table->integer('orders_count')->default(0); // Количество заказов в поставке
            $table->decimal('total_amount', 15, 2)->default(0); // Общая сумма
            $table->timestamp('closed_at')->nullable(); // Когда поставка закрыта для добавления заказов
            $table->timestamp('sent_at')->nullable(); // Когда поставка отправлена
            $table->timestamp('delivered_at')->nullable(); // Когда поставка доставлена
            $table->json('metadata')->nullable(); // Дополнительные данные (например, от WB API)
            $table->timestamps();

            // Индексы
            $table->index('marketplace_account_id');
            $table->index('status');
            $table->index('external_supply_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('supplies');
    }
};
