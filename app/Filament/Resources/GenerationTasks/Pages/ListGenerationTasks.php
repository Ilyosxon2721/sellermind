<?php

namespace App\Filament\Resources\GenerationTasks\Pages;

use App\Filament\Resources\GenerationTasks\GenerationTaskResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGenerationTasks extends ListRecords
{
    protected static string $resource = GenerationTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
