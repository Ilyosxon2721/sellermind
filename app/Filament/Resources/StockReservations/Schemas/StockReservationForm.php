<?php

namespace App\Filament\Resources\StockReservations\Schemas;

use App\Models\Warehouse\StockReservation;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class StockReservationForm
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
                        Select::make('warehouse_id')
                            ->label('Склад')
                            ->relationship('warehouse', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('sku_id')
                            ->label('SKU')
                            ->relationship('sku', 'sku_code')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('qty')
                            ->label('Количество')
                            ->numeric()
                            ->required()
                            ->minValue(0.001),
                    ])->columns(2),

                Section::make(__('filament.sections.status'))
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                StockReservation::STATUS_ACTIVE => 'Активен',
                                StockReservation::STATUS_RELEASED => 'Освобожден',
                                StockReservation::STATUS_CONSUMED => 'Использован',
                                StockReservation::STATUS_CANCELLED => 'Отменен',
                            ])
                            ->default(StockReservation::STATUS_ACTIVE)
                            ->required(),
                        DateTimePicker::make('expires_at')
                            ->label('Истекает')
                            ->nullable(),
                    ])->columns(2),

                Section::make(__('filament.sections.additional'))
                    ->schema([
                        Textarea::make('reason')
                            ->label('Причина резервирования')
                            ->rows(2)
                            ->columnSpanFull(),
                        TextInput::make('source_type')
                            ->label('Тип источника')
                            ->placeholder('ORDER, TRANSFER, MANUAL'),
                        TextInput::make('source_id')
                            ->label('ID источника')
                            ->numeric(),
                    ])->columns(2),
            ]);
    }
}
