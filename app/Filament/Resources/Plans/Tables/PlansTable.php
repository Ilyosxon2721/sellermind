<?php

namespace App\Filament\Resources\Plans\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PlansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')
                    ->label('#')
                    ->sortable()
                    ->width(50),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray'),
                TextColumn::make('price')
                    ->label('Цена')
                    ->formatStateUsing(fn ($state, $record) => number_format((float) $state, 0, '.', ' ').' '.$record->currency)
                    ->sortable(),
                TextColumn::make('billing_period')
                    ->label('Период')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'monthly' => 'Мес.',
                        'quarterly' => 'Кв.',
                        'yearly' => 'Год',
                        default => $state,
                    }),
                TextColumn::make('max_marketplace_accounts')
                    ->label('МП')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_products')
                    ->label('Товары')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_orders_per_month')
                    ->label('Заказы')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_users')
                    ->label('Юзеры')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('max_ai_requests')
                    ->label('AI')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Актив.')
                    ->boolean(),
                IconColumn::make('is_popular')
                    ->label('Попул.')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Статус')
                    ->options([
                        '1' => 'Активные',
                        '0' => 'Неактивные',
                    ]),
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
