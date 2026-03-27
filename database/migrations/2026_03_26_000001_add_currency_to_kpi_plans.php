<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавить выбор валюты в KPI-план
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_plans', function (Blueprint $table) {
            $table->string('currency', 10)->default('UZS')->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_plans', function (Blueprint $table) {
            $table->dropColumn('currency');
        });
    }
};
