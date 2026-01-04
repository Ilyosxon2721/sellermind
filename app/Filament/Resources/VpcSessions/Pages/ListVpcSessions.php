<?php

namespace App\Filament\Resources\VpcSessions\Pages;

use App\Filament\Resources\VpcSessions\VpcSessionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVpcSessions extends ListRecords
{
    protected static string $resource = VpcSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
