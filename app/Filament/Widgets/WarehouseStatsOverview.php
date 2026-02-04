<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse\InventoryDocument;
use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\StockReservation;
use App\Models\Warehouse\Warehouse;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WarehouseStatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        // Total warehouses
        $warehousesCount = Warehouse::where('is_active', true)->count();

        // Total SKUs
        $skusCount = Sku::where('is_active', true)->count();

        // Total stock (sum of qty_delta from ledger)
        $totalStock = StockLedger::sum('qty_delta');

        // Active reservations count
        $activeReservations = StockReservation::where('status', StockReservation::STATUS_ACTIVE)->count();

        // Reserved quantity
        $reservedQty = StockReservation::where('status', StockReservation::STATUS_ACTIVE)->sum('qty');

        // Documents today
        $documentsToday = InventoryDocument::whereDate('created_at', today())->count();

        // Stock movements this week
        $movementsThisWeek = StockLedger::where('created_at', '>=', now()->subDays(7))->count();

        return [
            Stat::make('Склады', $warehousesCount)
                ->description('Активных складов')
                ->descriptionIcon('heroicon-m-home-modern')
                ->color('primary'),

            Stat::make('SKU', $skusCount)
                ->description('Всего позиций')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),

            Stat::make('Остаток', number_format($totalStock, 0, '.', ' '))
                ->description('Единиц на складах')
                ->descriptionIcon('heroicon-m-archive-box')
                ->color('info')
                ->chart($this->getStockTrend()),

            Stat::make('Резервы', $activeReservations)
                ->description(number_format($reservedQty, 0, '.', ' ').' ед. зарезервировано')
                ->descriptionIcon('heroicon-m-lock-closed')
                ->color('warning'),

            Stat::make('Документы', $documentsToday)
                ->description('За сегодня')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('gray'),

            Stat::make('Движения', $movementsThisWeek)
                ->description('За 7 дней')
                ->descriptionIcon('heroicon-m-arrows-right-left')
                ->color('success')
                ->chart($this->getMovementsTrend()),
        ];
    }

    private function getStockTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            // Cumulative stock up to that date
            $data[] = (float) StockLedger::whereDate('created_at', '<=', $date)->sum('qty_delta');
        }

        return $data;
    }

    private function getMovementsTrend(): array
    {
        $data = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $data[] = StockLedger::whereDate('created_at', $date)->count();
        }

        return $data;
    }
}
