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
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'telegram_id')) {
                $table->string('telegram_id')->nullable()->unique()->after('remember_token');
            }
            if (! Schema::hasColumn('users', 'telegram_username')) {
                $table->string('telegram_username')->nullable()->after('telegram_id');
            }
            if (! Schema::hasColumn('users', 'telegram_notifications_enabled')) {
                $table->boolean('telegram_notifications_enabled')->default(true)->after('telegram_username');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telegram_id', 'telegram_username', 'telegram_notifications_enabled']);
        });
    }
};
