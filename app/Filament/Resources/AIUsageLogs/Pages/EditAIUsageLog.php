<?php

namespace App\Filament\Resources\AIUsageLogs\Pages;

use App\Filament\Resources\AIUsageLogs\AIUsageLogResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAIUsageLog extends EditRecord
{
    protected static string $resource = AIUsageLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
