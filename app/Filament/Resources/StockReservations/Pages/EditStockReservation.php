<?php

namespace App\Filament\Resources\StockReservations\Pages;

use App\Filament\Resources\StockReservations\StockReservationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditStockReservation extends EditRecord
{
    protected static string $resource = StockReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
