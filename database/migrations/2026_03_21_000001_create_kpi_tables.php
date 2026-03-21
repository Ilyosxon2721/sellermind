<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Сферы продаж (произвольные: WB, Ozon, Instagram, розница и т.д.)
        Schema::create('kpi_sales_spheres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50);
            $table->string('description')->nullable();
            $table->string('color', 7)->default('#3B82F6');
            $table->string('icon', 50)->default('chart');
            $table->foreignId('marketplace_account_id')->nullable()->constrained('marketplace_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        // Шкалы бонусов
        Schema::create('kpi_bonus_scales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->index('company_id');
        });

        // Ступени шкалы бонусов
        Schema::create('kpi_bonus_scale_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_bonus_scale_id')->constrained('kpi_bonus_scales')->cascadeOnDelete();
            $table->unsignedSmallInteger('min_percent');
            $table->unsignedSmallInteger('max_percent')->nullable();
            $table->enum('bonus_type', ['fixed', 'percent_revenue', 'percent_margin']);
            $table->decimal('bonus_value', 15, 2);
            $table->timestamps();

            $table->index('kpi_bonus_scale_id');
        });

        // KPI-планы сотрудников
        Schema::create('kpi_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('kpi_sales_sphere_id')->constrained('kpi_sales_spheres')->cascadeOnDelete();
            $table->foreignId('kpi_bonus_scale_id')->constrained('kpi_bonus_scales')->restrictOnDelete();
            $table->unsignedSmallInteger('period_year');
            $table->unsignedTinyInteger('period_month');

            // Целевые показатели
            $table->decimal('target_revenue', 18, 2)->default(0);
            $table->decimal('target_margin', 18, 2)->default(0);
            $table->unsignedInteger('target_orders')->default(0);

            // Веса метрик (сумма = 100)
            $table->unsignedTinyInteger('weight_revenue')->default(40);
            $table->unsignedTinyInteger('weight_margin')->default(30);
            $table->unsignedTinyInteger('weight_orders')->default(30);

            // Фактические показатели
            $table->decimal('actual_revenue', 18, 2)->default(0);
            $table->decimal('actual_margin', 18, 2)->default(0);
            $table->unsignedInteger('actual_orders')->default(0);

            // Результат
            $table->decimal('achievement_percent', 8, 2)->default(0);
            $table->decimal('bonus_amount', 15, 2)->default(0);

            // Статус
            $table->enum('status', ['active', 'calculated', 'approved', 'cancelled'])->default('active');
            $table->timestamp('calculated_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'kpi_sales_sphere_id', 'period_year', 'period_month'], 'kpi_plans_unique');
            $table->index(['company_id', 'period_year', 'period_month']);
            $table->index(['employee_id', 'period_year', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_plans');
        Schema::dropIfExists('kpi_bonus_scale_tiers');
        Schema::dropIfExists('kpi_bonus_scales');
        Schema::dropIfExists('kpi_sales_spheres');
    }
};
