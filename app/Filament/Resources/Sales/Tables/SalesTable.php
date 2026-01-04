<?php

namespace App\Filament\Resources\Sales\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SalesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sale_number')
                    ->label('№ Заказа')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('company.name')
                    ->label('Компания')
                    ->sortable(),
                TextColumn::make('source')
                    ->label('Источник')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'uzum' => 'Uzum',
                        'wb' => 'Wildberries',
                        'ozon' => 'Ozon',
                        'ym' => 'Yandex',
                        'manual' => 'Вручную',
                        default => $state,
                    }),
                TextColumn::make('total_amount')
                    ->label('Сумма')
                    ->money('UZS')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'draft' => 'gray',
                        'confirmed' => 'info',
                        'completed' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match($state) {
                        'draft' => 'Черновик',
                        'confirmed' => 'Подтвержден',
                        'completed' => 'Готово',
                        'cancelled' => 'Отменен',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make()->label('Корзина'),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'draft' => 'Черновик',
                        'confirmed' => 'Подтвержден',
                        'completed' => 'Готово',
                        'cancelled' => 'Отменен',
                    ]),
                SelectFilter::make('source')
                    ->label('Источник')
                    ->options([
                        'uzum' => 'Uzum',
                        'wb' => 'Wildberries',
                        'ozon' => 'Ozon',
                        'ym' => 'Yandex',
                        'manual' => 'Вручную',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
