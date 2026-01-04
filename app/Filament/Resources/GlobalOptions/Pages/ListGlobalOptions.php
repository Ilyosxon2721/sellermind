<?php

namespace App\Filament\Resources\GlobalOptions\Pages;

use App\Filament\Resources\GlobalOptions\GlobalOptionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGlobalOptions extends ListRecords
{
    protected static string $resource = GlobalOptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
