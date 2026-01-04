<?php

namespace App\Filament\Resources\AIUsageLogs\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AIUsageLogsTable
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
                TextColumn::make('model')
                    ->label('Модель')
                    ->searchable(),
                TextColumn::make('tokens_input')
                    ->label('IN токены')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tokens_output')
                    ->label('OUT токены')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('images_generated')
                    ->label('Изображения')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('cost_estimated')
                    ->label('Стоимость ($)')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Дата')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('company_id')
                    ->label('Компания')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('model')
                    ->label('Модель')
                    ->options([
                        'gpt-4' => 'GPT-4',
                        'gpt-4o' => 'GPT-4o',
                        'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                        'dall-e-3' => 'DALL-E 3',
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
