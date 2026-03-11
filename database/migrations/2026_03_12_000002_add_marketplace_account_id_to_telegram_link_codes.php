<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавить связь кода привязки с конкретным аккаунтом маркетплейса
     */
    public function up(): void
    {
        Schema::table('telegram_link_codes', function (Blueprint $table) {
            $table->foreignId('marketplace_account_id')
                ->nullable()
                ->after('user_id')
                ->constrained('marketplace_accounts')
                ->nullOnDelete();
        });
    }

    /**
     * Откатить изменения
     */
    public function down(): void
    {
        Schema::table('telegram_link_codes', function (Blueprint $table) {
            $table->dropForeign(['marketplace_account_id']);
            $table->dropColumn('marketplace_account_id');
        });
    }
};
