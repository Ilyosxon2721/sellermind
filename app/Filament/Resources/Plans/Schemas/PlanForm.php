<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class PlanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Основная информация')
                    ->schema([
                        Grid::make(2)->schema([
                            TextInput::make('name')
                                ->label('Название тарифа')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Например: Бизнес'),
                            TextInput::make('slug')
                                ->label('Slug (URL)')
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true)
                                ->placeholder('business'),
                        ]),
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3)
                            ->columnSpanFull()
                            ->placeholder('Краткое описание тарифа для клиентов'),
                        Grid::make(3)->schema([
                            TextInput::make('price')
                                ->label('Цена')
                                ->required()
                                ->numeric()
                                ->suffix('UZS')
                                ->placeholder('890000'),
                            TextInput::make('currency')
                                ->label('Валюта')
                                ->required()
                                ->default('UZS')
                                ->maxLength(3),
                            Select::make('billing_period')
                                ->label('Период оплаты')
                                ->options([
                                    'monthly' => 'Ежемесячно',
                                    'quarterly' => 'Ежеквартально',
                                    'yearly' => 'Ежегодно',
                                ])
                                ->default('monthly')
                                ->required(),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('sort_order')
                                ->label('Порядок сортировки')
                                ->required()
                                ->numeric()
                                ->default(0),
                            Toggle::make('is_active')
                                ->label('Активен')
                                ->default(true),
                            Toggle::make('is_popular')
                                ->label('Отметить как «Популярный»')
                                ->helperText('Будет выделен на странице тарифов'),
                        ]),
                    ]),

                Section::make('Лимиты')
                    ->description('Ограничения для данного тарифа')
                    ->schema([
                        Grid::make(3)->schema([
                            TextInput::make('max_marketplace_accounts')
                                ->label('Макс. маркетплейсов')
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->minValue(1),
                            TextInput::make('max_products')
                                ->label('Макс. товаров')
                                ->required()
                                ->numeric()
                                ->default(100)
                                ->minValue(1),
                            TextInput::make('max_orders_per_month')
                                ->label('Макс. заказов/мес')
                                ->required()
                                ->numeric()
                                ->default(300)
                                ->minValue(1),
                        ]),
                        Grid::make(3)->schema([
                            TextInput::make('max_users')
                                ->label('Макс. пользователей')
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->minValue(1),
                            TextInput::make('max_warehouses')
                                ->label('Макс. складов')
                                ->required()
                                ->numeric()
                                ->default(1)
                                ->minValue(1),
                            TextInput::make('max_ai_requests')
                                ->label('Макс. AI-запросов/мес')
                                ->required()
                                ->numeric()
                                ->default(30)
                                ->minValue(0),
                        ]),
                        TextInput::make('data_retention_days')
                            ->label('Хранение данных (дней)')
                            ->required()
                            ->numeric()
                            ->default(30)
                            ->minValue(1),
                    ]),

                Section::make('Функции')
                    ->description('Доступные возможности на этом тарифе')
                    ->schema([
                        Grid::make(3)->schema([
                            Toggle::make('has_api_access')
                                ->label('API доступ'),
                            Toggle::make('has_priority_support')
                                ->label('Приоритетная поддержка'),
                            Toggle::make('has_telegram_notifications')
                                ->label('Telegram уведомления')
                                ->default(true),
                        ]),
                        Grid::make(2)->schema([
                            Toggle::make('has_auto_pricing')
                                ->label('Автоценообразование'),
                            Toggle::make('has_analytics')
                                ->label('Расширенная аналитика'),
                        ]),
                        TagsInput::make('allowed_marketplaces')
                            ->label('Доступные маркетплейсы')
                            ->placeholder('uzum, wb, ozon, yandex')
                            ->helperText('Введите slug маркетплейсов: uzum, wb, ozon, yandex'),
                        TagsInput::make('features')
                            ->label('Список возможностей')
                            ->placeholder('Добавить возможность')
                            ->helperText('Отображается на странице тарифов'),
                    ]),
            ]);
    }
}
