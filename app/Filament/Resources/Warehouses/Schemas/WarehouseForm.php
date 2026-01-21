<?php

namespace App\Filament\Resources\Warehouses\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class WarehouseForm
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
                        TextInput::make('name')
                            ->label('Название склада')
                            ->required(),
                        TextInput::make('code')
                            ->label('Код склада')
                            ->placeholder('Например: MSK-01'),
                        TextInput::make('group_name')
                            ->label('Группа складов')
                            ->placeholder('Региональные'),
                    ])->columns(2),

                Section::make(__('filament.sections.address_contacts'))
                    ->schema([
                        TextInput::make('address')
                            ->label('Адрес'),
                        TextInput::make('address_comment')
                            ->label('Комментарий к адресу'),
                        Textarea::make('comment')
                            ->label('Заметки')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('filament.sections.system_settings'))
                    ->schema([
                        TextInput::make('external_code')
                            ->label('Внешний код (ERP/1C)'),
                        Toggle::make('is_default')
                            ->label('Склад по умолчанию')
                            ->default(false),
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ])->columns(3),
            ]);
    }
}
