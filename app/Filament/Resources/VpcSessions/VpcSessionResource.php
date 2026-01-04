<?php

namespace App\Filament\Resources\VpcSessions;

use App\Filament\Resources\VpcSessions\Pages\CreateVpcSession;
use App\Filament\Resources\VpcSessions\Pages\EditVpcSession;
use App\Filament\Resources\VpcSessions\Pages\ListVpcSessions;
use App\Filament\Resources\VpcSessions\Schemas\VpcSessionForm;
use App\Filament\Resources\VpcSessions\Tables\VpcSessionsTable;
use App\Models\VpcSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class VpcSessionResource extends Resource
{
    protected static ?string $model = VpcSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedComputerDesktop;

    protected static ?int $navigationSort = 100;

    public static function getModelLabel(): string
    {
        return 'VPC сессия';
    }

    public static function getPluralModelLabel(): string
    {
        return 'VPC сессии';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Система';
    }

    public static function form(Schema $schema): Schema
    {
        return VpcSessionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return VpcSessionsTable::configure($table);
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
            'index' => ListVpcSessions::route('/'),
            'create' => CreateVpcSession::route('/create'),
            'edit' => EditVpcSession::route('/{record}/edit'),
        ];
    }
}
