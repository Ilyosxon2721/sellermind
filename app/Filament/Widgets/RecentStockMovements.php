<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse\StockLedger;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentStockMovements extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return 'Последние движения';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockLedger::query()
                    ->with(['sku', 'warehouse'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('occurred_at')
                    ->label('Дата')
                    ->dateTime('d.m H:i')
                    ->sortable(),
                TextColumn::make('sku.sku_code')
                    ->label('SKU')
                    ->limit(15),
                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->limit(15),
                TextColumn::make('qty_delta')
                    ->label('Кол-во')
                    ->numeric(decimalPlaces: 0)
                    ->color(fn ($state) => $state > 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state > 0 ? '+' : '') . number_format($state, 0)),
                TextColumn::make('source_type')
                    ->label('Источник')
                    ->badge()
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
