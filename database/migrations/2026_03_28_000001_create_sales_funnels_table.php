<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_funnels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');

            // Этап 1: Просмотры (ввод)
            $table->unsignedBigInteger('views')->default(0);

            // Этап 2: Обращения (% от просмотров)
            $table->decimal('inquiry_rate', 6, 3)->default(0);

            // Этап 3: Встречи / Добавления в корзину (% от обращений)
            $table->decimal('meeting_rate', 6, 3)->default(0);

            // Этап 4: Продажи (% от встреч)
            $table->decimal('sale_rate', 6, 3)->default(0);

            // Этап 5: Средний чек (ввод)
            $table->decimal('average_check', 15, 2)->default(0);

            // Этап 7: Маржинальность — % чистой прибыли от дохода
            $table->decimal('profit_margin', 6, 3)->default(0);

            // Этап 8: Бонус — % от чистой прибыли
            $table->decimal('bonus_rate', 6, 3)->default(0);

            // Валюта
            $table->char('currency', 3)->default('UZS');

            // Режим: auto — автоматический расчёт из реальных данных
            $table->boolean('is_auto')->default(false);

            // Фильтры для авто-режима
            $table->json('source_filter')->nullable(); // ['wb','ozon','uzum','ym','manual','wholesale','retail','direct']
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();

            // Снимок авто-рассчитанных данных
            $table->json('auto_snapshot')->nullable();

            $table->json('metadata')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'is_auto']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_funnels');
    }
};
