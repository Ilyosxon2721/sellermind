<?php

namespace App\Filament\Resources\GlobalOptions;

use App\Filament\Resources\GlobalOptions\Pages\CreateGlobalOption;
use App\Filament\Resources\GlobalOptions\Pages\EditGlobalOption;
use App\Filament\Resources\GlobalOptions\Pages\ListGlobalOptions;
use App\Filament\Resources\GlobalOptions\Schemas\GlobalOptionForm;
use App\Filament\Resources\GlobalOptions\Tables\GlobalOptionsTable;
use App\Models\GlobalOption;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GlobalOptionResource extends Resource
{
    protected static ?string $model = GlobalOption::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 1;

    public static function getModelLabel(): string
    {
        return 'Глобальная настройка';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Настройки системы';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Система';
    }

    public static function form(Schema $schema): Schema
    {
        return GlobalOptionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GlobalOptionsTable::configure($table);
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
            'index' => ListGlobalOptions::route('/'),
            'create' => CreateGlobalOption::route('/create'),
            'edit' => EditGlobalOption::route('/{record}/edit'),
        ];
    }
}
