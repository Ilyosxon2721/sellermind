<?php

namespace App\Filament\Resources\Plans\Schemas;

use Filament\Forms\Components\Select;
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
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('$'),
                TextInput::make('currency')
                    ->required()
                    ->default('UZS'),
                Select::make('billing_period')
                    ->options(['monthly' => 'Monthly', 'quarterly' => 'Quarterly', 'yearly' => 'Yearly'])
                    ->default('monthly')
                    ->required(),
                TextInput::make('max_marketplace_accounts')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('max_products')
                    ->required()
                    ->numeric()
                    ->default(200),
                TextInput::make('max_orders_per_month')
                    ->required()
                    ->numeric()
                    ->default(500),
                TextInput::make('max_users')
                    ->required()
                    ->numeric()
                    ->default(2),
                TextInput::make('max_warehouses')
                    ->required()
                    ->numeric()
                    ->default(1),
                TextInput::make('max_ai_requests')
                    ->required()
                    ->numeric()
                    ->default(100),
                TextInput::make('data_retention_days')
                    ->required()
                    ->numeric()
                    ->default(30),
                Toggle::make('has_api_access')
                    ->required(),
                Toggle::make('has_priority_support')
                    ->required(),
                Toggle::make('has_telegram_notifications')
                    ->required(),
                Toggle::make('has_auto_pricing')
                    ->required(),
                Toggle::make('has_analytics')
                    ->required(),
                TextInput::make('allowed_marketplaces'),
                TextInput::make('features'),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->required(),
                Toggle::make('is_popular')
                    ->required(),
            ]);
    }
}
