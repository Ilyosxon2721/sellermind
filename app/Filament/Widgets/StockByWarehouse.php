<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse\StockLedger;
use App\Models\Warehouse\Warehouse;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class StockByWarehouse extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return 'Остатки по складам';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Warehouse::query()
                    ->where('is_active', true)
                    ->withSum('stockLedger as total_stock', 'qty_delta')
                    ->withCount('stockLedger as movements_count')
                    ->orderBy('total_stock', 'desc')
            )
            ->columns([
                TextColumn::make('name')
                    ->label('Склад')
                    ->searchable(),
                TextColumn::make('code')
                    ->label('Код')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('total_stock')
                    ->label('Остаток')
                    ->numeric(decimalPlaces: 0)
                    ->color('success'),
                TextColumn::make('movements_count')
                    ->label('Операций')
                    ->numeric(),
            ])
            ->paginated(false);
    }
}
