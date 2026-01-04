<?php

namespace App\Filament\Resources\GenerationTasks;

use App\Filament\Resources\GenerationTasks\Pages\CreateGenerationTask;
use App\Filament\Resources\GenerationTasks\Pages\EditGenerationTask;
use App\Filament\Resources\GenerationTasks\Pages\ListGenerationTasks;
use App\Filament\Resources\GenerationTasks\Schemas\GenerationTaskForm;
use App\Filament\Resources\GenerationTasks\Tables\GenerationTasksTable;
use App\Models\GenerationTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class GenerationTaskResource extends Resource
{
    protected static ?string $model = GenerationTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSparkles;

    protected static ?int $navigationSort = 11;

    public static function getModelLabel(): string
    {
        return 'Задача генерации';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Задачи генерации';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'ИИ и Контент';
    }

    public static function form(Schema $schema): Schema
    {
        return GenerationTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GenerationTasksTable::configure($table);
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
            'index' => ListGenerationTasks::route('/'),
            'create' => CreateGenerationTask::route('/create'),
            'edit' => EditGenerationTask::route('/{record}/edit'),
        ];
    }
}
