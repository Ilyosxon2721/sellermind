<?php

namespace App\Filament\Resources\MarketplaceSyncLogs\Pages;

use App\Filament\Resources\MarketplaceSyncLogs\MarketplaceSyncLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceSyncLog extends EditRecord
{
    protected static string $resource = MarketplaceSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
