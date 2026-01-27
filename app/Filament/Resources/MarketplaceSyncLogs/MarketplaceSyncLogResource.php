<?php

namespace App\Filament\Resources\MarketplaceSyncLogs;

use App\Filament\Resources\MarketplaceSyncLogs\Pages\CreateMarketplaceSyncLog;
use App\Filament\Resources\MarketplaceSyncLogs\Pages\EditMarketplaceSyncLog;
use App\Filament\Resources\MarketplaceSyncLogs\Pages\ListMarketplaceSyncLogs;
use App\Filament\Resources\MarketplaceSyncLogs\Schemas\MarketplaceSyncLogForm;
use App\Filament\Resources\MarketplaceSyncLogs\Tables\MarketplaceSyncLogsTable;
use App\Models\MarketplaceSyncLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MarketplaceSyncLogResource extends Resource
{
    protected static ?string $model = MarketplaceSyncLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowsRightLeft;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('filament.resources.marketplace_sync_log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.marketplace_sync_log.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav_groups.marketplaces');
    }

    public static function form(Schema $schema): Schema
    {
        return MarketplaceSyncLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MarketplaceSyncLogsTable::configure($table);
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
            'index' => ListMarketplaceSyncLogs::route('/'),
            'create' => CreateMarketplaceSyncLog::route('/create'),
            'edit' => EditMarketplaceSyncLog::route('/{record}/edit'),
        ];
    }
}
