<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Добавить поддержку нескольких маркетплейс-аккаунтов в сфере продаж
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->json('marketplace_account_ids')->nullable()->after('marketplace_account_id');
        });

        // Мигрируем данные из старого поля в новое
        DB::table('kpi_sales_spheres')
            ->whereNotNull('marketplace_account_id')
            ->eachById(function ($sphere) {
                DB::table('kpi_sales_spheres')
                    ->where('id', $sphere->id)
                    ->update(['marketplace_account_ids' => json_encode([$sphere->marketplace_account_id])]);
            });
    }

    public function down(): void
    {
        Schema::table('kpi_sales_spheres', function (Blueprint $table) {
            $table->dropColumn('marketplace_account_ids');
        });
    }
};
