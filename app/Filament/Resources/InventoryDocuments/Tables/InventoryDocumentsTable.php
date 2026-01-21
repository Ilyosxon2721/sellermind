<?php

namespace App\Filament\Resources\InventoryDocuments\Tables;

use App\Models\Warehouse\InventoryDocument;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InventoryDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('doc_no')
                    ->label('№ документа')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        InventoryDocument::TYPE_IN => 'success',
                        InventoryDocument::TYPE_OUT => 'danger',
                        InventoryDocument::TYPE_MOVE => 'info',
                        InventoryDocument::TYPE_WRITE_OFF => 'warning',
                        InventoryDocument::TYPE_INVENTORY => 'primary',
                        InventoryDocument::TYPE_REVERSAL => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        InventoryDocument::TYPE_IN => 'Приход',
                        InventoryDocument::TYPE_OUT => 'Расход',
                        InventoryDocument::TYPE_MOVE => 'Перемещение',
                        InventoryDocument::TYPE_WRITE_OFF => 'Списание',
                        InventoryDocument::TYPE_INVENTORY => 'Инвентаризация',
                        InventoryDocument::TYPE_REVERSAL => 'Сторнирование',
                        default => $state,
                    }),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        InventoryDocument::STATUS_DRAFT => 'gray',
                        InventoryDocument::STATUS_POSTED => 'success',
                        InventoryDocument::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        InventoryDocument::STATUS_DRAFT => 'Черновик',
                        InventoryDocument::STATUS_POSTED => 'Проведен',
                        InventoryDocument::STATUS_CANCELLED => 'Отменен',
                        default => $state,
                    }),
                TextColumn::make('warehouse.name')
                    ->label('Склад')
                    ->sortable(),
                TextColumn::make('warehouseTo.name')
                    ->label('Склад (куда)')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('supplier.name')
                    ->label('Поставщик')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('reason')
                    ->label('Причина')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('posted_at')
                    ->label('Проведен')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Тип')
                    ->options([
                        InventoryDocument::TYPE_IN => 'Приход',
                        InventoryDocument::TYPE_OUT => 'Расход',
                        InventoryDocument::TYPE_MOVE => 'Перемещение',
                        InventoryDocument::TYPE_WRITE_OFF => 'Списание',
                        InventoryDocument::TYPE_INVENTORY => 'Инвентаризация',
                        InventoryDocument::TYPE_REVERSAL => 'Сторнирование',
                    ]),
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        InventoryDocument::STATUS_DRAFT => 'Черновик',
                        InventoryDocument::STATUS_POSTED => 'Проведен',
                        InventoryDocument::STATUS_CANCELLED => 'Отменен',
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
