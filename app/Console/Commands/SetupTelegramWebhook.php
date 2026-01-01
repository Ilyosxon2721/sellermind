<?php

namespace App\Console\Commands;

use App\Telegram\TelegramService;
use Illuminate\Console\Command;

class SetupTelegramWebhook extends Command
{
    protected $signature = 'telegram:webhook {--remove : Remove the webhook}';

    protected $description = 'Set up or remove Telegram bot webhook';

    public function handle(TelegramService $telegram): int
    {
        if ($this->option('remove')) {
            $result = $telegram->removeWebhook();

            if ($result) {
                $this->info('Webhook removed successfully!');
                return Command::SUCCESS;
            }

            $this->error('Failed to remove webhook');
            return Command::FAILURE;
        }

        $webhookUrl = config('telegram.webhook_url');

        if (empty($webhookUrl)) {
            $this->error('TELEGRAM_WEBHOOK_URL is not set in .env');
            return Command::FAILURE;
        }

        $result = $telegram->setWebhook($webhookUrl);

        if ($result) {
            $this->info("Webhook set successfully: {$webhookUrl}");
            return Command::SUCCESS;
        }

        $this->error('Failed to set webhook');
        return Command::FAILURE;
    }
}
