<?php

namespace App\Filament\Resources\MarketplaceAccountIssues\Pages;

use App\Filament\Resources\MarketplaceAccountIssues\MarketplaceAccountIssueResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMarketplaceAccountIssues extends ListRecords
{
    protected static string $resource = MarketplaceAccountIssueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
