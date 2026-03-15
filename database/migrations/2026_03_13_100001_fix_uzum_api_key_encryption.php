<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

/**
 * Исправляем api_key для Uzum аккаунтов: если токен хранится
 * в открытом виде (после миграции из uzum_api_key), шифруем его.
 */
return new class extends Migration
{
    public function up(): void
    {
        $accounts = DB::table('marketplace_accounts')
            ->where('marketplace', 'uzum')
            ->whereNotNull('api_key')
            ->get(['id', 'api_key']);

        foreach ($accounts as $account) {
            $value = $account->api_key;

            // Пробуем расшифровать — если получится, токен уже зашифрован
            try {
                Crypt::decryptString($value);
                // Успешно расшифровали — токен уже в зашифрованном виде
                continue;
            } catch (\Exception $e) {
                // Не удалось расшифровать — значит токен в открытом виде
            }

            // Шифруем и обновляем
            DB::table('marketplace_accounts')
                ->where('id', $account->id)
                ->update(['api_key' => Crypt::encryptString($value)]);
        }
    }

    public function down(): void
    {
        // Нельзя безопасно откатить — оставляем как есть
    }
};
