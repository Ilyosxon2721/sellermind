<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
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
                        TextInput::make('article')
                            ->label('Артикул')
                            ->required(),
                        TextInput::make('brand_name')
                            ->label('Бренд'),
                        Select::make('category_id')
                            ->label('Категория')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(2),

                Section::make('Описание')
                    ->schema([
                        Textarea::make('description_short')
                            ->label('Краткое описание')
                            ->columnSpanFull(),
                        Textarea::make('description_full')
                            ->label('Полное описание')
                            ->columnSpanFull(),
                    ]),

                Section::make('Характеристики')
                    ->schema([
                        TextInput::make('country_of_origin')
                            ->label('Страна происхождения'),
                        TextInput::make('manufacturer')
                            ->label('Производитель'),
                        TextInput::make('unit')
                            ->label('Ед. измерения'),
                        Textarea::make('composition')
                            ->label('Состав')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make('Габариты упаковки')
                    ->schema([
                        TextInput::make('package_weight_g')
                            ->label('Вес (г)')
                            ->numeric(),
                        TextInput::make('package_length_mm')
                            ->label('Длина (мм)')
                            ->numeric(),
                        TextInput::make('package_width_mm')
                            ->label('Ширина (мм)')
                            ->numeric(),
                        TextInput::make('package_height_mm')
                            ->label('Высота (мм)')
                            ->numeric(),
                    ])->columns(4),

                Section::make('Статус')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true)
                            ->required(),
                        Toggle::make('is_archived')
                            ->label('В архиве')
                            ->default(false)
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
