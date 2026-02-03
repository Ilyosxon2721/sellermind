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
            Stat::make(__('filament.stats.sales'), number_format($totalSales, 0, '.', ' ').' UZS')
                ->description(__('filament.stats.total_revenue'))
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->chart($this->getSalesTrend()),

            Stat::make(__('filament.stats.users'), User::count())
                ->description(__('filament.stats.total_in_system'))
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            Stat::make(__('filament.stats.active_subscriptions'), $activeSubscriptions)
                ->description(__('filament.stats.paid_accounts'))
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning'),

            Stat::make(__('filament.stats.connections'), MarketplaceAccount::count())
                ->description(__('filament.stats.marketplaces'))
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
