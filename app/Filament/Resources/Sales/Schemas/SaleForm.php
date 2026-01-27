<?php

namespace App\Filament\Resources\Sales\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class SaleForm
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
                        TextInput::make('sale_number')
                            ->label('Номер продажи')
                            ->required(),
                        Select::make('type')
                            ->label('Тип')
                            ->options([
                                'marketplace' => 'Маркетплейс',
                                'manual' => 'Вручную',
                                'pos' => 'POS',
                            ])
                            ->default('manual')
                            ->required(),
                        Select::make('source')
                            ->label('Источник')
                            ->options([
                                'uzum' => 'Uzum',
                                'wb' => 'Wildberries',
                                'ozon' => 'Ozon',
                                'ym' => 'Yandex Market',
                                'manual' => 'Вручную',
                                'pos' => 'POS-терминал',
                            ]),
                    ])->columns(2),

                Section::make(__('filament.sections.order_details'))
                    ->schema([
                        Select::make('counterparty_id')
                            ->label('Контрагент')
                            ->relationship('counterparty', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('warehouse_id')
                            ->label('Склад')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload(),
                        TextInput::make('marketplace_order_id')
                            ->label('ID заказа маркетплейса')
                            ->numeric(),
                    ])->columns(3),

                Section::make(__('filament.sections.amount_payment'))
                    ->schema([
                        TextInput::make('subtotal')
                            ->label('Подытог')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('discount_amount')
                            ->label('Скидка')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('total_amount')
                            ->label('Итого')
                            ->required()
                            ->numeric()
                            ->default(0.0),
                        TextInput::make('currency')
                            ->label('Валюта')
                            ->required()
                            ->default('UZS'),
                    ])->columns(4),

                Section::make(__('filament.sections.status_dates'))
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'draft' => 'Черновик',
                                'confirmed' => 'Подтвержден',
                                'completed' => 'Выполнен',
                                'cancelled' => 'Отменен',
                            ])
                            ->default('draft')
                            ->required(),
                        DateTimePicker::make('confirmed_at')
                            ->label('Подтвержден'),
                        DateTimePicker::make('completed_at')
                            ->label('Завершен'),
                    ])->columns(3),

                Section::make(__('filament.sections.additional'))
                    ->schema([
                        Textarea::make('notes')
                            ->label('Заметки')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
