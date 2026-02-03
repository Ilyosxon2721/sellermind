<?php

namespace App\Filament\Resources\ProductCategories\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductCategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.basic_info'))
                    ->schema([
                        Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('parent_id')
                            ->label('Родительская категория')
                            ->relationship('parent', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('name')
                            ->label('Название')
                            ->required()
                            ->live(onBlur: true),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->unique(ignoreRecord: true),
                    ])->columns(2),

                Section::make(__('filament.sections.additional'))
                    ->schema([
                        Textarea::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                        TextInput::make('sort_order')
                            ->label('Порядок сортировки')
                            ->required()
                            ->numeric()
                            ->default(0),
                        Toggle::make('is_active')
                            ->label('Активна')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
