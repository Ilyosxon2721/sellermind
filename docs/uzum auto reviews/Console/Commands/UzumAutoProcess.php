<?php

namespace App\Console\Commands;

use App\Jobs\AutoConfirmFbsOrders;
use App\Jobs\AutoReplyReviews;
use Illuminate\Console\Command;

class UzumAutoProcess extends Command
{
    protected $signature = 'uzum:auto-process
        {--confirm : Авто-подтверждение заказов}
        {--reviews : Авто-ответ на отзывы}
        {--all : Всё сразу}';

    protected $description = 'Автоматическая обработка заказов и отзывов Uzum Market';

    public function handle(): int
    {
        $runAll = $this->option('all');

        if ($runAll || $this->option('confirm')) {
            $this->info('🚀 Запуск авто-подтверждения заказов...');
            AutoConfirmFbsOrders::dispatch();
            $this->info('✅ Job AutoConfirmFbsOrders добавлен в очередь');
        }

        if ($runAll || $this->option('reviews')) {
            $this->info('🤖 Запуск авто-ответа на отзывы...');
            AutoReplyReviews::dispatch();
            $this->info('✅ Job AutoReplyReviews добавлен в очередь');
        }

        if (! $runAll && ! $this->option('confirm') && ! $this->option('reviews')) {
            $this->warn('Укажи опцию: --confirm, --reviews или --all');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
