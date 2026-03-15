<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавить поля для привязки Telegram к аккаунту маркетплейса
     */
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->index()->after('source');
            $table->string('telegram_username')->nullable()->after('telegram_chat_id');
        });
    }

    /**
     * Откатить изменения
     */
    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropIndex(['telegram_chat_id']);
            $table->dropColumn(['telegram_chat_id', 'telegram_username']);
        });
    }
};
