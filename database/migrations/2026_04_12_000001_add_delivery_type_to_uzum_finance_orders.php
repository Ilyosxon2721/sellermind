<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Добавляем delivery_type в uzum_finance_orders, чтобы хранить тип доставки
 * (FBS / DBS / EDBS / FBO) рядом с финансовыми позициями.
 *
 * Тип определяется так: если order_id найден в uzum_orders — копируем оттуда
 * delivery_type, иначе считаем FBO (заказ выполняет сам Uzum, продавец в нём
 * не участвует, поэтому через FBS API он не приходит).
 *
 * Backfill значения делается отдельно — после next sync через
 * UzumFinanceOrderEnricher::enrichForAccount().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uzum_finance_orders', function (Blueprint $table) {
            $table->string('delivery_type', 16)
                ->nullable()
                ->after('shop_id')
                ->comment('FBS|DBS|EDBS|FBO — определяется через перекрёстную проверку с uzum_orders');

            $table->index(
                ['marketplace_account_id', 'delivery_type'],
                'uzum_finance_account_delivery_type_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('uzum_finance_orders', function (Blueprint $table) {
            $table->dropIndex('uzum_finance_account_delivery_type_idx');
            $table->dropColumn('delivery_type');
        });
    }
};
