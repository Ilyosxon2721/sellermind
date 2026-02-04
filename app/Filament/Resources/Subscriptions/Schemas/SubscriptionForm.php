<?php

namespace App\Filament\Resources\Subscriptions\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                Select::make('company_id')
                    ->relationship('company', 'name')
                    ->required(),
                Select::make('plan_id')
                    ->relationship('plan', 'name')
                    ->required(),
                Select::make('status')
                    ->options([
                        'active' => 'Active',
                        'trial' => 'Trial',
                        'expired' => 'Expired',
                        'cancelled' => 'Cancelled',
                        'pending' => 'Pending',
                    ])
                    ->default('trial')
                    ->required(),
                DateTimePicker::make('starts_at')
                    ->required(),
                DateTimePicker::make('ends_at'),
                DateTimePicker::make('trial_ends_at'),
                DateTimePicker::make('cancelled_at'),
                TextInput::make('amount_paid')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('payment_method'),
                TextInput::make('payment_reference'),
                TextInput::make('current_products_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('current_orders_count')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('current_ai_requests')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('usage_reset_at'),
                Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }
}
