<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\MarketplaceAccount;

echo "=== Анализ нагрузки очереди ===\n\n";

// 1. Анализируем активные аккаунты
$activeAccounts = MarketplaceAccount::where('is_active', true)->count();
$totalAccounts = MarketplaceAccount::count();

echo "📊 Аккаунты:\n";
echo "   Всего аккаунтов: {$totalAccounts}\n";
echo "   Активных: {$activeAccounts}\n\n";

// 2. Анализируем текущую очередь
$totalJobs = DB::table('jobs')->count();
$delayedJobs = DB::table('jobs')->where('available_at', '>', time())->count();
$readyJobs = $totalJobs - $delayedJobs;

echo "📋 Текущая очередь:\n";
echo "   Всего джоб: {$totalJobs}\n";
echo "   Готовых к выполнению: {$readyJobs}\n";
echo "   Отложенных: {$delayedJobs}\n\n";

// 3. Подсчитываем джобы по типам
$jobs = DB::table('jobs')->get();
$jobTypes = [];

foreach ($jobs as $job) {
    $payload = json_decode($job->payload, true);
    $className = $payload['displayName'] ?? 'Unknown';

    // Упрощаем имя класса
    $simpleName = basename(str_replace('\\', '/', $className));

    if (!isset($jobTypes[$simpleName])) {
        $jobTypes[$simpleName] = 0;
    }
    $jobTypes[$simpleName]++;
}

if (count($jobTypes) > 0) {
    echo "📦 Джобы по типам:\n";
    arsort($jobTypes);
    foreach ($jobTypes as $type => $count) {
        echo "   {$type}: {$count}\n";
    }
    echo "\n";
}

// 4. Проверяем запущенные воркеры
exec('ps aux | grep "queue:work" | grep -v grep | wc -l', $workerOutput);
$currentWorkers = (int)trim($workerOutput[0]);

echo "⚙️  Текущие воркеры:\n";
echo "   Запущено: {$currentWorkers}\n\n";

// 5. Анализируем историю упавших джоб за последние 24 часа
$failedLast24h = DB::table('failed_jobs')
    ->where('failed_at', '>', now()->subDay())
    ->count();

$failedByType = [];
if ($failedLast24h > 0) {
    $failedJobs = DB::table('failed_jobs')
        ->where('failed_at', '>', now()->subDay())
        ->get();

    foreach ($failedJobs as $failed) {
        $payload = json_decode($failed->payload, true);
        $className = $payload['displayName'] ?? 'Unknown';
        $simpleName = basename(str_replace('\\', '/', $className));

        if (!isset($failedByType[$simpleName])) {
            $failedByType[$simpleName] = 0;
        }
        $failedByType[$simpleName]++;
    }
}

echo "❌ Упавшие джобы (за 24 часа):\n";
echo "   Всего: {$failedLast24h}\n";
if (count($failedByType) > 0) {
    echo "   По типам:\n";
    arsort($failedByType);
    foreach ($failedByType as $type => $count) {
        echo "      {$type}: {$count}\n";
    }
}
echo "\n";

// 6. Рассчитываем рекомендуемое количество воркеров
echo "=== РЕКОМЕНДАЦИИ ===\n\n";

// Базовый расчёт
$baseWorkers = $activeAccounts; // Минимум 1 воркер на аккаунт

// Корректировка на основе очереди
$queueFactor = 1.0;
if ($readyJobs > 20) {
    $queueFactor = 1.5; // Большая очередь - увеличиваем
} elseif ($readyJobs > 50) {
    $queueFactor = 2.0; // Очень большая очередь
}

// Корректировка на основе упавших джоб
$failFactor = 1.0;
if ($failedLast24h > 5) {
    $failFactor = 1.2; // Много ошибок - добавляем запас
} elseif ($failedLast24h > 20) {
    $failFactor = 1.5;
}

// Итоговая рекомендация
$recommendedMin = max(2, ceil($baseWorkers * 0.8)); // Минимум
$recommendedMax = ceil($baseWorkers * $queueFactor * $failFactor); // Максимум
$recommendedOptimal = ceil(($recommendedMin + $recommendedMax) / 2); // Оптимум

echo "💡 Расчёт на основе:\n";
echo "   - Активных аккаунтов: {$activeAccounts}\n";
echo "   - Джоб в очереди: {$readyJobs}\n";
echo "   - Упавших джоб за 24ч: {$failedLast24h}\n\n";

echo "🎯 Рекомендуемое количество воркеров:\n";
echo "   Минимум: {$recommendedMin}\n";
echo "   Оптимально: {$recommendedOptimal}\n";
echo "   Максимум: {$recommendedMax}\n\n";

// Сравнение с текущим
if ($currentWorkers < $recommendedMin) {
    echo "⚠️  СТАТУС: Недостаточно воркеров!\n";
    echo "   У вас: {$currentWorkers}, нужно минимум: {$recommendedMin}\n";
    echo "   Рекомендация: запустите ещё " . ($recommendedMin - $currentWorkers) . " воркеров\n";
    echo "   Команда: ./start-queue-workers.sh {$recommendedOptimal}\n";
} elseif ($currentWorkers > $recommendedMax) {
    echo "💰 СТАТУС: Слишком много воркеров\n";
    echo "   У вас: {$currentWorkers}, достаточно: {$recommendedMax}\n";
    echo "   Можно сократить до {$recommendedOptimal} для экономии ресурсов\n";
    echo "   Команда: ./start-queue-workers.sh {$recommendedOptimal}\n";
} elseif ($currentWorkers < $recommendedOptimal) {
    echo "✅ СТАТУС: Работает нормально, но можно улучшить\n";
    echo "   У вас: {$currentWorkers}, оптимально: {$recommendedOptimal}\n";
    echo "   Рекомендация: добавьте " . ($recommendedOptimal - $currentWorkers) . " воркеров для лучшей производительности\n";
    echo "   Команда: ./start-queue-workers.sh {$recommendedOptimal}\n";
} else {
    echo "✅ СТАТУС: Отлично!\n";
    echo "   У вас запущено оптимальное количество воркеров: {$currentWorkers}\n";
}

echo "\n";

// 7. Дополнительные рекомендации
echo "📝 Дополнительные рекомендации:\n\n";

if ($readyJobs > 30) {
    echo "⚠️  Большая очередь готовых джоб ({$readyJobs})\n";
    echo "   → Увеличьте количество воркеров или оптимизируйте джобы\n\n";
}

if ($failedLast24h > 10) {
    echo "⚠️  Много упавших джоб за последние 24 часа ({$failedLast24h})\n";
    echo "   → Проверьте логи: tail -f storage/logs/laravel.log\n";
    echo "   → Очистите упавшие: php artisan queue:flush\n\n";
}

if ($currentWorkers === 0) {
    echo "❌ Воркеры не запущены!\n";
    echo "   → Запустите: ./start-queue-workers.sh {$recommendedOptimal}\n\n";
}

$avgJobsPerAccount = $activeAccounts > 0 ? round($readyJobs / $activeAccounts, 1) : 0;
if ($avgJobsPerAccount > 10) {
    echo "⚠️  В среднем {$avgJobsPerAccount} джоб на аккаунт\n";
    echo "   → Возможно, джобы выполняются слишком долго\n";
    echo "   → Проверьте timeout и оптимизируйте код джоб\n\n";
}

echo "💡 Полезные команды:\n";
echo "   Запустить воркеры:     ./start-queue-workers.sh {$recommendedOptimal}\n";
echo "   Проверить очередь:     php test-queue-status.php\n";
echo "   Мониторинг очереди:    php artisan queue:monitor database\n";
echo "   Запустить мониторинг:  php artisan marketplace:start-monitoring\n";
echo "   Остановить мониторинг: php artisan marketplace:stop-monitoring\n";

echo "\n";

// 8. Прогноз на основе времени
echo "📈 Прогноз нагрузки:\n\n";

$now = now();
$hour = (int)$now->format('H');

// Определяем пиковые часы (обычно рабочее время)
$isPeakHours = ($hour >= 9 && $hour <= 18);

if ($isPeakHours) {
    echo "🕐 Сейчас пиковые часы ({$hour}:00)\n";
    echo "   Рекомендуется держать {$recommendedMax} воркеров\n";
} else {
    echo "🌙 Сейчас непиковые часы ({$hour}:00)\n";
    echo "   Можно сократить до {$recommendedMin} воркеров\n";
}

echo "\n";
echo "=== Анализ завершён ===\n";
