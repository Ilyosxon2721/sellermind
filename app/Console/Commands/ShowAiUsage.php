<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AIUsageLog;
use Illuminate\Console\Command;

final class ShowAiUsage extends Command
{
    protected $signature = 'ai:usage
                            {--company-id=1 : ID компании}
                            {--limit=10 : Количество записей}
                            {--model= : Фильтр по модели}';

    protected $description = 'Показать логи использования AI (токены, стоимость)';

    public function handle(): int
    {
        $companyId = (int) $this->option('company-id');
        $limit = (int) $this->option('limit');

        $this->info('📊 Логи использования AI');
        $this->newLine();

        $query = AIUsageLog::where('company_id', $companyId)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->limit($limit);

        if ($this->option('model')) {
            $query->where('model', 'like', '%'.$this->option('model').'%');
        }

        $logs = $query->get();

        if ($logs->isEmpty()) {
            $this->warn('⚠️  Нет логов использования AI');
            $this->info('💡 Используй ИИ-рекомендацию в KPI для создания логов');

            return 0;
        }

        // Статистика
        $totalTokensIn = $logs->sum('tokens_input');
        $totalTokensOut = $logs->sum('tokens_output');
        $totalCost = $logs->sum('cost_estimated');

        $this->table(
            ['Дата', 'Модель', 'Пользователь', 'Токены IN', 'Токены OUT', 'Стоимость'],
            $logs->map(fn ($log) => [
                $log->created_at->format('Y-m-d H:i:s'),
                $log->model,
                $log->user?->name ?? 'N/A',
                number_format($log->tokens_input),
                number_format($log->tokens_output),
                '$'.number_format((float) $log->cost_estimated, 4),
            ])->toArray()
        );

        $this->newLine();
        $this->info('📈 Итого за период:');
        $this->line('   Запросов: '.$logs->count());
        $this->line('   Токенов входящих: '.number_format($totalTokensIn));
        $this->line('   Токенов исходящих: '.number_format($totalTokensOut));
        $this->line('   Всего токенов: '.number_format($totalTokensIn + $totalTokensOut));
        $this->line('   Общая стоимость: $'.number_format((float) $totalCost, 4));
        $this->newLine();

        return 0;
    }
}
