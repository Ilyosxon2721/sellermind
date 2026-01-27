<?php

namespace App\Filament\Resources\AIUsageLogs;

use App\Filament\Resources\AIUsageLogs\Pages\CreateAIUsageLog;
use App\Filament\Resources\AIUsageLogs\Pages\EditAIUsageLog;
use App\Filament\Resources\AIUsageLogs\Pages\ListAIUsageLogs;
use App\Filament\Resources\AIUsageLogs\Schemas\AIUsageLogForm;
use App\Filament\Resources\AIUsageLogs\Tables\AIUsageLogsTable;
use App\Models\AIUsageLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AIUsageLogResource extends Resource
{
    protected static ?string $model = AIUsageLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCpuChip;

    protected static ?int $navigationSort = 10;

    public static function getModelLabel(): string
    {
        return __('filament.resources.ai_usage_log.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('filament.resources.ai_usage_log.plural');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('filament.nav_groups.ai_content');
    }

    public static function form(Schema $schema): Schema
    {
        return AIUsageLogForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AIUsageLogsTable::configure($table);
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
            'index' => ListAIUsageLogs::route('/'),
            'create' => CreateAIUsageLog::route('/create'),
            'edit' => EditAIUsageLog::route('/{record}/edit'),
        ];
    }
}
