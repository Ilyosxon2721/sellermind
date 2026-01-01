<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'sqlite') {
            return;
        }
        // Удаляем старые таблицы заказов
        // Данные уже мигрированы в wb_orders и uzum_orders

        // Сначала удаляем внешние ключи из зависимых таблиц
        if (Schema::hasTable('marketplace_returns')) {
            Schema::table('marketplace_returns', function (Blueprint $table) {
                if (Schema::hasColumn('marketplace_returns', 'marketplace_order_item_id')) {
                    $table->dropForeign(['marketplace_order_item_id']);
                    $table->dropColumn('marketplace_order_item_id');
                }
                if (Schema::hasColumn('marketplace_returns', 'marketplace_order_id')) {
                    $table->dropForeign(['marketplace_order_id']);
                    $table->dropColumn('marketplace_order_id');
                }
            });
        }

        if (Schema::hasTable('marketplace_payout_items')) {
            Schema::table('marketplace_payout_items', function (Blueprint $table) {
                if (Schema::hasColumn('marketplace_payout_items', 'marketplace_order_item_id')) {
                    $table->dropForeign(['marketplace_order_item_id']);
                    $table->dropColumn('marketplace_order_item_id');
                }
                if (Schema::hasColumn('marketplace_payout_items', 'marketplace_order_id')) {
                    $table->dropForeign(['marketplace_order_id']);
                    $table->dropColumn('marketplace_order_id');
                }
            });
        }

        // Теперь можем удалить таблицы
        Schema::dropIfExists('marketplace_order_items');
        Schema::dropIfExists('marketplace_orders');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Восстановление не предусмотрено
        // Данные находятся в wb_orders и uzum_orders
    }
};
