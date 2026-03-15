<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавить поля автоматизации Uzum в таблицу marketplace_accounts
     */
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->boolean('uzum_auto_confirm')->default(false)->after('uzum_token_expires_at');
            $table->boolean('uzum_auto_reply')->default(false)->after('uzum_auto_confirm');
            $table->string('uzum_review_tone')->default('friendly')->after('uzum_auto_reply');
        });
    }

    /**
     * Откатить добавление полей автоматизации Uzum
     */
    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->dropColumn(['uzum_auto_confirm', 'uzum_auto_reply', 'uzum_review_tone']);
        });
    }
};
