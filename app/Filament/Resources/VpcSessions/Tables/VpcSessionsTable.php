<?php

namespace App\Filament\Resources\VpcSessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class VpcSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('company.name')
                    ->label('Компания')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'running' => 'success',
                        'creating' => 'info',
                        'stopping', 'stopped' => 'gray',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'running' => 'Запущена',
                        'creating' => 'Создание',
                        'stopping' => 'Остановка',
                        'stopped' => 'Остановлена',
                        'failed' => 'Ошибка',
                        default => $state,
                    }),
                TextColumn::make('control_mode')
                    ->label('Режим')
                    ->toggleable(),
                TextColumn::make('last_activity_at')
                    ->label('Активность')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('started_at')
                    ->label('Запуск')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'creating' => 'Создание',
                        'running' => 'Запущена',
                        'stopping' => 'Остановка',
                        'stopped' => 'Остановлена',
                        'failed' => 'Ошибка',
                    ]),
                SelectFilter::make('control_mode')
                    ->label('Режим управления')
                    ->options([
                        'AGENT_CONTROL' => 'Агент',
                        'USER_CONTROL' => 'Пользователь',
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
            ->defaultSort('last_activity_at', 'desc');
    }
}
