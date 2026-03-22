<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Finance\Employee;
use App\Models\Kpi\SalesSphere;
use App\Services\Kpi\KpiAiService;
use Illuminate\Console\Command;

final class TestKpiAi extends Command
{
    protected $signature = 'kpi:test-ai
                            {--company-id=1 : ID компании}
                            {--employee-id= : ID сотрудника (опционально)}
                            {--sphere-id= : ID сферы продаж (опционально)}';

    protected $description = 'Тестирование ИИ-рекомендаций для KPI';

    public function handle(KpiAiService $aiService): int
    {
        $companyId = (int) $this->option('company-id');

        $this->info('🤖 Тестирование GPT-5.1 для KPI-рекомендаций');
        $this->newLine();

        // Найти сотрудника
        if ($this->option('employee-id')) {
            $employee = Employee::find((int) $this->option('employee-id'));
        } else {
            $employee = Employee::where('company_id', $companyId)->first();
        }

        if (! $employee) {
            $this->error('❌ Сотрудник не найден!');
            $this->info('Создай сотрудника в разделе "Финансы → Сотрудники"');

            return 1;
        }

        // Найти сферу продаж
        if ($this->option('sphere-id')) {
            $sphere = SalesSphere::find((int) $this->option('sphere-id'));
        } else {
            $sphere = SalesSphere::byCompany($companyId)->first();
        }

        if (! $sphere) {
            $this->error('❌ Сфера продаж не найдена!');
            $this->info('Создай сферу продаж в разделе "KPI → Сферы продаж"');

            return 1;
        }

        $this->info("👤 Сотрудник: {$employee->name}");
        $this->info("📊 Сфера продаж: {$sphere->name}");
        $this->info('📅 Период: '.now()->addMonth()->format('F Y'));
        $this->newLine();

        $this->info('⏳ Отправка запроса к GPT-5.1...');
        $startTime = microtime(true);

        try {
            $suggestion = $aiService->suggestPlan(
                $companyId,
                1, // user_id (для теста)
                $employee->id,
                $sphere->id,
                now()->addMonth()->year,
                now()->addMonth()->month
            );

            $duration = round((microtime(true) - $startTime) * 1000);

            $this->newLine();
            $this->info("✅ Ответ получен за {$duration}ms");
            $this->newLine();

            // Вывод результатов
            $this->table(
                ['Метрика', 'Значение'],
                [
                    ['Целевой оборот', number_format($suggestion['target_revenue'], 0, '.', ' ').' сум'],
                    ['Целевая маржа', number_format($suggestion['target_margin'], 0, '.', ' ').' сум'],
                    ['Целевые заказы', $suggestion['target_orders']],
                    ['Вес оборота', $suggestion['weight_revenue'].'%'],
                    ['Вес маржи', $suggestion['weight_margin'].'%'],
                    ['Вес заказов', $suggestion['weight_orders'].'%'],
                ]
            );

            $this->newLine();
            $this->info('💡 Обоснование ИИ:');
            $this->line('   '.$suggestion['reasoning']);
            $this->newLine();

            $this->info('✅ GPT-5.1 работает отлично!');

            return 0;

        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Ошибка: '.$e->getMessage());
            $this->newLine();

            $this->warn('💡 Проверь:');
            $this->line('   1. OPENAI_API_KEY установлен в .env');
            $this->line('   2. API ключ валидный');
            $this->line('   3. Есть доступ к модели gpt-5.1');
            $this->newLine();

            return 1;
        }
    }
}
