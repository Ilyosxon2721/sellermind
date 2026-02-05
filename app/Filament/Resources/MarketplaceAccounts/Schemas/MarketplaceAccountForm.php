<?php

namespace App\Filament\Resources\MarketplaceAccounts\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MarketplaceAccountForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->relationship('user', 'name'),
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                TextInput::make('marketplace')
                    ->required(),
                TextInput::make('name'),
                Textarea::make('api_key')
                    ->columnSpanFull(),
                TextInput::make('client_id'),
                TextInput::make('client_secret'),
                TextInput::make('shop_id'),
                TextInput::make('credentials_json'),
                Toggle::make('wb_tokens_valid')
                    ->required(),
                DateTimePicker::make('wb_last_successful_call'),
                Textarea::make('credentials')
                    ->required()
                    ->columnSpanFull(),
                Toggle::make('is_active')
                    ->required(),
                DateTimePicker::make('connected_at'),
                TextInput::make('uzum_client_id'),
                Textarea::make('uzum_client_secret')
                    ->columnSpanFull(),
                Textarea::make('uzum_api_key')
                    ->columnSpanFull(),
                DateTimePicker::make('uzum_token_expires_at'),
                TextInput::make('uzum_settings'),
                TextInput::make('stock_sync_strategy')
                    ->required()
                    ->default('wb_priority'),
                TextInput::make('stock_size_strategy')
                    ->required()
                    ->default('by_total'),
            ]);
    }
}
