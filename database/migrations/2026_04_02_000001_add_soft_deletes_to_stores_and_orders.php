<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавить soft deletes для stores и store_orders
     */
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Откат миграции
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('store_orders', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
