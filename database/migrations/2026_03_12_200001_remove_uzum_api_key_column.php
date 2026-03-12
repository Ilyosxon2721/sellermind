<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Убираем дублирующее поле uzum_api_key.
 * Токен Uzum теперь хранится только в api_key.
 * Перед удалением копируем данные: если api_key пустой, берём узum_api_key.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Копируем токен из uzum_api_key → api_key если api_key пустой
        if (Schema::hasColumn('marketplace_accounts', 'uzum_api_key')) {
            DB::table('marketplace_accounts')
                ->where('marketplace', 'uzum')
                ->whereNull('api_key')
                ->whereNotNull('uzum_api_key')
                ->update(['api_key' => DB::raw('uzum_api_key')]);

            Schema::table('marketplace_accounts', function (Blueprint $table) {
                $table->dropColumn('uzum_api_key');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('marketplace_accounts', 'uzum_api_key')) {
            Schema::table('marketplace_accounts', function (Blueprint $table) {
                $table->text('uzum_api_key')->nullable()->after('uzum_client_secret');
            });
        }
    }
};
