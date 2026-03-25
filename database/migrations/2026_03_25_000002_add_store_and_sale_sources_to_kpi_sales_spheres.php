<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавить поддержку интернет-магазина и ручных продаж (Sale) как источников KPI
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->json('store_ids')->nullable()->after('offline_sale_types');
            $table->json('sale_sources')->nullable()->after('store_ids');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->dropColumn(['store_ids', 'sale_sources']);
        });
    }
};
