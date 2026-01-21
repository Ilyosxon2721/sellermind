<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;

class UzumUpdateToken extends Command
{
    protected $signature = 'uzum:update-token {accountId : ID аккаунта} {token : Новый API токен}';
    protected $description = 'Обновить API токен для Uzum аккаунта';

    public function handle(): int
    {
        $accountId = (int) $this->argument('accountId');
        $newToken = $this->argument('token');

        $account = MarketplaceAccount::find($accountId);

        if (!$account) {
            $this->error("Аккаунт #{$accountId} не найден");
            return self::FAILURE;
        }

        if (!$account->isUzum()) {
            $this->error("Аккаунт #{$accountId} не является Uzum");
            return self::FAILURE;
        }

        $this->info("Обновляем токен для аккаунта #{$account->id} ({$account->name})");

        // Show old token info
        $oldToken = $account->uzum_api_key;
        $this->line("Старый токен: " . ($oldToken ? substr($oldToken, 0, 20) . '...' : 'NULL'));

        // Update token (setter will encrypt automatically)
        $account->uzum_api_key = $newToken;
        $account->save();

        // Verify update
        $account->refresh();
        $verifyToken = $account->uzum_api_key;

        $this->info("Новый токен сохранён: " . substr($verifyToken, 0, 20) . '...');

        // Quick verification - tokens should match
        if ($verifyToken === $newToken) {
            $this->info("Токен успешно обновлён и верифицирован!");
        } else {
            $this->warn("Внимание: сохранённый токен отличается от введённого (возможно проблема с шифрованием)");
        }

        return self::SUCCESS;
    }
}
