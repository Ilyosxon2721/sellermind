<?php

namespace App\Filament\Resources\Subscriptions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class SubscriptionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Компания')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plan.name')
                    ->label('Тариф')
                    ->sortable()
                    ->badge(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'trial' => 'info',
                        'pending' => 'warning',
                        'expired' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Активна',
                        'trial' => 'Пробный',
                        'pending' => 'Ожидает',
                        'expired' => 'Истекла',
                        'cancelled' => 'Отменена',
                        default => $state,
                    }),
                TextColumn::make('starts_at')
                    ->label('Начало')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                TextColumn::make('ends_at')
                    ->label('Окончание')
                    ->dateTime('d.m.Y')
                    ->sortable(),
                TextColumn::make('trial_ends_at')
                    ->label('Конец trial')
                    ->dateTime('d.m.Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('amount_paid')
                    ->label('Оплачено')
                    ->formatStateUsing(fn ($state) => $state > 0 ? number_format((float) $state, 0, '.', ' ').' UZS' : '—')
                    ->sortable(),
                TextColumn::make('payment_method')
                    ->label('Метод')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'click' => 'Click',
                        'payme' => 'Payme',
                        'bank_transfer' => 'Перевод',
                        default => $state ?? '—',
                    })
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('current_products_count')
                    ->label('Товаров')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('current_orders_count')
                    ->label('Заказов')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('current_ai_requests')
                    ->label('AI')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime('d.m.Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активна',
                        'trial' => 'Пробный',
                        'pending' => 'Ожидает',
                        'expired' => 'Истекла',
                        'cancelled' => 'Отменена',
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
