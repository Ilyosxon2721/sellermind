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
        // Удаляем unique constraints, чтобы разрешить несколько аккаунтов одного маркетплейса
        // Используем прямой SQL для большего контроля

        // 1. Удаляем unique индекс на user_id + company_id + marketplace
        try {
            \DB::statement('ALTER TABLE marketplace_accounts DROP INDEX mp_user_company_marketplace_idx');
            echo "Dropped mp_user_company_marketplace_idx\n";
        } catch (\Throwable $e) {
            echo "mp_user_company_marketplace_idx already dropped or doesn't exist\n";
        }

        // 2. Удаляем unique индекс на company_id + marketplace
        try {
            \DB::statement('ALTER TABLE marketplace_accounts DROP INDEX marketplace_accounts_company_id_marketplace_unique');
            echo "Dropped marketplace_accounts_company_id_marketplace_unique\n";
        } catch (\Throwable $e) {
            echo "marketplace_accounts_company_id_marketplace_unique already dropped or doesn't exist\n";
        }

        // 3. Добавляем обычные (не unique) индексы для производительности
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            $table->index(['company_id', 'marketplace']);
            $table->index(['user_id', 'company_id', 'marketplace']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('marketplace_accounts', function (Blueprint $table) {
            // Восстанавливаем unique constraint
            $table->unique(['company_id', 'marketplace']);
        });
    }
};
