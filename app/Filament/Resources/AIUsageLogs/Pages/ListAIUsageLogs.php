<?php

namespace App\Filament\Resources\AIUsageLogs\Pages;

use App\Filament\Resources\AIUsageLogs\AIUsageLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAIUsageLogs extends ListRecords
{
    protected static string $resource = AIUsageLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
