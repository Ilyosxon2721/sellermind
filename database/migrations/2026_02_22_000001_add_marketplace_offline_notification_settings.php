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
        Schema::table('user_notification_settings', function (Blueprint $table) {
            $table->boolean('notify_marketplace_order')->default(true)->after('notify_new_order');
            $table->boolean('notify_offline_sale')->default(true)->after('notify_marketplace_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_notification_settings', function (Blueprint $table) {
            $table->dropColumn(['notify_marketplace_order', 'notify_offline_sale']);
        });
    }
};
