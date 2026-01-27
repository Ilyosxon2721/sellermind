<?php

namespace App\Filament\Resources\StockReservations;

use App\Filament\Resources\StockReservations\Pages\CreateStockReservation;
use App\Filament\Resources\StockReservations\Pages\EditStockReservation;
use App\Filament\Resources\StockReservations\Pages\ListStockReservations;
use App\Filament\Resources\StockReservations\Schemas\StockReservationForm;
use App\Filament\Resources\StockReservations\Tables\StockReservationsTable;
use App\Models\Warehouse\StockReservation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class StockReservationResource extends Resource
{
    protected static ?string $model = StockReservation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLockClosed;

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('filament.resources.stock_reservation.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.stock_reservation.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav_groups.warehouse');
    }

    public static function form(Schema $schema): Schema
    {
        return StockReservationForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return StockReservationsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListStockReservations::route('/'),
            'create' => CreateStockReservation::route('/create'),
            'edit' => EditStockReservation::route('/{record}/edit'),
        ];
    }
}
