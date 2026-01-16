<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            // Удаляем уникальный индекс, чтобы разрешить несколько аккаунтов одного маркетплейса
            $table->dropUnique(['company_id', 'marketplace']);
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->unique(['company_id', 'marketplace']);
        });
    }
};
