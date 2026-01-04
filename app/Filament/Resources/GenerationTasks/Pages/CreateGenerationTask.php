<?php

namespace App\Filament\Resources\GenerationTasks\Pages;

use App\Filament\Resources\GenerationTasks\GenerationTaskResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGenerationTask extends CreateRecord
{
    protected static string $resource = GenerationTaskResource::class;
}
