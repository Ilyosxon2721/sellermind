<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Добавить значение 'store' в enum-колонки таблицы sales
 * для поддержки продаж через интернет-магазин
 */
return new class extends Migration
{
    /**
     * Добавить 'store' в enum-колонки type и source
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `type` ENUM('marketplace','manual','pos','store') NOT NULL DEFAULT 'manual'");
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `source` ENUM('uzum','wb','ozon','ym','manual','pos','store') NULL DEFAULT NULL");
    }

    /**
     * Откатить enum-колонки к исходным значениям
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `type` ENUM('marketplace','manual','pos') NOT NULL DEFAULT 'manual'");
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `source` ENUM('uzum','wb','ozon','ym','manual','pos') NULL DEFAULT NULL");
    }
};
