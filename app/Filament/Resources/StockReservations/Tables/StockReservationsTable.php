<?php

namespace App\Filament\Resources\StockReservations\Tables;

use App\Models\Warehouse\StockReservation;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockReservationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('sku.sku_code')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),
                TextColumn::make('qty')
                    ->label('Кол-во')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        StockReservation::STATUS_ACTIVE => 'warning',
                        StockReservation::STATUS_RELEASED => 'success',
                        StockReservation::STATUS_CONSUMED => 'info',
                        StockReservation::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        StockReservation::STATUS_ACTIVE => 'Активен',
                        StockReservation::STATUS_RELEASED => 'Освобожден',
                        StockReservation::STATUS_CONSUMED => 'Использован',
                        StockReservation::STATUS_CANCELLED => 'Отменен',
                        default => $state,
                    }),
                TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('source_type')
                    ->label('Источник')
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label('Истекает')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        StockReservation::STATUS_ACTIVE => 'Активен',
                        StockReservation::STATUS_RELEASED => 'Освобожден',
                        StockReservation::STATUS_CONSUMED => 'Использован',
                        StockReservation::STATUS_CANCELLED => 'Отменен',
                    ]),
                SelectFilter::make('warehouse_id')
                    ->label('Склад')
                    ->relationship('warehouse', 'name'),
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
