<?php

namespace App\Filament\Resources\VpcSessions\Pages;

use App\Filament\Resources\VpcSessions\VpcSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVpcSession extends EditRecord
{
    protected static string $resource = VpcSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
