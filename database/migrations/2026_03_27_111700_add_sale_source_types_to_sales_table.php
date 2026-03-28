<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Расширить enum source в таблице sales для поддержки типов ручных продаж:
 * retail (розница), wholesale (опт), direct (прямая продажа)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `source` ENUM('uzum','wb','ozon','ym','manual','pos','store','retail','wholesale','direct') NULL DEFAULT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `sales` MODIFY COLUMN `source` ENUM('uzum','wb','ozon','ym','manual','pos','store') NULL DEFAULT NULL");
    }
};
