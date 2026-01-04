<?php

namespace App\Filament\Resources\GenerationTasks\Pages;

use App\Filament\Resources\GenerationTasks\GenerationTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditGenerationTask extends EditRecord
{
    protected static string $resource = GenerationTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
