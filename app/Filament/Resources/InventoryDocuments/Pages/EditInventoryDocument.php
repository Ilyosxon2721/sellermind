<?php

namespace App\Filament\Resources\InventoryDocuments\Pages;

use App\Filament\Resources\InventoryDocuments\InventoryDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditInventoryDocument extends EditRecord
{
    protected static string $resource = InventoryDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
