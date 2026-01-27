<?php

namespace App\Filament\Resources\InventoryDocuments\Pages;

use App\Filament\Resources\InventoryDocuments\InventoryDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListInventoryDocuments extends ListRecords
{
    protected static string $resource = InventoryDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
