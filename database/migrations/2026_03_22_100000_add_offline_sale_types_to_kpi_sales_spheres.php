<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->json('offline_sale_types')->nullable()->after('marketplace_account_ids');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->dropColumn('offline_sale_types');
        });
    }
};
