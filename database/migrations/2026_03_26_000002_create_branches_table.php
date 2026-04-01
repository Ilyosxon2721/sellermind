<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Филиалы компании + поддержка KPI-планов на филиал
 */
return new class extends Migration
{
    public function up(): void
    {
        // Таблица филиалов
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->foreignId('director_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['company_id', 'code']);
            $table->index(['company_id', 'is_active']);
        });

        // Привязка сотрудников к филиалу
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
        });

        // KPI-план может быть на филиал (branch_id) или на сотрудника (employee_id)
        Schema::table('kpi_plans', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('employee_id')->constrained('branches')->nullOnDelete();
            $table->foreignId('parent_plan_id')->nullable()->after('branch_id')->constrained('kpi_plans')->nullOnDelete();
            $table->string('plan_type', 20)->default('employee')->after('parent_plan_id'); // employee | branch
        });
    }

    public function down(): void
    {
        Schema::table('kpi_plans', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropConstrainedForeignId('parent_plan_id');
            $table->dropColumn('plan_type');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::dropIfExists('branches');
    }
};
