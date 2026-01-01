<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterparty_contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('counterparty_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            // Данные договора
            $table->string('number'); // Номер договора
            $table->string('name')->nullable(); // Название договора
            $table->date('date'); // Дата договора
            $table->date('valid_from')->nullable(); // Действует с
            $table->date('valid_until')->nullable(); // Действует до
            
            // Комиссия
            $table->decimal('commission_percent', 5, 2)->default(0); // Комиссия в %
            $table->enum('commission_type', ['sales', 'profit'])->default('sales'); // От продаж или от прибыли
            $table->boolean('commission_includes_vat')->default(true); // Комиссия с НДС
            
            // Статус
            $table->enum('status', ['draft', 'active', 'suspended', 'terminated', 'expired'])->default('draft');
            
            // Условия
            $table->integer('payment_days')->default(0); // Отсрочка платежа в днях
            $table->decimal('credit_limit', 15, 2)->nullable(); // Кредитный лимит
            $table->string('currency', 3)->default('UZS');
            
            // Файлы и примечания
            $table->string('file_path')->nullable(); // Путь к файлу договора
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Индексы
            $table->index(['counterparty_id', 'status']);
            $table->index(['company_id', 'status']);
            $table->index('number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterparty_contracts');
    }
};
