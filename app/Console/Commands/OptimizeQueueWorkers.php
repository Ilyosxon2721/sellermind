<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAccount;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class OptimizeQueueWorkers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:optimize-workers
                            {--dry-run : Показать рекомендации без запуска воркеров}
                            {--auto : Автоматически запустить рекомендуемое количество воркеров}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Анализировать нагрузку и оптимизировать количество воркеров очереди';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🔍 Анализ нагрузки очереди...');
        $this->newLine();

        // 1. Анализируем аккаунты
        $activeAccounts = MarketplaceAccount::where('is_active', true)->count();
        $totalAccounts = MarketplaceAccount::count();

        // 2. Анализируем очередь
        $totalJobs = DB::table('jobs')->count();
        $delayedJobs = DB::table('jobs')->where('available_at', '>', time())->count();
        $readyJobs = $totalJobs - $delayedJobs;

        // 3. Проверяем воркеры
        $currentWorkers = $this->getCurrentWorkers();

        // 4. Анализируем упавшие джобы
        $failedLast24h = DB::table('failed_jobs')
            ->where('failed_at', '>', now()->subDay())
            ->count();

        // Показываем текущее состояние
        $this->table(
            ['Метрика', 'Значение'],
            [
                ['Активных аккаунтов', $activeAccounts],
                ['Джоб в очереди', $totalJobs],
                ['Готовых к выполнению', $readyJobs],
                ['Отложенных', $delayedJobs],
                ['Запущено воркеров', $currentWorkers],
                ['Упавших джоб (24ч)', $failedLast24h],
            ]
        );

        $this->newLine();

        // 5. Рассчитываем рекомендации
        $recommendation = $this->calculateRecommendation(
            $activeAccounts,
            $readyJobs,
            $failedLast24h
        );

        $this->info('🎯 Рекомендуемое количество воркеров:');
        $this->line("   Минимум: {$recommendation['min']}");
        $this->line("   Оптимально: <fg=green>{$recommendation['optimal']}</>");
        $this->line("   Максимум: {$recommendation['max']}");
        $this->newLine();

        // 6. Сравниваем с текущим
        $status = $this->getStatus($currentWorkers, $recommendation);

        if ($status['level'] === 'critical') {
            $this->error("❌ {$status['message']}");
        } elseif ($status['level'] === 'warning') {
            $this->warn("⚠️  {$status['message']}");
        } else {
            $this->info("✅ {$status['message']}");
        }

        $this->newLine();

        // 7. Автоматическая оптимизация
        if ($this->option('auto') && $currentWorkers !== $recommendation['optimal']) {
            $this->info('🔧 Автоматическая оптимизация...');
            $this->restartWorkers($recommendation['optimal']);

            return self::SUCCESS;
        }

        // 8. Dry run или предложение действий
        if ($this->option('dry-run')) {
            $this->comment('💡 Это был dry-run, воркеры не были изменены');
            $this->comment('   Для применения: php artisan queue:optimize-workers --auto');
        } elseif ($currentWorkers !== $recommendation['optimal']) {
            if ($this->confirm("Запустить {$recommendation['optimal']} воркеров?", true)) {
                $this->restartWorkers($recommendation['optimal']);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Получить количество текущих воркеров
     */
    protected function getCurrentWorkers(): int
    {
        exec('ps aux | grep "queue:work" | grep -v grep | wc -l', $output);

        return (int) trim($output[0]);
    }

    /**
     * Рассчитать рекомендуемое количество воркеров
     */
    protected function calculateRecommendation(int $accounts, int $readyJobs, int $failed): array
    {
        // Базовый расчёт: 1 воркер на аккаунт
        $base = max(2, $accounts);

        // Корректировка на основе очереди
        $queueFactor = 1.0;
        if ($readyJobs > 20) {
            $queueFactor = 1.5;
        } elseif ($readyJobs > 50) {
            $queueFactor = 2.0;
        }

        // Корректировка на основе ошибок
        $failFactor = 1.0;
        if ($failed > 5) {
            $failFactor = 1.2;
        } elseif ($failed > 20) {
            $failFactor = 1.5;
        }

        // Учёт времени суток
        $hour = (int) now()->format('H');
        $isPeak = ($hour >= 9 && $hour <= 18);
        $timeFactor = $isPeak ? 1.0 : 0.8;

        $min = max(2, (int) ceil($base * 0.8 * $timeFactor));
        $max = (int) ceil($base * $queueFactor * $failFactor);
        $optimal = (int) ceil(($min + $max) / 2);

        return [
            'min' => $min,
            'optimal' => $optimal,
            'max' => $max,
        ];
    }

    /**
     * Получить статус воркеров
     */
    protected function getStatus(int $current, array $recommendation): array
    {
        if ($current === 0) {
            return [
                'level' => 'critical',
                'message' => 'Воркеры не запущены! Запустите: ./start-queue-workers.sh '.$recommendation['optimal'],
            ];
        }

        if ($current < $recommendation['min']) {
            return [
                'level' => 'critical',
                'message' => "Недостаточно воркеров! У вас: {$current}, нужно минимум: {$recommendation['min']}",
            ];
        }

        if ($current > $recommendation['max']) {
            return [
                'level' => 'warning',
                'message' => "Слишком много воркеров. У вас: {$current}, достаточно: {$recommendation['max']}",
            ];
        }

        if ($current < $recommendation['optimal']) {
            return [
                'level' => 'warning',
                'message' => "Работает нормально. Для оптимальной производительности рекомендуется: {$recommendation['optimal']}",
            ];
        }

        return [
            'level' => 'success',
            'message' => "Отлично! У вас запущено оптимальное количество воркеров: {$current}",
        ];
    }

    /**
     * Перезапустить воркеры
     */
    protected function restartWorkers(int $count): void
    {
        $scriptPath = base_path('start-queue-workers.sh');

        if (! file_exists($scriptPath)) {
            $this->error("Скрипт {$scriptPath} не найден!");
            $this->comment('Запустите воркеры вручную: php artisan queue:work --timeout=600');

            return;
        }

        $this->info("🚀 Перезапуск воркеров ({$count})...");

        exec("bash {$scriptPath} {$count} 2>&1", $output, $returnCode);

        if ($returnCode === 0) {
            $this->info('✅ Воркеры успешно перезапущены!');
            $this->newLine();

            // Показываем вывод скрипта
            foreach ($output as $line) {
                $this->line($line);
            }
        } else {
            $this->error('❌ Ошибка при запуске воркеров');
            foreach ($output as $line) {
                $this->error($line);
            }
        }
    }
}
