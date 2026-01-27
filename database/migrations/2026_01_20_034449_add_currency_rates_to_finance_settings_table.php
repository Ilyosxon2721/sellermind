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
        Schema::table('finance_settings', function (Blueprint $table) {
            // Курсы валют (ручной ввод)
            $table->decimal('usd_rate', 12, 2)->default(12700)->after('base_currency_code');
            $table->decimal('rub_rate', 12, 4)->default(140)->after('usd_rate');
            $table->decimal('eur_rate', 12, 2)->default(13800)->after('rub_rate');

            // Дата последнего обновления курсов
            $table->timestamp('rates_updated_at')->nullable()->after('eur_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_settings', function (Blueprint $table) {
            $table->dropColumn(['usd_rate', 'rub_rate', 'eur_rate', 'rates_updated_at']);
        });
    }
};
