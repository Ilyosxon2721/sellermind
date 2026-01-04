<?php

namespace App\Filament\Resources\AgentTasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AgentTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Задача')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('agent.name')
                    ->label('Агент')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->badge()
                    ->color('gray'),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'active' => 'info',
                        'completed' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'active' => 'Активна',
                        'completed' => 'Готово',
                        'failed' => 'Ошибка',
                        'cancelled' => 'Отмена',
                        default => $state,
                    }),
                TextColumn::make('created_at')
                    ->label('Дата создания')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активна',
                        'completed' => 'Готово',
                        'failed' => 'Ошибка',
                    ]),
                SelectFilter::make('agent_id')
                    ->label('Агент')
                    ->relationship('agent', 'name'),
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
