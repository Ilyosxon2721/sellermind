<?php

namespace App\Filament\Resources\AgentTasks\Pages;

use App\Filament\Resources\AgentTasks\AgentTaskResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAgentTask extends EditRecord
{
    protected static string $resource = AgentTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
