<?php

namespace App\Filament\Resources\MarketplaceAccountIssues\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MarketplaceAccountIssueForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.issue_object'))
                    ->schema([
                        Select::make('marketplace_account_id')
                            ->label('Аккаунт')
                            ->relationship('account', 'name')
                            ->required(),
                        Select::make('company_id')
                            ->label('Компания')
                            ->relationship('company', 'name')
                            ->required(),
                    ])->columns(2),

                Section::make(__('filament.sections.error_details'))
                    ->schema([
                        Select::make('type')
                            ->label('Тип проблемы')
                            ->options([
                                'token_invalid' => 'Неверный токен',
                                'token_expired' => 'Токен истек',
                                'insufficient_permissions' => 'Мало прав',
                                'shop_access_denied' => 'Доступ запрещен',
                                'api_error' => 'Ошибка API',
                                'rate_limit' => 'Лимит запросов',
                                'sync_failed' => 'Сбой синхронизации',
                                'connection_failed' => 'Ошибка соединения',
                            ])
                            ->required(),
                        Select::make('severity')
                            ->label('Уровень')
                            ->options([
                                'critical' => 'Критическая',
                                'warning' => 'Предупреждение',
                                'info' => 'Инфо',
                            ])
                            ->required(),
                        TextInput::make('title')
                            ->label('Заголовок')
                            ->required(),
                        Textarea::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                    ])->columns(3),

                Section::make(__('filament.sections.technical_data'))
                    ->schema([
                        TextInput::make('http_status')
                            ->label('HTTP Статус')
                            ->numeric(),
                        TextInput::make('error_code')
                            ->label('Код ошибки'),
                        Textarea::make('error_details')
                            ->label('Детали (JSON/Text)')
                            ->columnSpanFull(),
                    ])->columns(2),

                Section::make(__('filament.sections.status_frequency'))
                    ->schema([
                        Select::make('status')
                            ->label('Статус')
                            ->options([
                                'active' => 'Активна',
                                'resolved' => 'Решена',
                                'ignored' => 'Игнорируется',
                            ])
                            ->required(),
                        TextInput::make('occurrences')
                            ->label('Повторений')
                            ->numeric(),
                        DateTimePicker::make('last_occurred_at')
                            ->label('Последний раз'),
                        DateTimePicker::make('resolved_at')
                            ->label('Решена в'),
                    ])->columns(4),
            ]);
    }
}
