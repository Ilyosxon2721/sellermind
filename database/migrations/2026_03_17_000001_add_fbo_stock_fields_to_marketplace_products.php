<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->integer('stock_fbs')->nullable()->after('last_synced_stock')->comment('FBS остатки (у продавца)');
            $table->integer('stock_fbo')->nullable()->after('stock_fbs')->comment('FBO остатки (на складе маркетплейса)');
            $table->integer('stock_additional')->nullable()->after('stock_fbo')->comment('Дополнительные остатки');
            $table->integer('quantity_sold')->nullable()->after('stock_additional')->comment('Всего продано');
            $table->integer('quantity_returned')->nullable()->after('quantity_sold')->comment('Всего возвращено');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_products', function (Blueprint $table) {
            $table->dropColumn(['stock_fbs', 'stock_fbo', 'stock_additional', 'quantity_sold', 'quantity_returned']);
        });
    }
};
