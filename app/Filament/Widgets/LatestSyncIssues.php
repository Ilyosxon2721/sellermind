<?php

namespace App\Filament\Widgets;

use App\Models\MarketplaceSyncLog;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestSyncIssues extends BaseWidget
{
    protected static ?string $heading = 'Последние ошибки синхронизации';
    protected static ?int $sort = 4;
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                MarketplaceSyncLog::where('status', 'error')->latest()->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('account.name')
                    ->label('Аккаунт'),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge(),
                Tables\Columns\TextColumn::make('message')
                    ->label('Ошибка')
                    ->limit(50)
                    ->color('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Время')
                    ->dateTime()
                    ->since(),
            ])
            ->actions([
                Action::make('view')
                    ->label('Перейти')
                    ->url(fn (MarketplaceSyncLog $record): string => "/admin/marketplace-sync-logs/{$record->id}")
                    ->icon('heroicon-m-eye'),
            ]);
    }
}
