<?php

namespace App\Filament\Resources\VpcSessions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class VpcSessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.subject'))
                    ->schema([
                        Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Section::make(__('filament.sections.task_context'))
                    ->schema([
                        Select::make('agent_task_id')
                            ->label('Задача агента')
                            ->relationship('agentTask', 'title') // По модели AgentTask у нас поле title
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->label('Название сессии'),
                    ])->columns(2),

                Section::make(__('filament.sections.status_control'))
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'creating' => 'Создание',
                                'running' => 'Запущена',
                                'stopping' => 'Остановка',
                                'stopped' => 'Остановлена',
                                'failed' => 'Ошибка',
                            ])
                            ->required()
                            ->default('creating'),
                        Select::make('control_mode')
                            ->label('Режим управления')
                            ->options([
                                'AGENT_CONTROL' => 'Агент',
                                'USER_CONTROL' => 'Пользователь',
                                'MIXED' => 'Смешанный',
                            ])
                            ->required()
                            ->default('AGENT_CONTROL'),
                        TextInput::make('endpoint')
                            ->label('Точка доступа (URL)'),
                    ])->columns(3),

                Section::make(__('filament.sections.timestamps'))
                    ->schema([
                        DateTimePicker::make('started_at')
                            ->label('Запущена'),
                        DateTimePicker::make('stopped_at')
                            ->label('Остановлена'),
                        DateTimePicker::make('last_activity_at')
                            ->label('Последняя активность'),
                    ])->columns(3),
            ]);
    }
}
