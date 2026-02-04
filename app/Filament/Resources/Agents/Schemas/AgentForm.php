<?php

namespace App\Filament\Resources\Agents\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class AgentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.basic_info'))
                    ->schema([
                        TextInput::make('name')
                            ->label('Имя агента')
                            ->required(),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->required(),
                        TextInput::make('model')
                            ->label('Используемая модель')
                            ->required()
                            ->default('gpt-4o-mini'),
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->required(),
                    ])->columns(2),

                Section::make(__('filament.sections.behavior_config'))
                    ->schema([
                        Textarea::make('description')
                            ->label('Описание для пользователей')
                            ->columnSpanFull(),
                        Textarea::make('system_prompt')
                            ->label('Системный промпт (Инструкции)')
                            ->required()
                            ->rows(10)
                            ->columnSpanFull(),
                        TextInput::make('enabled_tools')
                            ->label('Доступные инструменты (Comma separated)')
                            ->placeholder('search,calculator,browser'),
                    ]),
            ]);
    }
}
