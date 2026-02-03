<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StopMarketplaceMonitoring extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'marketplace:stop-monitoring
                            {--clear-failed : Также очистить упавшие джобы}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Остановить мониторинг маркетплейсов (удалить все джобы из очереди)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->warn('🛑 Остановка мониторинга маркетплейсов...');
        $this->newLine();

        // Подсчитываем джобы перед удалением
        $monitoringJobs = DB::table('jobs')->get()->filter(function ($job) {
            $payload = json_decode($job->payload, true);
            $displayName = $payload['displayName'] ?? '';

            return str_contains($displayName, 'Monitor');
        });

        $totalJobs = DB::table('jobs')->count();
        $monitoringCount = $monitoringJobs->count();

        $this->info('📊 Статистика очереди:');
        $this->line("   Всего джоб в очереди: {$totalJobs}");
        $this->line("   Джоб мониторинга: {$monitoringCount}");

        if ($monitoringCount === 0) {
            $this->info('✅ Джобы мониторинга не найдены');
        } else {
            if ($this->confirm('❓ Удалить все джобы мониторинга из очереди?', true)) {
                // Удаляем джобы мониторинга
                foreach ($monitoringJobs as $job) {
                    DB::table('jobs')->where('id', $job->id)->delete();
                }

                $this->info("✅ Удалено джоб мониторинга: {$monitoringCount}");
            } else {
                $this->info('❌ Отменено пользователем');

                return self::SUCCESS;
            }
        }

        // Очищаем упавшие джобы если указана опция
        if ($this->option('clear-failed')) {
            $this->newLine();
            $failedCount = DB::table('failed_jobs')->count();

            if ($failedCount > 0) {
                $this->line("   Упавших джоб: {$failedCount}");

                if ($this->confirm('❓ Также очистить упавшие джобы?', true)) {
                    $this->call('queue:flush');
                    $this->info('✅ Упавшие джобы очищены');
                }
            } else {
                $this->info('✅ Упавших джоб нет');
            }
        }

        $this->newLine();
        $this->comment('💡 Для повторного запуска используйте: php artisan marketplace:start-monitoring');

        return self::SUCCESS;
    }
}
