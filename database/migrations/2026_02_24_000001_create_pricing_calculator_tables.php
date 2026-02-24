<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Миграция для модуля калькулятора ценообразования.
 *
 * Создаёт 7 таблиц:
 * - marketplace_categories — справочник категорий маркетплейсов
 * - marketplace_commissions — комиссии маркетплейсов по категориям
 * - marketplace_logistics — тарифы логистики маркетплейсов
 * - marketplace_acquiring — эквайринг маркетплейсов
 * - product_pricings — расчёт цен товаров (привязка к компании)
 * - expense_templates — шаблоны расходов (привязка к компании)
 * - user_tax_settings — налоговые настройки (привязка к компании)
 */
return new class extends Migration
{
    /**
     * Создать таблицы модуля ценообразования
     */
    public function up(): void
    {
        // 1. Справочник категорий маркетплейсов (глобальный, без company_id)
        Schema::create('marketplace_categories', function (Blueprint $table): void {
            $table->id();
            $table->string('marketplace', 50);
            $table->string('category_id', 100);
            $table->string('name', 255);
            $table->string('parent_id')->nullable();
            $table->string('path')->nullable();
            $table->timestamps();

            $table->unique(['marketplace', 'category_id']);
            $table->index(['marketplace', 'name']);
        });

        // 2. Комиссии маркетплейсов по категориям (глобальный)
        Schema::create('marketplace_commissions', function (Blueprint $table): void {
            $table->id();
            $table->string('marketplace', 50);
            $table->foreignId('category_id')
                ->constrained('marketplace_categories')
                ->cascadeOnDelete();
            $table->enum('fulfillment_type', ['fbo', 'fbs', 'dbs', 'express'])->default('fbo');
            $table->decimal('commission_percent', 5, 2);
            $table->decimal('commission_min', 10, 2)->nullable();
            $table->decimal('commission_max', 10, 2)->nullable();
            $table->json('price_ranges')->nullable();
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['marketplace', 'is_active', 'effective_from'], 'mp_comm_mp_active_from_idx');
        });

        // 3. Тарифы логистики маркетплейсов (глобальный)
        Schema::create('marketplace_logistics', function (Blueprint $table): void {
            $table->id();
            $table->string('marketplace', 50);
            $table->enum('fulfillment_type', ['fbo', 'fbs', 'dbs', 'express']);
            $table->enum('logistics_type', ['delivery', 'return', 'processing', 'storage']);
            $table->string('region')->nullable();
            $table->decimal('volume_from', 10, 2)->nullable();
            $table->decimal('volume_to', 10, 2)->nullable();
            $table->decimal('weight_from', 10, 2)->nullable();
            $table->decimal('weight_to', 10, 2)->nullable();
            $table->decimal('rate', 12, 2);
            $table->enum('rate_type', ['fixed', 'per_liter', 'per_kg', 'percent'])->default('fixed');
            $table->string('currency', 3)->default('RUB');
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['marketplace', 'fulfillment_type', 'logistics_type', 'is_active'], 'mp_log_mp_ff_type_active_idx');
        });

        // 4. Эквайринг маркетплейсов (глобальный)
        Schema::create('marketplace_acquiring', function (Blueprint $table): void {
            $table->id();
            $table->string('marketplace', 50);
            $table->enum('payout_frequency', ['daily', 'weekly', 'biweekly', 'monthly'])->nullable();
            $table->decimal('rate_percent', 5, 2);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 5. Расчёт цен товаров (привязка к компании)
        Schema::create('product_pricings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('marketplace', 50);
            $table->string('marketplace_sku')->nullable();
            $table->foreignId('marketplace_category_id')
                ->nullable()
                ->constrained('marketplace_categories');
            $table->enum('fulfillment_type', ['fbo', 'fbs', 'dbs', 'express'])->default('fbo');

            // Затраты
            $table->decimal('cost_price', 12, 2);
            $table->decimal('packaging_cost', 10, 2)->default(0);
            $table->decimal('delivery_to_warehouse', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);

            // Габариты
            $table->decimal('length_cm', 8, 2)->nullable();
            $table->decimal('width_cm', 8, 2)->nullable();
            $table->decimal('height_cm', 8, 2)->nullable();
            $table->decimal('weight_kg', 8, 3)->nullable();

            // Рассчитанные поля
            $table->decimal('total_cost', 12, 2)->nullable();
            $table->decimal('commission_amount', 12, 2)->nullable();
            $table->decimal('logistics_cost', 12, 2)->nullable();
            $table->decimal('acquiring_amount', 12, 2)->nullable();
            $table->decimal('storage_cost', 12, 2)->nullable();
            $table->decimal('total_expenses', 12, 2)->nullable();

            // Цены
            $table->decimal('recommended_price', 12, 2)->nullable();
            $table->decimal('current_price', 12, 2)->nullable();
            $table->decimal('min_price', 12, 2)->nullable();

            // Маржинальность
            $table->decimal('target_margin_percent', 5, 2)->default(30);
            $table->decimal('actual_margin_percent', 5, 2)->nullable();
            $table->decimal('actual_margin_amount', 12, 2)->nullable();
            $table->decimal('roi_percent', 6, 2)->nullable();

            $table->string('currency', 3)->default('RUB');
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'marketplace']);
            $table->index(['company_id', 'marketplace']);
        });

        // 6. Шаблоны расходов (привязка к компании)
        Schema::create('expense_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('marketplace', 50)->nullable();
            $table->decimal('packaging_cost', 10, 2)->default(0);
            $table->decimal('delivery_to_warehouse', 10, 2)->default(0);
            $table->decimal('other_costs', 10, 2)->default(0);
            $table->decimal('target_margin_percent', 5, 2)->default(30);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        // 7. Налоговые настройки (привязка к компании)
        Schema::create('user_tax_settings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->enum('tax_system', [
                'osn',
                'usn_income',
                'usn_income_expense',
                'patent',
                'npd',
                'no_vat',
            ]);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->boolean('include_in_price')->default(true);
            $table->timestamps();

            $table->unique('company_id');
        });
    }

    /**
     * Откатить миграцию (удаление таблиц в обратном порядке)
     */
    public function down(): void
    {
        Schema::dropIfExists('user_tax_settings');
        Schema::dropIfExists('expense_templates');
        Schema::dropIfExists('product_pricings');
        Schema::dropIfExists('marketplace_acquiring');
        Schema::dropIfExists('marketplace_logistics');
        Schema::dropIfExists('marketplace_commissions');
        Schema::dropIfExists('marketplace_categories');
    }
};
