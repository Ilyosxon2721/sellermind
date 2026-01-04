<?php

namespace App\Filament\Widgets;

use App\Models\MarketplaceAccount;
use App\Models\Sale;
use App\Models\Subscription;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected function getStats(): array
    {
        $activeSubscriptions = Subscription::where('status', 'active')->count();
        $totalSales = Sale::sum('total_amount');
        
        return [
            Stat::make('Продажи', number_format($totalSales, 0, '.', ' ') . ' UZS')
                ->description('Общая выручка')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getSalesTrend()),

            Stat::make('Пользователи', User::count())
                ->description('Всего в системе')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),
                
            Stat::make('Активные подписки', $activeSubscriptions)
                ->description('Платные аккаунты')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),
                
            Stat::make('Подключения', MarketplaceAccount::count())
                ->description('Маркетплейсы')
                ->descriptionIcon('heroicon-m-shopping-bag')
                ->color('info'),
        ];
    }
    
    private function getSalesTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $data[] = Sale::whereDate('created_at', $date)->sum('total_amount');
        }
        return $data;
    }
}
