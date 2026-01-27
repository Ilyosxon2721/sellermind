<?php

namespace App\Filament\Resources\GenerationTasks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class GenerationTaskForm
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
                        TextInput::make('type')
                            ->label('Тип задачи')
                            ->required(),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'В очереди',
                                'in_progress' => 'Выполняется',
                                'done' => 'Готово',
                                'failed' => 'Ошибка'
                            ])
                            ->default('pending')
                            ->required(),
                    ])->columns(2),

                Section::make(__('filament.sections.data_progress'))
                    ->schema([
                        TextInput::make('progress')
                            ->label('Прогресс (%)')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Textarea::make('input_data')
                            ->label('Входящие данные (JSON)'),
                        Textarea::make('output_data')
                            ->label('Результат (JSON)'),
                        Textarea::make('error_message')
                            ->label('Заметка об ошибке')
                            ->columnSpanFull(),
                    ])->columns(1),
            ]);
    }
}
