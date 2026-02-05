<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Остатки
            $table->decimal('expected_quantity', 12, 3)->default(0); // Ожидаемое (по учёту)
            $table->decimal('actual_quantity', 12, 3)->nullable(); // Фактическое (подсчитанное)
            $table->decimal('difference', 12, 3)->default(0); // Разница (факт - учёт)

            // Цены для расчёта сумм
            $table->decimal('unit_price', 15, 2)->default(0); // Цена единицы
            $table->decimal('difference_amount', 15, 2)->default(0); // Сумма расхождения

            // Статус позиции
            $table->enum('status', ['pending', 'counted', 'verified'])->default('pending');

            // Причина расхождения
            $table->string('discrepancy_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            // Индексы
            $table->index(['inventory_id', 'status']);
            $table->unique(['inventory_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_items');
    }
};
