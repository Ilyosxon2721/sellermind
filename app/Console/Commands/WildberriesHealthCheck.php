<?php

// file: app/Console/Commands/WildberriesHealthCheck.php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use App\Services\Marketplaces\Wildberries\WildberriesHttpClient;
use Illuminate\Console\Command;

class WildberriesHealthCheck extends Command
{
    protected $signature = 'wb:health-check
                            {account_id : ID аккаунта Wildberries}
                            {--category= : Проверить только указанную категорию API (content, marketplace, prices, statistics)}';

    protected $description = 'Проверка подключения к Wildberries API по аккаунту';

    public function handle(): int
    {
        $accountId = (int) $this->argument('account_id');
        $specificCategory = $this->option('category');

        $account = MarketplaceAccount::find($accountId);

        if (! $account) {
            $this->error("Аккаунт с ID {$accountId} не найден");

            return self::FAILURE;
        }

        if (! $account->isWildberries()) {
            $this->error("Аккаунт #{$accountId} не является Wildberries (marketplace: {$account->marketplace})");

            return self::FAILURE;
        }

        $this->info("Проверка подключения для аккаунта: {$account->name} (ID: {$account->id})");
        $this->newLine();

        $client = new WildberriesHttpClient($account);

        $categories = $specificCategory
            ? [$specificCategory]
            : ['common', 'content', 'marketplace', 'prices', 'statistics'];

        $allSuccess = true;
        $results = [];

        foreach ($categories as $category) {
            $this->line("Проверка API категории: <comment>{$category}</comment>...");

            $startTime = microtime(true);
            $result = $client->ping($category);
            $duration = round((microtime(true) - $startTime) * 1000);

            $results[$category] = $result;

            if ($result['success']) {
                $this->info("  ✓ {$category}: OK ({$duration}ms)");
            } else {
                $this->error("  ✗ {$category}: ОШИБКА - {$result['message']}");
                $allSuccess = false;
            }
        }

        $this->newLine();

        // Summary
        if ($allSuccess) {
            $this->info('✓ Все API категории доступны');

            // Mark tokens as valid
            $account->markWbTokensValid();
        } else {
            $this->warn('⚠ Некоторые API категории недоступны');

            // Check if it's an auth error
            foreach ($results as $result) {
                if (! $result['success'] && str_contains($result['message'] ?? '', 'auth')) {
                    $this->error('Проверьте API токены в настройках аккаунта');
                    break;
                }
            }
        }

        // Token status
        $this->newLine();
        $this->line('Статус токенов:');
        $this->table(
            ['Категория', 'Токен настроен'],
            [
                ['content', $account->wb_content_token ? 'Да' : 'Нет (используется основной)'],
                ['marketplace', $account->wb_marketplace_token ? 'Да' : 'Нет (используется основной)'],
                ['prices', $account->wb_prices_token ? 'Да' : 'Нет (используется основной)'],
                ['statistics', $account->wb_statistics_token ? 'Да' : 'Нет (используется основной)'],
                ['Основной API ключ', $account->api_key ? 'Да' : 'Нет'],
            ]
        );

        return $allSuccess ? self::SUCCESS : self::FAILURE;
    }
}
