<?php

namespace App\Filament\Resources\AgentTasks;

use App\Filament\Resources\AgentTasks\Pages\CreateAgentTask;
use App\Filament\Resources\AgentTasks\Pages\EditAgentTask;
use App\Filament\Resources\AgentTasks\Pages\ListAgentTasks;
use App\Filament\Resources\AgentTasks\Schemas\AgentTaskForm;
use App\Filament\Resources\AgentTasks\Tables\AgentTasksTable;
use App\Models\AgentTask;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AgentTaskResource extends Resource
{
    protected static ?string $model = AgentTask::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQueueList;

    protected static ?int $navigationSort = 2;

    public static function getModelLabel(): string
    {
        return 'Задача агента';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Задачи агентов';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'ИИ и Контент';
    }

    public static function form(Schema $schema): Schema
    {
        return AgentTaskForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AgentTasksTable::configure($table);
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
            'index' => ListAgentTasks::route('/'),
            'create' => CreateAgentTask::route('/create'),
            'edit' => EditAgentTask::route('/{record}/edit'),
        ];
    }
}
