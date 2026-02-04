<?php

namespace App\Filament\Resources\MarketplacePayouts\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MarketplacePayoutForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.general_info'))
                    ->schema([
                        Select::make('marketplace_account_id')
                            ->label('Аккаунт')
                            ->relationship('account', 'name')
                            ->required(),
                        TextInput::make('external_payout_id')
                            ->label('Внешний ID выплаты'),
                        TextInput::make('currency')
                            ->label('Валюта')
                            ->default('UZS'),
                    ])->columns(3),

                Section::make(__('filament.sections.period'))
                    ->schema([
                        DatePicker::make('period_from')
                            ->label('С'),
                        DatePicker::make('period_to')
                            ->label('По'),
                    ])->columns(2),

                Section::make(__('filament.sections.financial_metrics'))
                    ->schema([
                        TextInput::make('amount')
                            ->label('К выплате')
                            ->numeric()
                            ->required(),
                        TextInput::make('sales_amount')
                            ->label('Сумма продаж')
                            ->numeric(),
                        TextInput::make('returns_amount')
                            ->label('Сумма возвратов')
                            ->numeric(),
                        TextInput::make('commission_amount')
                            ->label('Комиссия МП')
                            ->numeric(),
                        TextInput::make('logistics_amount')
                            ->label('Логистика')
                            ->numeric(),
                        TextInput::make('storage_amount')
                            ->label('Хранение')
                            ->numeric(),
                        TextInput::make('ads_amount')
                            ->label('Реклама')
                            ->numeric(),
                        TextInput::make('penalties_amount')
                            ->label('Штрафы')
                            ->numeric(),
                    ])->columns(4),

                Section::make(__('filament.sections.raw_data'))
                    ->schema([
                        Textarea::make('raw_payload')
                            ->label('Данные ответа (JSON)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
