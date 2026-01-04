<?php

namespace App\Filament\Resources\GlobalOptions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class GlobalOptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Настройка')
                    ->schema([
                        Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Название')
                            ->required(),
                        TextInput::make('code')
                            ->label('Код (системный)')
                            ->required(),
                        Select::make('type')
                            ->label('Тип')
                            ->options([
                                'select' => 'Выбор (Select)',
                                'text' => 'Текст',
                                'color' => 'Цвет',
                            ])
                            ->default('select')
                            ->required(),
                    ])->columns(2),

                Section::make('Отображение')
                    ->schema([
                        TextInput::make('sort_order')
                            ->label('Порядок')
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true),
                    ])->columns(2),
            ]);
    }
}
