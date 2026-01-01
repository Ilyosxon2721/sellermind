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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->string('sale_number')->unique(); // Уникальный номер продажи (AUTO, MAN-001, etc)
            $table->enum('type', ['marketplace', 'manual', 'pos'])->default('manual'); // Тип продажи
            $table->enum('source', ['uzum', 'wb', 'ozon', 'ym', 'manual', 'pos'])->nullable(); // Источник

            // Контрагент (опционально для marketplace продаж)
            $table->foreignId('counterparty_id')->nullable()->constrained()->onDelete('set null');

            // Связь с заказом маркетплейса (если применимо)
            $table->string('marketplace_order_type')->nullable(); // uzum_order, wb_order, ozon_order, ym_order
            $table->unsignedBigInteger('marketplace_order_id')->nullable(); // ID заказа в соответствующей таблице

            // Финансовая информация
            $table->decimal('subtotal', 15, 2)->default(0); // Сумма без учета скидок/наценок
            $table->decimal('discount_amount', 15, 2)->default(0); // Сумма скидки
            $table->decimal('tax_amount', 15, 2)->default(0); // Сумма налога
            $table->decimal('total_amount', 15, 2)->default(0); // Итоговая сумма
            $table->string('currency', 3)->default('UZS'); // Валюта

            // Статус
            $table->enum('status', ['draft', 'confirmed', 'completed', 'cancelled'])->default('draft');
            $table->timestamp('confirmed_at')->nullable(); // Дата подтверждения
            $table->timestamp('completed_at')->nullable(); // Дата завершения
            $table->timestamp('cancelled_at')->nullable(); // Дата отмены

            // Дополнительная информация
            $table->text('notes')->nullable(); // Примечания
            $table->json('metadata')->nullable(); // Доп. данные (адрес доставки, телефон и т.д.)

            // Информация о пользователе
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->onDelete('set null');

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'status']);
            $table->index(['counterparty_id']);
            $table->index(['created_at']);
            $table->index(['marketplace_order_type', 'marketplace_order_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
