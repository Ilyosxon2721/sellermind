<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\LowStockAlerts;
use App\Filament\Widgets\RecentStockMovements;
use App\Filament\Widgets\StockByWarehouse;
use App\Filament\Widgets\WarehouseStatsOverview;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class WarehouseDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected string $view = 'filament.pages.warehouse-dashboard';

    protected static ?int $navigationSort = 0;

    public static function getNavigationLabel(): string
    {
        return __('filament.pages.warehouse_dashboard.label');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav_groups.warehouse');
    }

    public function getTitle(): string
    {
        return __('filament.pages.warehouse_dashboard.title');
    }

    public function getHeaderWidgets(): array
    {
        return [
            WarehouseStatsOverview::class,
        ];
    }

    public function getFooterWidgets(): array
    {
        return [
            LowStockAlerts::class,
            RecentStockMovements::class,
            StockByWarehouse::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    public function getFooterWidgetsColumns(): int|array
    {
        return [
            'default' => 1,
            'md' => 2,
            'xl' => 3,
        ];
    }
}
