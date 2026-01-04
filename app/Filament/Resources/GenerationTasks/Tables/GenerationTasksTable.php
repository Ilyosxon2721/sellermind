<?php

namespace App\Filament\Resources\GenerationTasks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GenerationTasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('company.name')
                    ->label('Компания')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Пользователь')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Тип')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'pending' => 'gray',
                        'in_progress' => 'info',
                        'done' => 'success',
                        'failed' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'pending' => 'В очереди',
                        'in_progress' => 'Выполняется',
                        'done' => 'Готово',
                        'failed' => 'Ошибка',
                        default => $state,
                    }),
                TextColumn::make('progress')
                    ->label('Прогресс')
                    ->numeric()
                    ->sortable()
                    ->suffix('%'),
                TextColumn::make('created_at')
                    ->label('Создана')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'pending' => 'В очереди',
                        'in_progress' => 'Выполняется',
                        'done' => 'Готово',
                        'failed' => 'Ошибка',
                    ]),
                SelectFilter::make('type')
                    ->label('Тип задачи')
                    ->options([
                        'description' => 'Описание товара',
                        'seo' => 'SEO оптимизация',
                        'image' => 'Генерация фото',
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
