<?php

namespace App\Filament\Resources\MarketplacePayouts;

use App\Filament\Resources\MarketplacePayouts\Pages\CreateMarketplacePayout;
use App\Filament\Resources\MarketplacePayouts\Pages\EditMarketplacePayout;
use App\Filament\Resources\MarketplacePayouts\Pages\ListMarketplacePayouts;
use App\Filament\Resources\MarketplacePayouts\Schemas\MarketplacePayoutForm;
use App\Filament\Resources\MarketplacePayouts\Tables\MarketplacePayoutsTable;
use App\Models\MarketplacePayout;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketplacePayoutResource extends Resource
{
    protected static ?string $model = MarketplacePayout::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCreditCard;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Выплата';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Выплаты маркетплейсов';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Финансы';
    }

    public static function form(Schema $schema): Schema
    {
        return MarketplacePayoutForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplacePayoutsTable::configure($table);
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
            'index' => ListMarketplacePayouts::route('/'),
            'create' => CreateMarketplacePayout::route('/create'),
            'edit' => EditMarketplacePayout::route('/{record}/edit'),
        ];
    }
}
