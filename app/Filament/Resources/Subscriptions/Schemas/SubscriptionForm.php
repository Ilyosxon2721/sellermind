<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SubscriptionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Подписка')
                    ->schema([
                        Grid::make(2)->schema([
                            Select::make('company_id')
                                ->label('Компания')
                                ->relationship('company', 'name')
                                ->searchable()
                                ->required(),
                            Select::make('plan_id')
                                ->label('Тарифный план')
                                ->relationship('plan', 'name')
                                ->required(),
                        ]),
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активна',
                                'trial' => 'Пробный период',
                                'expired' => 'Истекла',
                                'cancelled' => 'Отменена',
                                'pending' => 'Ожидает оплаты',
                            ])
                            ->default('trial')
                            ->required(),
                    ]),

                Section::make('Период действия')
                    ->schema([
                        Grid::make(2)->schema([
                            DateTimePicker::make('starts_at')
                                ->label('Начало')
                                ->required(),
                            DateTimePicker::make('ends_at')
                                ->label('Окончание'),
                        ]),
                        Grid::make(2)->schema([
                            DateTimePicker::make('trial_ends_at')
                                ->label('Конец пробного периода'),
                            DateTimePicker::make('cancelled_at')
                                ->label('Дата отмены'),
                        ]),
                    ]),

                Section::make('Оплата')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('amount_paid')
                                ->label('Оплачено')
                                ->required()
                                ->numeric()
                                ->suffix('UZS')
                                ->default(0),
                            TextInput::make('payment_method')
                                ->label('Метод оплаты')
                                ->placeholder('click, payme, bank_transfer'),
                            TextInput::make('payment_reference')
                                ->label('Номер платежа'),
                        ]),
                    ]),

                Section::make('Использование')
                    ->description('Текущее использование ресурсов')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('current_products_count')
                                ->label('Товаров')
                                ->required()
                                ->numeric()
                                ->default(0),
                            TextInput::make('current_orders_count')
                                ->label('Заказов')
                                ->required()
                                ->numeric()
                                ->default(0),
                            TextInput::make('current_ai_requests')
                                ->label('AI-запросов')
                                ->required()
                                ->numeric()
                                ->default(0),
                        ]),
                        DateTimePicker::make('usage_reset_at')
                            ->label('Дата сброса счётчиков'),
                    ]),

                Textarea::make('notes')
                    ->label('Заметки')
                    ->rows(3)
                    ->columnSpanFull()
                    ->placeholder('Внутренние заметки по подписке'),
            ]);
    }
}
