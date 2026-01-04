<?php

namespace App\Filament\Resources\MarketplaceAccountIssues;

use App\Filament\Resources\MarketplaceAccountIssues\Pages\CreateMarketplaceAccountIssue;
use App\Filament\Resources\MarketplaceAccountIssues\Pages\EditMarketplaceAccountIssue;
use App\Filament\Resources\MarketplaceAccountIssues\Pages\ListMarketplaceAccountIssues;
use App\Filament\Resources\MarketplaceAccountIssues\Schemas\MarketplaceAccountIssueForm;
use App\Filament\Resources\MarketplaceAccountIssues\Tables\MarketplaceAccountIssuesTable;
use App\Models\MarketplaceAccountIssue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketplaceAccountIssueResource extends Resource
{
    protected static ?string $model = MarketplaceAccountIssue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;

    protected static ?int $navigationSort = 11;

    public static function getModelLabel(): string
    {
        return 'Проблема аккаунта';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Проблемы аккаунтов';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Маркетплейсы';
    }

    public static function form(Schema $schema): Schema
    {
        return MarketplaceAccountIssueForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplaceAccountIssuesTable::configure($table);
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
            'index' => ListMarketplaceAccountIssues::route('/'),
            'create' => CreateMarketplaceAccountIssue::route('/create'),
            'edit' => EditMarketplaceAccountIssue::route('/{record}/edit'),
        ];
    }
}
