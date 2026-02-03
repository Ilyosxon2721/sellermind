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
        if (Schema::hasTable('wb_orders')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                if (! Schema::hasColumn('wb_orders', 'tare_id')) {
                    $table->unsignedBigInteger('tare_id')->nullable()->after('supply_id');
                    $table->foreign('tare_id')->references('id')->on('tares')->onDelete('set null');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('wb_orders')) {
            Schema::table('wb_orders', function (Blueprint $table) {
                if (Schema::hasColumn('wb_orders', 'tare_id')) {
                    $table->dropForeign(['tare_id']);
                    $table->dropColumn('tare_id');
                }
            });
        }
    }
};
