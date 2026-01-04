<?php

namespace App\Filament\Resources\MarketplaceSyncLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MarketplaceSyncLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('account.name')
                    ->label('Аккаунт')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'products' => 'Товары',
                        'prices' => 'Цены',
                        'stocks' => 'Остатки',
                        'orders' => 'Заказы',
                        'reports' => 'Отчёты',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'success' => 'success',
                        'running' => 'info',
                        'pending' => 'gray',
                        'error' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'success' => 'Успешно',
                        'running' => 'Загрузка',
                        'pending' => 'Ожидание',
                        'error' => 'Ошибка',
                        default => $state,
                    }),
                TextColumn::make('started_at')
                    ->label('Начало')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('finished_at')
                    ->label('Конец')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'success' => 'Успешно',
                        'error' => 'Ошибка',
                        'running' => 'В процессе',
                    ]),
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        'products' => 'Товары',
                        'prices' => 'Цены',
                        'stocks' => 'Остатки',
                        'orders' => 'Заказы',
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
            ->defaultSort('created_at', 'desc');
    }
}
