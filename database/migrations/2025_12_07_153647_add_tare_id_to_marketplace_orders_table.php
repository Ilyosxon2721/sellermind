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
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->foreignId('tare_id')->nullable()->after('supply_id')->constrained('tares')->onDelete('set null');
            $table->index('tare_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropForeign(['tare_id']);
            $table->dropColumn('tare_id');
        });
    }
};
