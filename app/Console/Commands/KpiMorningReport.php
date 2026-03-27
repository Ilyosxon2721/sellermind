<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Kpi\KpiPlan;
use App\Models\User;
use App\Telegram\TelegramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Утренний KPI-отчёт в Telegram для руководителей
 *
 * Отправляет сводку: прогресс за вчера, до плана, дней до конца месяца
 */
final class KpiMorningReport extends Command
{
    protected $signature = 'kpi:morning-report {--company= : ID конкретной компании}';

    protected $description = 'Отправить утренний KPI-отчёт в Telegram';

    public function handle(TelegramService $telegram): int
    {
        $companyId = $this->option('company');

        $companies = $companyId
            ? Company::where('id', $companyId)->get()
            : Company::whereIn('id', KpiPlan::forPeriod(now()->year, now()->month)->distinct()->pluck('company_id'))->get();

        foreach ($companies as $company) {
            $this->sendReport($company, $telegram);
        }

        return self::SUCCESS;
    }

    private function sendReport(Company $company, TelegramService $telegram): void
    {
        try {
            $year = now()->year;
            $month = now()->month;
            $daysLeft = now()->endOfMonth()->day - now()->day;

            $plans = KpiPlan::byCompany($company->id)
                ->forPeriod($year, $month)
                ->where('status', '!=', KpiPlan::STATUS_CANCELLED)
                ->with(['employee', 'salesSphere'])
                ->get();

            if ($plans->isEmpty()) {
                return;
            }

            $avgAchievement = round($plans->avg('achievement_percent'), 1);
            $totalRevenue = $plans->sum('actual_revenue');
            $targetRevenue = $plans->sum('target_revenue');
            $progressPercent = $targetRevenue > 0 ? round($totalRevenue / $targetRevenue * 100, 1) : 0;

            $onTrack = $plans->where('achievement_percent', '>=', 80)->count();
            $atRisk = $plans->where('achievement_percent', '<', 50)->count();

            $monthNames = [
                1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
                5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
                9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
            ];

            $message = "📊 *Утренний KPI-отчёт*\n";
            $message .= "_{$monthNames[$month]} {$year} • {$company->name}_\n\n";

            $message .= "📈 Средний KPI: *{$avgAchievement}%*\n";
            $message .= "💰 Оборот: " . number_format($totalRevenue, 0, '.', ' ') . " / " . number_format($targetRevenue, 0, '.', ' ') . " ({$progressPercent}%)\n";
            $message .= "📋 Планов: {$plans->count()} (в плане: {$onTrack}";

            if ($atRisk > 0) {
                $message .= ", ⚠️ под угрозой: {$atRisk}";
            }
            $message .= ")\n";
            $message .= "📅 До конца месяца: *{$daysLeft} дн.*\n";

            // Топ-3 и анти-топ
            if ($plans->count() >= 3) {
                $sorted = $plans->sortByDesc('achievement_percent');

                $message .= "\n🏆 *Лидеры:*\n";
                foreach ($sorted->take(3) as $i => $plan) {
                    $name = $plan->employee?->short_name ?? 'Сотрудник #' . $plan->employee_id;
                    $sphere = $plan->salesSphere?->name ?? '';
                    $pct = round($plan->achievement_percent, 1);
                    $emoji = ['🥇', '🥈', '🥉'][$i] ?? '•';
                    $message .= "{$emoji} {$name}" . ($sphere ? " ({$sphere})" : '') . " — {$pct}%\n";
                }

                $bottom = $sorted->last();
                if ($bottom && $bottom->achievement_percent < 50) {
                    $name = $bottom->employee?->short_name ?? 'Сотрудник #' . $bottom->employee_id;
                    $pct = round($bottom->achievement_percent, 1);
                    $message .= "\n⚠️ *Под угрозой:* {$name} — {$pct}%\n";
                }
            }

            // Отправить владельцу компании
            $owner = User::where('company_id', $company->id)
                ->where('is_owner', true)
                ->first();

            if ($owner?->telegram_id) {
                $telegram->sendMessage($owner->telegram_id, $message, ['parse_mode' => 'Markdown']);
                $this->info("Отчёт отправлен: {$company->name} → {$owner->email}");
            }
        } catch (\Exception $e) {
            Log::error('KPI morning report failed', [
                'company_id' => $company->id,
                'error' => $e->getMessage(),
            ]);
            $this->error("Ошибка для {$company->name}: {$e->getMessage()}");
        }
    }
}
