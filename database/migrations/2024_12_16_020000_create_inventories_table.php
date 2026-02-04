<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            // Номер и дата
            $table->string('number')->nullable(); // Номер документа
            $table->date('date'); // Дата инвентаризации

            // Статус
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');

            // Тип инвентаризации
            $table->enum('type', ['full', 'partial'])->default('full'); // Полная или частичная

            // Результаты
            $table->integer('total_items')->default(0); // Всего позиций
            $table->integer('matched_items')->default(0); // Совпадают
            $table->integer('surplus_items')->default(0); // Излишки
            $table->integer('shortage_items')->default(0); // Недостачи

            // Суммы расхождений
            $table->decimal('surplus_amount', 15, 2)->default(0); // Сумма излишков
            $table->decimal('shortage_amount', 15, 2)->default(0); // Сумма недостач

            // Применение результатов
            $table->boolean('is_applied')->default(false); // Результаты применены
            $table->timestamp('applied_at')->nullable();
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['company_id', 'status']);
            $table->index(['warehouse_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
