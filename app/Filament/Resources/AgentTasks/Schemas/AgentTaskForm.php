<?php

namespace App\Filament\Resources\AgentTasks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class AgentTaskForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Контекст задачи')
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
                        Select::make('agent_id')
                            ->label('Исполняющий агент')
                            ->relationship('agent', 'name')
                            ->required(),
                        Select::make('product_id')
                            ->label('Товар (если применимо)')
                            ->relationship('product', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Section::make('Описание задачи')
                    ->schema([
                        TextInput::make('title')
                            ->label('Заголовок задачи')
                            ->required(),
                        Textarea::make('description')
                            ->label('Подробное описание')
                            ->columnSpanFull(),
                        TextInput::make('type')
                            ->label('Тип задачи')
                            ->required()
                            ->default('general'),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активна',
                                'completed' => 'Завершена',
                                'failed' => 'Ошибка',
                                'cancelled' => 'Отменена',
                            ])
                            ->required()
                            ->default('active'),
                    ])->columns(2),

                Section::make('Данные исполнения')
                    ->schema([
                        Textarea::make('input_payload')
                            ->label('Входящие параметры (JSON)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
