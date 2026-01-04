<?php

namespace App\Filament\Resources\GlobalOptions\Pages;

use App\Filament\Resources\GlobalOptions\GlobalOptionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGlobalOption extends EditRecord
{
    protected static string $resource = GlobalOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
