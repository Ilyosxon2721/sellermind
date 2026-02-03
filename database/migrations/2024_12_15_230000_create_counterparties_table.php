<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counterparties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');

            // Тип: физ лицо или юр лицо
            $table->enum('type', ['individual', 'legal'])->default('individual');

            // Основные данные
            $table->string('name'); // ФИО или название организации
            $table->string('short_name')->nullable(); // Сокращённое название

            // Реквизиты юр. лица
            $table->string('inn')->nullable(); // ИНН
            $table->string('kpp')->nullable(); // КПП (для юр. лиц)
            $table->string('ogrn')->nullable(); // ОГРН
            $table->string('okpo')->nullable(); // ОКПО

            // Контакты
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();

            // Адреса
            $table->text('legal_address')->nullable(); // Юридический адрес
            $table->text('actual_address')->nullable(); // Фактический адрес

            // Банковские реквизиты
            $table->string('bank_name')->nullable();
            $table->string('bank_bik')->nullable();
            $table->string('bank_account')->nullable(); // Расчётный счёт
            $table->string('bank_corr_account')->nullable(); // Корр. счёт

            // Контактное лицо
            $table->string('contact_person')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_position')->nullable();

            // Статус и прочее
            $table->boolean('is_active')->default(true);
            $table->boolean('is_supplier')->default(false); // Поставщик
            $table->boolean('is_customer')->default(true); // Покупатель
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Индексы
            $table->index(['company_id', 'is_active']);
            $table->index('inn');
            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counterparties');
    }
};
