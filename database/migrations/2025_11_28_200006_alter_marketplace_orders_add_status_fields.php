<?php
// file: database/migrations/2025_11_28_200006_alter_marketplace_orders_add_status_fields.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            // Внутренний унифицированный статус
            $table->string('internal_status', 50)
                ->nullable()
                ->after('status'); // created, confirmed, packed, shipped, delivered, canceled, returned

            // Статусы возврата/претензий
            $table->string('return_status', 50)
                ->nullable()
                ->after('internal_status');

            $table->string('claim_status', 50)
                ->nullable()
                ->after('return_status');
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropColumn(['internal_status', 'return_status', 'claim_status']);
        });
    }
};
