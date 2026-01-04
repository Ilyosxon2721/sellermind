<?php

namespace App\Filament\Resources\MarketplacePayouts\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MarketplacePayoutsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.name')
                    ->label('Аккаунт')
                    ->sortable(),
                TextColumn::make('period_to')
                    ->label('Период по')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Сумма')
                    ->money('currency')
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('sales_amount')
                    ->label('Продажи')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('commission_amount')
                    ->label('Комиссия')
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('logistics_amount')
                    ->label('Логистика')
                    ->numeric()
                    ->toggleable(),
                TextColumn::make('penalties_amount')
                    ->label('Штрафы')
                    ->numeric()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Загружена')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('marketplace_account_id')
                    ->label('Аккаунт')
                    ->relationship('account', 'name'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('period_to', 'desc');
    }
}
