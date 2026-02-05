<?php

namespace App\Filament\Resources\AIUsageLogs\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AIUsageLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.general_info'))
                    ->schema([
                        Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('user_id')
                            ->label('Пользователь')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('model')
                            ->label('Модель ИИ')
                            ->required(),
                    ])->columns(3),

                Section::make(__('filament.sections.resource_consumption'))
                    ->schema([
                        TextInput::make('tokens_input')
                            ->label('Входящие токены')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('tokens_output')
                            ->label('Исходящие токены')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('images_generated')
                            ->label('Сгенерировано изображений')
                            ->required()
                            ->numeric()
                            ->default(0),
                        TextInput::make('cost_estimated')
                            ->label('Оценочная стоимость ($)')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                    ])->columns(2),

                Section::make(__('filament.sections.additional'))
                    ->schema([
                        TextInput::make('meta')
                            ->label('Метаданные (JSON)'),
                    ]),
            ]);
    }
}
