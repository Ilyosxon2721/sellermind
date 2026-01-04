<?php

namespace App\Filament\Resources\Inventories\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class InventoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Параметры инвентаризации')
                    ->schema([
                        Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('warehouse_id')
                            ->label('Склад')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('number')
                            ->label('Номер акта')
                            ->placeholder('Автоматически')
                            ->disabled(),
                        DatePicker::make('date')
                            ->label('Дата проведения')
                            ->default(now())
                            ->required(),
                    ])->columns(2),

                Section::make('Статус и Тип')
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'in_progress' => 'В процессе',
                                'completed' => 'Завершена',
                                'cancelled' => 'Отменена',
                            ])
                            ->default('draft')
                            ->required(),
                        Select::make('type')
                            ->label('Тип')
                            ->options([
                                'full' => 'Полная',
                                'partial' => 'Частичная'
                            ])
                            ->default('full')
                            ->required(),
                    ])->columns(2),

                Section::make('Итоги (расчетные данные)')
                    ->schema([
                        TextInput::make('total_items')
                            ->label('Всего позиций')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('matched_items')
                            ->label('С совпадением')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('surplus_items')
                            ->label('Излишки (шт)')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('shortage_items')
                            ->label('Недостача (шт)')
                            ->numeric()
                            ->default(0)
                            ->disabled(),
                        TextInput::make('surplus_amount')
                            ->label('Сумма излишков')
                            ->numeric()
                            ->default(0.0)
                            ->disabled(),
                        TextInput::make('shortage_amount')
                            ->label('Сумма недостачи')
                            ->numeric()
                            ->default(0.0)
                            ->disabled(),
                    ])->columns(3),

                Section::make('Применение результатов')
                    ->schema([
                        Toggle::make('is_applied')
                            ->label('Результаты применены к остаткам')
                            ->disabled(),
                        DateTimePicker::make('applied_at')
                            ->label('Дата применения')
                            ->disabled(),
                    ])->columns(2),

                Section::make('Дополнительно')
                    ->schema([
                        Textarea::make('notes')
                            ->label('Заметки')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
