<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавить поддержку ручных KPI-сфер с кастомными названиями метрик
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->boolean('is_manual')->default(false)->after('is_active');
            $table->string('label_metric1', 100)->nullable()->after('is_manual');
            $table->string('label_metric2', 100)->nullable()->after('label_metric1');
            $table->string('label_metric3', 100)->nullable()->after('label_metric2');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->dropColumn(['is_manual', 'label_metric1', 'label_metric2', 'label_metric3']);
        });
    }
};
