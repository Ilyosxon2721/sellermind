<?php

namespace App\Filament\Resources\MarketplaceAccounts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MarketplaceAccountsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('marketplace')
                    ->searchable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('client_id')
                    ->searchable(),
                TextColumn::make('client_secret')
                    ->searchable(),
                TextColumn::make('shop_id')
                    ->searchable(),
                IconColumn::make('wb_tokens_valid')
                    ->boolean(),
                TextColumn::make('wb_last_successful_call')
                    ->dateTime()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('connected_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('uzum_client_id')
                    ->searchable(),
                TextColumn::make('uzum_token_expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('stock_sync_strategy')
                    ->searchable(),
                TextColumn::make('stock_size_strategy')
                    ->searchable(),
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
