<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Статус очередей ===\n\n";

// 1. Проверяем таблицу jobs
echo "1. Таблица jobs (активные задачи):\n";
try {
    $jobs = DB::table('jobs')->get();
    echo "   Всего задач в очереди: " . $jobs->count() . "\n";

    if ($jobs->count() > 0) {
        echo "\n   Детали задач:\n";
        foreach ($jobs->take(5) as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';
            echo "   - ID: {$job->id}\n";
            echo "     Класс: {$jobClass}\n";
            echo "     Очередь: {$job->queue}\n";
            echo "     Попыток: {$job->attempts}\n";
            echo "     Создана: " . date('Y-m-d H:i:s', $job->created_at) . "\n\n";
        }
    } else {
        echo "   ✅ Очередь пуста\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Ошибка: {$e->getMessage()}\n";
}

echo "\n2. Таблица failed_jobs (упавшие задачи):\n";
try {
    $failedCount = DB::table('failed_jobs')->count();
    echo "   Всего упавших задач: {$failedCount}\n";

    if ($failedCount > 0) {
        $recentFailed = DB::table('failed_jobs')
            ->orderBy('failed_at', 'desc')
            ->limit(5)
            ->get();

        echo "\n   Последние 5 упавших задач:\n";
        foreach ($recentFailed as $failed) {
            $payload = json_decode($failed->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $exceptionPreview = substr($failed->exception, 0, 200);

            echo "   - UUID: {$failed->uuid}\n";
            echo "     Класс: {$jobClass}\n";
            echo "     Очередь: {$failed->queue}\n";
            echo "     Упала: {$failed->failed_at}\n";
            echo "     Ошибка: " . str_replace("\n", " ", $exceptionPreview) . "...\n\n";
        }
    } else {
        echo "   ✅ Нет упавших задач\n";
    }
} catch (\Exception $e) {
    echo "   ❌ Ошибка: {$e->getMessage()}\n";
}

echo "\n3. Проверяем процессы queue:work:\n";
exec('ps aux | grep "queue:work" | grep -v grep', $output);
if (count($output) > 0) {
    echo "   ✅ Воркеры запущены:\n";
    foreach ($output as $line) {
        preg_match('/\s+(\d+)\s+.*php artisan queue:work(.*)/', $line, $matches);
        if (!empty($matches)) {
            echo "      PID: {$matches[1]} - php artisan queue:work{$matches[2]}\n";
        }
    }
} else {
    echo "   ⚠️  Воркеры не запущены\n";
    echo "   Запустите: php artisan queue:work\n";
}

echo "\n4. Конфигурация очереди:\n";
echo "   Connection: " . config('queue.default') . "\n";
echo "   Driver: " . config('queue.connections.database.driver') . "\n";
echo "   Table: " . config('queue.connections.database.table') . "\n";
echo "   Queue: " . config('queue.connections.database.queue') . "\n";
echo "   Retry after: " . config('queue.connections.database.retry_after') . " сек\n";

echo "\n5. Проверяем MonitorMarketplaceChangesJob:\n";
try {
    $monitoringJobs = DB::table('jobs')->get()->filter(function($job) {
        $payload = json_decode($job->payload, true);
        return strpos($payload['displayName'] ?? '', 'MonitorMarketplaceChangesJob') !== false;
    });

    echo "   Активных джоб мониторинга: " . $monitoringJobs->count() . "\n";

    $failedMonitoring = DB::table('failed_jobs')
        ->where('payload', 'like', '%MonitorMarketplaceChangesJob%')
        ->count();

    echo "   Упавших джоб мониторинга: {$failedMonitoring}\n";
} catch (\Exception $e) {
    echo "   ❌ Ошибка: {$e->getMessage()}\n";
}

echo "\n=== ВЫВОДЫ ===\n";

// Recommendations
$recommendations = [];

if ($jobs->count() > 100) {
    $recommendations[] = "⚠️  Большая очередь ({$jobs->count()} задач) - возможно, воркеры не успевают обрабатывать";
}

if ($failedCount > 10) {
    $recommendations[] = "❌ Много упавших задач ({$failedCount}) - нужно проверить ошибки";
}

if (count($output) === 0) {
    $recommendations[] = "❌ Воркеры не запущены - запустите 'php artisan queue:work'";
}

if (empty($recommendations)) {
    echo "✅ Система очередей работает нормально!\n";
} else {
    foreach ($recommendations as $rec) {
        echo "{$rec}\n";
    }
}

echo "\n";
