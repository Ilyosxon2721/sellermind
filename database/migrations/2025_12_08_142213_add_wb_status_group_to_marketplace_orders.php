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
        if (!Schema::hasColumn('marketplace_orders', 'wb_status_group')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->string('wb_status_group')->nullable()->after('wb_supplier_status')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('marketplace_orders', 'wb_status_group')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->dropIndex(['wb_status_group']);
                $table->dropColumn('wb_status_group');
            });
        }
    }
};
