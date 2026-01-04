<?php

namespace App\Filament\Resources\MarketplaceAccountIssues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MarketplaceAccountIssuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.name')
                    ->label('Аккаунт')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label('Компания')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Проблема')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('severity')
                    ->label('Важность')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'critical' => 'danger',
                        'warning' => 'warning',
                        'info' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'critical' => 'Критично',
                        'warning' => 'Важно',
                        'info' => 'Инфо',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'danger',
                        'resolved' => 'success',
                        'ignored' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Активна',
                        'resolved' => 'Решена',
                        'ignored' => 'Скрыта',
                        default => $state,
                    }),
                TextColumn::make('occurrences')
                    ->label('Раз')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('last_occurred_at')
                    ->label('Последний раз')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активна',
                        'resolved' => 'Решена',
                    ]),
                SelectFilter::make('severity')
                    ->label('Важность')
                    ->options([
                        'critical' => 'Критично',
                        'warning' => 'Важно',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('last_occurred_at', 'desc');
    }
}
