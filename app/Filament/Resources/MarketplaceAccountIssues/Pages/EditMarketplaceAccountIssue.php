<?php

namespace App\Filament\Resources\MarketplaceAccountIssues\Pages;

use App\Filament\Resources\MarketplaceAccountIssues\MarketplaceAccountIssueResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMarketplaceAccountIssue extends EditRecord
{
    protected static string $resource = MarketplaceAccountIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
