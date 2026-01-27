<?php

namespace App\Filament\Widgets;

use App\Models\Warehouse\Sku;
use App\Models\Warehouse\StockLedger;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LowStockAlerts extends BaseWidget
{
    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 1;

    public function getTableHeading(): string
    {
        return 'Низкие остатки';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Sku::query()
                    ->where('is_active', true)
                    ->withSum('stockLedger as current_stock', 'qty_delta')
                    ->having('current_stock', '<=', 5)
                    ->orderBy('current_stock', 'asc')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('sku_code')
                    ->label('SKU')
                    ->searchable(),
                TextColumn::make('productVariant.name')
                    ->label('Товар')
                    ->limit(20),
                TextColumn::make('current_stock')
                    ->label('Остаток')
                    ->numeric(decimalPlaces: 0)
                    ->color(fn ($state) => $state <= 0 ? 'danger' : ($state <= 3 ? 'warning' : 'gray'))
                    ->badge(),
            ])
            ->paginated(false);
    }
}
