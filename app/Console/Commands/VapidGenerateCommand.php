<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

/**
 * Команда для генерации VAPID ключей для Web Push уведомлений
 */
final class VapidGenerateCommand extends Command
{
    /**
     * Сигнатура команды
     *
     * @var string
     */
    protected $signature = 'vapid:generate
                            {--show : Только показать ключи, не записывать в .env}
                            {--force : Перезаписать существующие ключи в .env}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Генерация VAPID ключей для Web Push уведомлений';

    /**
     * Выполнение команды
     */
    public function handle(): int
    {
        $this->info('Генерация VAPID ключей...');
        $this->newLine();

        // Генерируем ключи с помощью библиотеки web-push
        $keys = VAPID::createVapidKeys();

        $publicKey = $keys['publicKey'];
        $privateKey = $keys['privateKey'];

        if ($this->option('show')) {
            $this->displayKeys($publicKey, $privateKey);

            return self::SUCCESS;
        }

        // Проверяем существующие ключи
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            $this->error('Файл .env не найден!');

            return self::FAILURE;
        }

        $envContent = file_get_contents($envPath);

        $hasPublicKey = str_contains($envContent, 'VAPID_PUBLIC_KEY=') &&
                        ! str_contains($envContent, 'VAPID_PUBLIC_KEY='.PHP_EOL) &&
                        ! str_contains($envContent, 'VAPID_PUBLIC_KEY=""');

        $hasPrivateKey = str_contains($envContent, 'VAPID_PRIVATE_KEY=') &&
                         ! str_contains($envContent, 'VAPID_PRIVATE_KEY='.PHP_EOL) &&
                         ! str_contains($envContent, 'VAPID_PRIVATE_KEY=""');

        if (($hasPublicKey || $hasPrivateKey) && ! $this->option('force')) {
            $this->warn('VAPID ключи уже установлены в .env');
            $this->info('Используйте --force для перезаписи или --show для просмотра новых ключей');
            $this->newLine();
            $this->displayKeys($publicKey, $privateKey);

            return self::SUCCESS;
        }

        // Записываем ключи в .env
        $this->writeToEnv($envPath, $envContent, $publicKey, $privateKey);

        $this->info('VAPID ключи успешно записаны в .env');
        $this->newLine();
        $this->displayKeys($publicKey, $privateKey);

        $this->newLine();
        $this->warn('Не забудьте добавить VAPID_SUBJECT в .env если еще не добавлен:');
        $this->line('VAPID_SUBJECT=mailto:support@sellermind.uz');

        return self::SUCCESS;
    }

    /**
     * Отобразить ключи в консоли
     */
    private function displayKeys(string $publicKey, string $privateKey): void
    {
        $this->components->twoColumnDetail('<fg=green>VAPID_PUBLIC_KEY</>', $publicKey);
        $this->components->twoColumnDetail('<fg=green>VAPID_PRIVATE_KEY</>', $privateKey);
    }

    /**
     * Записать ключи в .env файл
     */
    private function writeToEnv(string $envPath, string $envContent, string $publicKey, string $privateKey): void
    {
        // Обновляем или добавляем VAPID_PUBLIC_KEY
        if (preg_match('/^VAPID_PUBLIC_KEY=.*$/m', $envContent)) {
            $envContent = preg_replace(
                '/^VAPID_PUBLIC_KEY=.*$/m',
                'VAPID_PUBLIC_KEY='.$publicKey,
                $envContent
            );
        } else {
            $envContent .= PHP_EOL.'VAPID_PUBLIC_KEY='.$publicKey;
        }

        // Обновляем или добавляем VAPID_PRIVATE_KEY
        if (preg_match('/^VAPID_PRIVATE_KEY=.*$/m', $envContent)) {
            $envContent = preg_replace(
                '/^VAPID_PRIVATE_KEY=.*$/m',
                'VAPID_PRIVATE_KEY='.$privateKey,
                $envContent
            );
        } else {
            $envContent .= PHP_EOL.'VAPID_PRIVATE_KEY='.$privateKey;
        }

        file_put_contents($envPath, $envContent);
    }
}
