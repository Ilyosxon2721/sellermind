<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('slug')
                    ->searchable(),
                TextColumn::make('price')
                    ->money()
                    ->sortable(),
                TextColumn::make('currency')
                    ->searchable(),
                TextColumn::make('billing_period'),
                TextColumn::make('max_marketplace_accounts')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_products')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_orders_per_month')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_users')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_warehouses')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_ai_requests')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('data_retention_days')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('has_api_access')
                    ->boolean(),
                IconColumn::make('has_priority_support')
                    ->boolean(),
                IconColumn::make('has_telegram_notifications')
                    ->boolean(),
                IconColumn::make('has_auto_pricing')
                    ->boolean(),
                IconColumn::make('has_analytics')
                    ->boolean(),
                TextColumn::make('sort_order')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                IconColumn::make('is_popular')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
