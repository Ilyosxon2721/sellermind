<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавить валюту закупочной цены в варианты товаров
     */
    public function up(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->string('purchase_price_currency', 3)
                ->default('UZS')
                ->after('purchase_price')
                ->comment('Валюта закупочной цены (ISO 4217)');
        });
    }

    public function down(): void
    {
        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropColumn('purchase_price_currency');
        });
    }
};
