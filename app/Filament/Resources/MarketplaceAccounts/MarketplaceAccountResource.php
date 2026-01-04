<?php

namespace App\Filament\Resources\MarketplaceAccounts;

use App\Filament\Resources\MarketplaceAccounts\Pages\CreateMarketplaceAccount;
use App\Filament\Resources\MarketplaceAccounts\Pages\EditMarketplaceAccount;
use App\Filament\Resources\MarketplaceAccounts\Pages\ListMarketplaceAccounts;
use App\Filament\Resources\MarketplaceAccounts\Schemas\MarketplaceAccountForm;
use App\Filament\Resources\MarketplaceAccounts\Tables\MarketplaceAccountsTable;
use App\Models\MarketplaceAccount;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketplaceAccountResource extends Resource
{
    protected static ?string $model = MarketplaceAccount::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Аккаунт маркетплейса';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Аккаунты маркетплейсов';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Маркетплейсы';
    }

    public static function form(Schema $schema): Schema
    {
        return MarketplaceAccountForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplaceAccountsTable::configure($table);
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
            'index' => ListMarketplaceAccounts::route('/'),
            'create' => CreateMarketplaceAccount::route('/create'),
            'edit' => EditMarketplaceAccount::route('/{record}/edit'),
        ];
    }
}
