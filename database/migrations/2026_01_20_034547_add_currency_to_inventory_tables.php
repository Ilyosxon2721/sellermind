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
        // Добавляем валюту в строки документов
        Schema::table('inventory_document_lines', function (Blueprint $table) {
            $table->string('currency_code', 8)->default('UZS')->after('total_cost');
            $table->decimal('exchange_rate', 12, 4)->default(1)->after('currency_code');
            // total_cost_base - стоимость в базовой валюте (UZS)
            $table->decimal('total_cost_base', 18, 2)->nullable()->after('exchange_rate');
        });

        // Добавляем валюту в stock_ledger
        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->string('currency_code', 8)->default('UZS')->after('cost_delta');
            // cost_delta хранит стоимость в базовой валюте (UZS)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_document_lines', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'exchange_rate', 'total_cost_base']);
        });

        Schema::table('stock_ledger', function (Blueprint $table) {
            $table->dropColumn(['currency_code']);
        });
    }
};
