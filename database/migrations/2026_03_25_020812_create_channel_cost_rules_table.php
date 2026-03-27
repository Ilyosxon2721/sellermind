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
        Schema::create('channel_cost_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id');
            $table->string('channel_code', 32); // WB, OZON, UZUM, YM
            $table->string('name')->nullable(); // Описание правила

            // Комиссия маркетплейса
            $table->decimal('commission_percent', 8, 4)->default(0.25); // 0.25 = 25%
            $table->decimal('commission_fixed', 12, 2)->default(0); // Фиксированная часть

            // Логистика
            $table->decimal('logistics_fixed', 12, 2)->default(0); // Стоимость логистики
            $table->decimal('return_logistics_fixed', 12, 2)->default(0); // Обратная логистика

            // Эквайринг / платёжный сбор
            $table->decimal('payment_fee_percent', 8, 4)->default(0); // 0.015 = 1.5%

            // Возвраты
            $table->decimal('return_percent', 8, 4)->default(0); // Процент возвратов (0.05 = 5%)

            // Хранение
            $table->decimal('storage_cost_per_day', 12, 2)->default(0); // Стоимость хранения/день

            // Налоги
            $table->decimal('vat_percent', 8, 4)->default(0); // НДС (0.12 = 12%)
            $table->decimal('turnover_tax_percent', 8, 4)->default(0); // Налог на оборот
            $table->decimal('profit_tax_percent', 8, 4)->default(0); // Налог на прибыль

            // Прочие расходы
            $table->decimal('other_percent', 8, 4)->default(0); // Прочие % от цены
            $table->decimal('other_fixed', 12, 2)->default(0); // Прочие фиксированные

            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'channel_code']);
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channel_cost_rules');
    }
};
