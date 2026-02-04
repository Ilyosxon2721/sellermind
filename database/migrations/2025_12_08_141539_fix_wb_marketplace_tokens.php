<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Копируем api_key в wb_marketplace_token для существующих WB аккаунтов
        // где wb_marketplace_token пустой, но api_key заполнен
        DB::table('marketplace_accounts')
            ->where('marketplace', 'wb')
            ->whereNull('wb_marketplace_token')
            ->whereNotNull('api_key')
            ->update(['wb_marketplace_token' => DB::raw('api_key')]);

        Log::info('WB marketplace tokens migration completed');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Не откатываем, так как это может нарушить работу существующих аккаунтов
    }
};
