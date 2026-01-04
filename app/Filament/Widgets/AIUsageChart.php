<?php

namespace App\Filament\Widgets;

use App\Models\AIUsageLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class AIUsageChart extends ChartWidget
{
    protected ?string $heading = 'Использование ИИ (токены)';
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'md';

    protected function getData(): array
    {
        $data = [];
        $labels = [];

        for ($i = 14; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d.m');
            
            // Суммируем входные и выходные токены
            $usage = AIUsageLog::whereDate('created_at', $date->toDateString())
                ->select(DB::raw('SUM(tokens_input + tokens_output) as total'))
                ->first();
                
            $data[] = $usage->total ?? 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Токены',
                    'data' => $data,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
