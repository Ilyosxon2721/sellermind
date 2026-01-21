<?php

namespace App\Filament\Resources\MarketplaceSyncLogs\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Section;
use Filament\Schemas\Schema;

class MarketplaceSyncLogForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.session_metadata'))
                    ->schema([
                        Select::make('marketplace_account_id')
                            ->label('Аккаунт')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('type')
                            ->label('Тип синхронизации')
                            ->options([
                                'products' => 'Товары',
                                'prices' => 'Цены',
                                'stocks' => 'Остатки',
                                'orders' => 'Заказы',
                                'reports' => 'Отчёты',
                            ])
                            ->required(),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'pending' => 'Ожидает',
                                'running' => 'Выполняется',
                                'success' => 'Успешно',
                                'error' => 'Ошибка',
                            ])
                            ->required(),
                    ])->columns(3),

                Section::make(__('filament.sections.time_range'))
                    ->schema([
                        DateTimePicker::make('started_at')
                            ->label('Начало'),
                        DateTimePicker::make('finished_at')
                            ->label('Конец'),
                    ])->columns(2),

                Section::make(__('filament.sections.result_data'))
                    ->schema([
                        Textarea::make('message')
                            ->label('Сообщение')
                            ->columnSpanFull(),
                        Textarea::make('request_payload')
                            ->label('Тело запроса (JSON)')
                            ->columnSpanFull(),
                        Textarea::make('response_payload')
                            ->label('Тело ответа (JSON)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
