<?php

namespace App\Filament\Resources\MarketplaceAutomationRules\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MarketplaceAutomationRuleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('filament.sections.rule_logic'))
                    ->schema([
                        Select::make('marketplace_account_id')
                            ->label('Аккаунт')
                            ->relationship('account', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('name')
                            ->label('Название правила')
                            ->required(),
                        TextInput::make('event_type')
                            ->label('Тип события')
                            ->required()
                            ->placeholder('Например: order_created'),
                        TextInput::make('action_type')
                            ->label('Тип действия')
                            ->required()
                            ->placeholder('Например: send_telegram'),
                    ])->columns(2),

                Section::make(__('filament.sections.params_conditions'))
                    ->schema([
                        Textarea::make('conditions_json')
                            ->label('Условия (JSON)')
                            ->columnSpanFull(),
                        Textarea::make('action_params_json')
                            ->label('Параметры действия (JSON)')
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Активно')
                            ->default(true)
                            ->required(),
                    ]),
            ]);
    }
}
