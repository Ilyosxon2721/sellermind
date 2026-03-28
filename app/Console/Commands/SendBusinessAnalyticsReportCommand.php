<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\BusinessAnalyticsService;
use App\Services\CurrencyConversionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Отправка еженедельного отчёта бизнес-аналитики (ABC) в Telegram.
 */
final class SendBusinessAnalyticsReportCommand extends Command
{
    protected $signature = 'business-analytics:report
        {--company= : ID компании (если не указан — для всех)}
        {--period=30days : Период анализа}';

    protected $description = 'Отправить отчёт ABC-анализа в Telegram';

    public function handle(BusinessAnalyticsService $service, CurrencyConversionService $currencyService): int
    {
        $botToken = config('services.telegram.bot_token');
        if (!$botToken) {
            $this->error('Telegram bot token не настроен');
            return self::FAILURE;
        }

        $companyId = $this->option('company');
        $period = $this->option('period');

        $companies = $companyId
            ? Company::where('id', $companyId)->where('is_active', true)->get()
            : Company::where('is_active', true)->get();

        foreach ($companies as $company) {
            try {
                $currencyService->forCompany($company);

                // Берём из кэша или рассчитываем
                $cacheKey = "business_abc_{$company->id}_{$period}";
                $abcData = Cache::get($cacheKey) ?? $service->getAbcAnalysis($company->id, $period);

                $this->sendReport($company, $abcData, $botToken);
                $this->info("Отчёт отправлен для компании: {$company->name}");
            } catch (\Exception $e) {
                $this->error("Ошибка для компании {$company->id}: {$e->getMessage()}");
                Log::error("BusinessAnalytics report failed for company {$company->id}", [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return self::SUCCESS;
    }

    /**
     * Отправить отчёт в Telegram
     */
    private function sendReport(Company $company, array $abcData, string $botToken): void
    {
        // Найти пользователей с Telegram
        $users = $company->users()
            ->where('telegram_notifications_enabled', true)
            ->whereNotNull('telegram_id')
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        $message = $this->formatReport($company, $abcData);

        foreach ($users as $user) {
            Http::post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $user->telegram_id,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);
        }
    }

    /**
     * Форматировать отчёт для Telegram
     */
    private function formatReport(Company $company, array $abcData): string
    {
        $summary = $abcData['summary'] ?? [];
        $categories = $summary['categories'] ?? [];

        $catA = $categories['A'] ?? ['count' => 0, 'revenue' => 0, 'percentage' => 0];
        $catB = $categories['B'] ?? ['count' => 0, 'revenue' => 0, 'percentage' => 0];
        $catC = $categories['C'] ?? ['count' => 0, 'revenue' => 0, 'percentage' => 0];

        $totalProducts = $summary['total_products'] ?? 0;
        $totalRevenue = number_format($summary['total_revenue'] ?? 0, 0, '.', ' ');

        $lines = [
            "<b>📊 ABC-анализ: {$company->name}</b>",
            "",
            "📦 Товаров: <b>{$totalProducts}</b>",
            "💰 Выручка: <b>{$totalRevenue} сум</b>",
            "",
            "🟢 <b>A</b> — {$catA['count']} товаров ({$catA['percentage']}% выручки)",
            "🟡 <b>B</b> — {$catB['count']} товаров ({$catB['percentage']}% выручки)",
            "🔴 <b>C</b> — {$catC['count']} товаров ({$catC['percentage']}% выручки)",
        ];

        // Топ-5 товаров категории A
        $topProducts = array_slice($abcData['products'] ?? [], 0, 5);
        if (!empty($topProducts)) {
            $lines[] = "";
            $lines[] = "<b>🏆 Топ-5 лидеров (A):</b>";
            foreach ($topProducts as $i => $product) {
                $rev = number_format($product['revenue'] ?? 0, 0, '.', ' ');
                $name = mb_substr($product['product_name'] ?? '—', 0, 30);
                $lines[] = ($i + 1) . ". {$name} — {$rev} сум";
            }
        }

        // Товары категории C, требующие внимания
        $cProducts = array_filter($abcData['products'] ?? [], fn($p) => ($p['category'] ?? '') === 'C');
        $cCount = count($cProducts);
        if ($cCount > 0) {
            $lines[] = "";
            $lines[] = "⚠️ <b>{$cCount} товаров</b> в категории C — рассмотрите оптимизацию ассортимента";
        }

        $lines[] = "";
        $lines[] = "🔗 <a href=\"https://sellermind.uz/business-analytics\">Открыть аналитику</a>";

        return implode("\n", $lines);
    }
}
